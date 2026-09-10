<?php
namespace App\Service;

use App\Entity\Certification;
use App\Entity\Curriculum;
use App\Entity\CurriculumProgress;
use App\Entity\Lesson;
use App\Entity\LessonProgress;
use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/** Coordinates the purchase sandbox, access rights and learning progress. */
final class LearningService
{
    public function __construct(private EntityManagerInterface $em) {}
    public function buyCurriculum(User $user, Curriculum $curriculum): void { $this->buy($user, $curriculum->getPriceCents(), $curriculum); }
    public function buyLesson(User $user, Lesson $lesson): void { $this->buy($user, $lesson->getPriceCents(), null, $lesson); }
    private function buy(User $user, int $amount, ?Curriculum $curriculum = null, ?Lesson $lesson = null): void
    {
        $criteria = ['user' => $user]; if ($curriculum) $criteria['curriculum'] = $curriculum; else $criteria['lesson'] = $lesson;
        if (!$this->em->getRepository(Purchase::class)->findOneBy($criteria)) { $purchase = new Purchase($user, $amount, $curriculum, $lesson); $purchase->setAuditUser($user->getEmail()); $this->em->persist($purchase); $this->em->flush(); }
    }
    public function canAccess(User $user, Lesson $lesson): bool
    {
        return null !== $this->em->getRepository(Purchase::class)->findOneBy(['user' => $user, 'lesson' => $lesson]) || null !== $this->em->getRepository(Purchase::class)->findOneBy(['user' => $user, 'curriculum' => $lesson->getCurriculum()]);
    }
    public function validateLesson(User $user, Lesson $lesson): void
    {
        if (!$this->canAccess($user, $lesson)) throw new \DomainException('Achetez cette leçon ou son cursus pour la valider.');
        $progress = $this->em->getRepository(LessonProgress::class)->findOneBy(['user' => $user, 'lesson' => $lesson]) ?? new LessonProgress($user, $lesson);
        $progress->validate(); $progress->setAuditUser($user->getEmail()); $this->em->persist($progress); $this->em->flush();
        $curriculum = $lesson->getCurriculum(); $curriculumLessons = $curriculum->getLessons(); $curriculumDone = 0;
        foreach ($curriculumLessons as $candidate) if (($entry = $this->em->getRepository(LessonProgress::class)->findOneBy(['user' => $user, 'lesson' => $candidate])) && $entry->isValidated()) ++$curriculumDone;
        if ($curriculumLessons->count() === $curriculumDone && !$this->em->getRepository(CurriculumProgress::class)->findOneBy(['user' => $user, 'curriculum' => $curriculum])) { $completion = new CurriculumProgress($user, $curriculum); $completion->setAuditUser($user->getEmail()); $this->em->persist($completion); $this->em->flush(); }
        $theme = $lesson->getCurriculum()->getTheme(); $all = 0; $done = 0;
        foreach ($theme->getCurricula() as $curriculum) foreach ($curriculum->getLessons() as $candidate) { ++$all; if (($entry = $this->em->getRepository(LessonProgress::class)->findOneBy(['user' => $user, 'lesson' => $candidate])) && $entry->isValidated()) ++$done; }
        if ($all === $done && !$this->em->getRepository(Certification::class)->findOneBy(['user' => $user, 'theme' => $theme])) { $certification = new Certification($user, $theme); $certification->setAuditUser($user->getEmail()); $this->em->persist($certification); $this->em->flush(); }
    }
}
