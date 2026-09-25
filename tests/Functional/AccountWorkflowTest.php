<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportException;

/** Vérifie l’inscription, la reprise après échec d’envoi et l’activation du compte. */
final class AccountWorkflowTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        // Ces tests recréent le schéma : utiliser exclusivement une base de test dédiée.
        $tool = new SchemaTool($this->entityManager);
        $tool->dropSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
        $tool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

    public function testMailFailureAllowsRetryWithoutDuplicatingOrActivatingAccount(): void
    {
        $this->client->disableReboot();
        $mailer = $this->createMock(MailerInterface::class);
        $attempt = 0;
        $mailer
            ->expects(self::exactly(2))
            ->method('send')
            ->willReturnCallback(function () use (&$attempt): void {
                if (++$attempt === 1) {
                    throw new TransportException('SMTP unavailable');
                }
            });
        self::getContainer()->set(MailerInterface::class, $mailer);
        $this->client->request('GET', '/inscription');
        $this->client->submitForm('Créer mon compte', [
            'email' => 'retry@example.test',
            'password' => 'SecurePassword12',
        ]);
        self::assertResponseRedirects('/inscription', 303);
        $this->client->followRedirect();
        self::assertSelectorTextContains('.flash-error', 'n’a pas pu être envoyé');
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'retry@example.test']);
        self::assertNotNull($user);
        self::assertFalse($user->isVerified());
        $token = $user->getVerificationToken();

        $this->client->submitForm('Créer mon compte', [
            'email' => 'retry@example.test',
            'password' => 'WrongPassword12',
        ]);
        self::assertResponseRedirects('/inscription');
        $this->client->followRedirect();
        $this->client->submitForm('Créer mon compte', [
            'email' => 'retry@example.test',
            'password' => 'SecurePassword12',
        ]);
        self::assertResponseRedirects('/connexion');
        self::assertSame(
            1,
            $this->entityManager
                ->getRepository(User::class)
                ->count(['email' => 'retry@example.test']),
        );
        self::assertSame($token, $user->getVerificationToken());
        self::assertFalse($user->isVerified());
    }

    public function testVisitorCanRegisterActivateAndLogIn(): void
    {
        $this->client->request('GET', '/inscription');
        $this->client->submitForm('Créer mon compte', [
            'email' => 'learner@example.test',
            'password' => 'SecurePassword12',
        ]);
        self::assertResponseRedirects('/connexion');

        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'learner@example.test']);
        self::assertNotNull($user);
        self::assertSame(['ROLE_CLIENT'], $user->getRoles());
        self::assertFalse($user->isVerified());
        $token = $user->getVerificationToken();
        self::assertNotNull($token);
        $this->client->request('GET', '/activation/' . $token);
        self::assertResponseRedirects('/connexion');
        $this->entityManager->clear();
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => 'learner@example.test']);
        self::assertTrue($user->isVerified());

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', [
            '_username' => 'learner@example.test',
            '_password' => 'SecurePassword12',
        ]);
        self::assertResponseRedirects('/');
    }
}
