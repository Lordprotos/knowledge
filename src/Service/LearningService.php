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

/** Gère les achats enregistrés, les droits d’accès et la progression pédagogique. */
final class LearningService
{
    public function __construct(private EntityManagerInterface $em) {}

    public function buyCurriculum(User $user, Curriculum $curriculum): void
    {
        $this->buy($user, $curriculum->getPriceCents(), $curriculum);
    }

    public function buyLesson(User $user, Lesson $lesson): void
    {
        $this->buy($user, $lesson->getPriceCents(), null, $lesson);
    }

    /** Enregistre un achat confirmé pour une seule cible : cursus ou leçon. */
    private function buy(
        User $user,
        int $amount,
        ?Curriculum $curriculum = null,
        ?Lesson $lesson = null,
    ): void {
        $criteria = ['user' => $user];

        if ($curriculum) {
            $criteria['curriculum'] = $curriculum;
        } else {
            $criteria['lesson'] = $lesson;
        }

        // Évite un doublon lors d’un nouvel appel ; ce contrôle ne verrouille pas les achats concurrents.
        if (!$this->em->getRepository(Purchase::class)->findOneBy($criteria)) {
            $purchase = new Purchase($user, $amount, $curriculum, $lesson);
            $purchase->setAuditUser($user->getEmail());
            $this->em->persist($purchase);
            $this->em->flush();
        }
    }

    /** Seuls les achats payés du client ouvrent les droits sur le cursus. */
    public function ownsCurriculum(User $user, Curriculum $curriculum): bool
    {
        return null !== $this->em->getRepository(Purchase::class)->findOneBy([
            'user' => $user,
            'curriculum' => $curriculum,
            'status' => 'paid',
        ]);
    }

    /** Une leçon est accessible par achat individuel ou par achat de son cursus. */
    public function canAccess(User $user, Lesson $lesson): bool
    {
        return $this->ownsCurriculum($user, $lesson->getCurriculum())
            || null !== $this->em->getRepository(Purchase::class)->findOneBy([
                'user' => $user,
                'lesson' => $lesson,
                'status' => 'paid',
            ]);
    }

    /** Valide une leçon, puis vérifie la complétion du cursus et du thème. */
    public function validateLesson(User $user, Lesson $lesson): void
    {
        if (!$this->canAccess($user, $lesson)) {
            throw new \DomainException('Achetez cette leçon ou son cursus pour la valider.');
        }
        // Réutilise la progression existante si la leçon a déjà été validée.
        $progress =
            $this->em
                ->getRepository(LessonProgress::class)
                ->findOneBy(['user' => $user, 'lesson' => $lesson]) ??
            new LessonProgress($user, $lesson);
        $progress->validate();
        $progress->setAuditUser($user->getEmail());
        $this->em->persist($progress);
        $this->em->flush();
        // Un cursus est terminé lorsque chacune de ses leçons est validée.
        $curriculum = $lesson->getCurriculum();
        $curriculumLessons = $curriculum->getLessons();
        $curriculumDone = 0;
        foreach ($curriculumLessons as $candidate) {
            if (
                ($entry = $this->em
                    ->getRepository(LessonProgress::class)
                    ->findOneBy(['user' => $user, 'lesson' => $candidate])) &&
                $entry->isValidated()
            ) {
                ++$curriculumDone;
            }
        }
        if (
            $curriculumLessons->count() === $curriculumDone &&
            !$this->em
                ->getRepository(CurriculumProgress::class)
                ->findOneBy(['user' => $user, 'curriculum' => $curriculum])
        ) {
            $completion = new CurriculumProgress($user, $curriculum);
            $completion->setAuditUser($user->getEmail());
            $this->em->persist($completion);
            $this->em->flush();
        }
        // La certification porte sur toutes les leçons du thème, pas seulement sur ce cursus.
        $theme = $lesson->getCurriculum()->getTheme();
        $all = 0;
        $done = 0;
        foreach ($theme->getCurricula() as $curriculum) {
            foreach ($curriculum->getLessons() as $candidate) {
                ++$all;
                if (
                    ($entry = $this->em
                        ->getRepository(LessonProgress::class)
                        ->findOneBy(['user' => $user, 'lesson' => $candidate])) &&
                    $entry->isValidated()
                ) {
                    ++$done;
                }
            }
        }
        if (
            $all === $done &&
            !$this->em
                ->getRepository(Certification::class)
                ->findOneBy(['user' => $user, 'theme' => $theme])
        ) {
            $certification = new Certification($user, $theme);
            $certification->setAuditUser($user->getEmail());
            $this->em->persist($certification);
            $this->em->flush();
        }
    }
}
