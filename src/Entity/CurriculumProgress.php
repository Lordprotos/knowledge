<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Stores automatic curriculum completion once every lesson is validated. */
#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'curriculum_progress')]
#[ORM\UniqueConstraint(name: 'one_curriculum_progress_per_user', columns: ['user_id', 'curriculum_id'])]
class CurriculumProgress
{
    use Auditable;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $user;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private Curriculum $curriculum;
    #[ORM\Column] private \DateTimeImmutable $validatedAt;
    public function __construct(User $user, Curriculum $curriculum) { $this->user = $user; $this->curriculum = $curriculum; $this->validatedAt = new \DateTimeImmutable(); }
}
