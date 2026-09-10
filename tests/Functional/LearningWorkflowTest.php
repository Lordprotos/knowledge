<?php
namespace App\Tests\Functional;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\CurriculumProgress;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Theme;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Covers sandbox purchases, protected content and automatic completion rules. */
final class LearningWorkflowTest extends WebTestCase
{
    private EntityManagerInterface $entityManager;
    private User $user;
    private Curriculum $curriculum;
    private Lesson $lesson;
    private AbstractBrowser $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown(); $this->client = static::createClient(); $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $tool = new SchemaTool($this->entityManager); $tool->dropSchema($this->entityManager->getMetadataFactory()->getAllMetadata()); $tool->createSchema($this->entityManager->getMetadataFactory()->getAllMetadata());
        $this->user = new User('client@example.test', ''); $this->user->setPassword(self::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($this->user, 'SecurePassword12')); $this->user->setVerificationToken('verified'); $this->user->verify();
        $theme = new Theme('Test'); $this->curriculum = new Curriculum($theme, 'Cursus test', 5000); $this->lesson = new Lesson($this->curriculum, 'Leçon test', 1, 2600);
        $this->entityManager->persist($this->user); $this->entityManager->persist($theme); $this->entityManager->persist($this->curriculum); $this->entityManager->persist($this->lesson); $this->entityManager->flush();
        $userId = $this->user->getId(); $curriculumId = $this->curriculum->getId(); $lessonId = $this->lesson->getId(); $this->entityManager->clear();
        $this->user = $this->entityManager->find(User::class, $userId); $this->curriculum = $this->entityManager->find(Curriculum::class, $curriculumId); $this->lesson = $this->entityManager->find(Lesson::class, $lessonId);
    }

    public function testClientCanBuyCurriculumAndAccessLesson(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/cursus/'.$this->curriculum->getId());
        $this->client->submitForm('Acheter le cursus'); self::assertResponseRedirects('/cursus/'.$this->curriculum->getId());
        $this->client->request('GET', '/lecon/'.$this->lesson->getId()); self::assertResponseIsSuccessful(); self::assertSelectorTextContains('h1', 'Leçon test');
    }

    public function testClientCanBuyLessonValidateCurriculumAndObtainCertification(): void
    {
        $this->client->loginUser($this->user);
        $this->client->request('GET', '/cursus/'.$this->curriculum->getId()); $this->client->submit($this->client->getCrawler()->filter('button.button-secondary')->form()); self::assertResponseRedirects('/lecon/'.$this->lesson->getId());
        $this->client->followRedirect(); $this->client->submitForm('Valider cette leçon'); self::assertResponseRedirects('/lecon/'.$this->lesson->getId());
        self::assertNotNull($this->entityManager->getRepository(LessonProgress::class)->findOneBy(['user'=>$this->user, 'lesson'=>$this->lesson]));
        self::assertNotNull($this->entityManager->getRepository(CurriculumProgress::class)->findOneBy(['user'=>$this->user, 'curriculum'=>$this->curriculum]));
        self::assertCount(1, $this->entityManager->getRepository(Certification::class)->findBy(['user'=>$this->user]));
    }
}
