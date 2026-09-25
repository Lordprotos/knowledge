<?php

namespace App\Tests\Functional;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\CurriculumProgress;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Theme;
use App\Entity\User;
use App\Service\LearningService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class LearningWorkflowTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $user;
    private Curriculum $curriculum;
    private Lesson $lesson;
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
        $this->user = new User('client@example.test', '');
        $this->user->setPassword(
            self::getContainer()
                ->get(UserPasswordHasherInterface::class)
                ->hashPassword($this->user, 'SecurePassword12'),
        );
        $this->user->setVerificationToken('verified');
        $this->user->verify();
        $theme = new Theme('Test');
        $this->curriculum = new Curriculum($theme, 'Cursus test', 5000);
        $this->lesson = new Lesson($this->curriculum, 'Leçon test', 1, 2600);
        $this->entityManager->persist($this->user);
        $this->entityManager->persist($theme);
        $this->entityManager->persist($this->curriculum);
        $this->entityManager->persist($this->lesson);
        $this->entityManager->flush();
        $userId = $this->user->getId();
        $curriculumId = $this->curriculum->getId();
        $lessonId = $this->lesson->getId();
        $this->entityManager->clear();
        $this->user = $this->entityManager->find(User::class, $userId);
        $this->curriculum = $this->entityManager->find(Curriculum::class, $curriculumId);
        $this->lesson = $this->entityManager->find(Lesson::class, $lessonId);
    }

    public function testPurchasedCurriculumAppearsInAccountAndCannotBeBoughtAgain(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/cursus/' . $this->curriculum->getId());
        $curriculumForm = $this->client->getCrawler()->selectButton('Acheter le cursus')->form();
        $lessonForm = $this->client->getCrawler()->selectButton('Acheter la leçon')->form();
        self::getContainer()
            ->get(LearningService::class)
            ->buyCurriculum($this->user, $this->curriculum);

        $this->client->submit($curriculumForm);
        self::assertResponseRedirects('/cursus/' . $this->curriculum->getId(), 303);
        $this->client->followRedirect();
        self::assertSelectorNotExists('form[action$="/acheter"]');
        self::assertSelectorExists('a[href="/lecon/' . $this->lesson->getId() . '"]');
        $this->client->submit($lessonForm);
        self::assertResponseRedirects('/lecon/' . $this->lesson->getId(), 303);

        $this->client->request('GET', '/certifications');
        self::assertSelectorTextContains('#purchases-title', 'Mes formations achetées');
        self::assertSelectorTextContains('.lesson-list', 'Cursus test');
        self::assertSelectorTextContains('main', 'Aucune certification obtenue.');
    }

    public function testPurchasedLessonCannotBeBoughtAgainAndIsPrivateToItsOwner(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/cursus/' . $this->curriculum->getId());
        $form = $this->client->getCrawler()->selectButton('Acheter la leçon')->form();
        self::getContainer()->get(LearningService::class)->buyLesson($this->user, $this->lesson);
        $this->client->submit($form);
        self::assertResponseRedirects('/lecon/' . $this->lesson->getId(), 303);
        $this->client->request('GET', '/certifications');
        self::assertSelectorTextContains('.lesson-list', 'Leçon test');

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $other = new User('other@example.test', 'hash');
        $other->verify();
        $em->persist($other);
        $em->flush();
        $this->client->loginUser($other);
        $this->client->request('GET', '/certifications');
        self::assertSelectorNotExists('.lesson-list article');
        $this->client->request('GET', '/cursus/' . $this->curriculum->getId());
        self::assertSelectorExists('form[action$="/acheter"]');
        $this->client->request('GET', '/lecon/' . $this->lesson->getId());
        self::assertResponseRedirects('/cursus/' . $this->curriculum->getId());
    }

    public function testClientWithPurchasedCurriculumCanAccessLesson(): void
    {
        self::getContainer()
            ->get(LearningService::class)
            ->buyCurriculum($this->user, $this->curriculum);
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/lecon/' . $this->lesson->getId());
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Leçon test');
    }

    public function testClientCanValidateLessonAndObtainCertification(): void
    {
        self::getContainer()->get(LearningService::class)->buyLesson($this->user, $this->lesson);
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/lecon/' . $this->lesson->getId());
        $this->client->submitForm('Valider cette leçon');
        self::assertResponseRedirects('/cursus/' . $this->curriculum->getId(), 303);
        self::assertNotNull(
            $this->entityManager
                ->getRepository(LessonProgress::class)
                ->findOneBy(['user' => $this->user, 'lesson' => $this->lesson]),
        );
        self::assertNotNull(
            $this->entityManager
                ->getRepository(CurriculumProgress::class)
                ->findOneBy(['user' => $this->user, 'curriculum' => $this->curriculum]),
        );
        self::assertCount(
            1,
            $this->entityManager
                ->getRepository(Certification::class)
                ->findBy(['user' => $this->user]),
        );
    }
}
