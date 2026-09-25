<?php

namespace App\Tests\Functional;

use App\Entity\Curriculum;
use App\Entity\Lesson;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AdminWorkflowTest extends WebTestCase
{
    private EntityManagerInterface $em;
    private User $admin;
    private KernelBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        // Ces tests recréent le schéma : utiliser exclusivement une base de test dédiée.
        $tool = new SchemaTool($this->em);
        $tool->dropSchema($this->em->getMetadataFactory()->getAllMetadata());
        $tool->createSchema($this->em->getMetadataFactory()->getAllMetadata());
        $this->admin = new User('admin@example.test', '');
        $this->admin->setPassword(
            self::getContainer()
                ->get(UserPasswordHasherInterface::class)
                ->hashPassword($this->admin, 'SecurePassword12'),
        );
        $this->admin->setRoles(['ROLE_ADMIN', 'ROLE_CLIENT']);
        $this->admin->setVerificationToken('verified');
        $this->admin->verify();
        $this->em->persist($this->admin);
        $this->em->flush();
    }

    public function testClientCannotAccessBackoffice(): void
    {
        $this->client->request('GET', '/admin');
        self::assertResponseRedirects('/connexion');
    }

    public function testAdminCanCreateThemeCurriculumAndLesson(): void
    {
        $client = $this->client;
        $client->loginUser($this->admin);
        $client->request('GET', '/admin');
        $client->submit(
            $client
                ->getCrawler()
                ->filter('form[action="/admin/theme/new"]')
                ->form(['name' => 'Photographie']),
        );
        self::assertResponseRedirects('/admin');
        $theme = $this->em->getRepository(Theme::class)->findOneBy(['name' => 'Photographie']);
        self::assertNotNull($theme);
        $client->request('GET', '/admin');
        $client->submit(
            $client
                ->getCrawler()
                ->filter('form[action="/admin/curriculum/new"]')
                ->form([
                    'theme_id' => $theme->getId(),
                    'title' => 'Bases photo',
                    'price' => '39.90',
                ]),
        );
        $curriculum = $this->em
            ->getRepository(Curriculum::class)
            ->findOneBy(['title' => 'Bases photo']);
        self::assertNotNull($curriculum);
        self::assertSame(3990, $curriculum->getPriceCents());
        $client->request('GET', '/admin');
        $client->submit(
            $client
                ->getCrawler()
                ->filter('form[action="/admin/lesson/new"]')
                ->form([
                    'curriculum_id' => $curriculum->getId(),
                    'title' => 'La lumière',
                    'position' => 1,
                    'price' => '9.90',
                    'content' => 'Cours de démonstration.',
                    'video_url' => '',
                ]),
        );
        $lesson = $this->em->getRepository(Lesson::class)->findOneBy(['title' => 'La lumière']);
        self::assertNotNull($lesson);
        self::assertSame(990, $lesson->getPriceCents());
    }
}
