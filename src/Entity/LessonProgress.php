<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'lesson_progress')]
#[ORM\UniqueConstraint(name: 'one_progress_per_lesson_user', columns: ['user_id', 'lesson_id'])]
class LessonProgress
{
    use Auditable;
    public function validate(): void { $this->isValidated = true; $this->validatedAt = new \DateTimeImmutable(); }
    public function isValidated(): bool { return $this->isValidated; }
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $user;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private Lesson $lesson;
    #[ORM\Column] private bool $isValidated = false;
    #[ORM\Column(nullable: true)] private ?\DateTimeImmutable $validatedAt = null;
    public function __construct(User $user, Lesson $lesson) { $this->user=$user; $this->lesson=$lesson; }
}
