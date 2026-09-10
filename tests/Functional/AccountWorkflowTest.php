<?php
namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;

/** Covers the public account lifecycle: registration, mail activation and login. */
final class AccountWorkflowTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private AbstractBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown(); $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->entityManager); $tool->dropSchema($this->entityManager->getMetadataFactory()->getAllMetadata()); $tool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
    }

    public function testVisitorCanRegisterActivateAndLogIn(): void
    {
        $this->client->request('GET', '/inscription');
        $this->client->submitForm('Créer mon compte', ['email' => 'learner@example.test', 'password' => 'SecurePassword12']);
        self::assertResponseRedirects('/connexion');

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'learner@example.test']);
        self::assertNotNull($user); self::assertSame(['ROLE_CLIENT'], $user->getRoles()); self::assertFalse($user->isVerified());
        $token = $user->getVerificationToken(); self::assertNotNull($token);
        $this->client->request('GET', '/activation/'.$token);
        self::assertResponseRedirects('/connexion'); $this->entityManager->clear();
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => 'learner@example.test']); self::assertTrue($user->isVerified());

        $this->client->request('GET', '/connexion');
        $this->client->submitForm('Se connecter', ['_username' => 'learner@example.test', '_password' => 'SecurePassword12']);
        self::assertResponseRedirects('/');
    }
}
