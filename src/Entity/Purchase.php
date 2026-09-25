<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Achat enregistré après confirmation ; les appels métier choisissent un cursus ou une leçon. */
#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'purchases')]
class Purchase
{
    use Auditable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Curriculum $curriculum = null;

    #[ORM\ManyToOne, ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?Lesson $lesson = null;

    #[ORM\Column(type: 'integer')]
    private int $amountCents;

    #[ORM\Column(length: 30)]
    private string $status = 'paid';

    #[ORM\Column]
    private \DateTimeImmutable $purchasedAt;

    public function __construct(
        User $user,
        int $amountCents,
        ?Curriculum $curriculum = null,
        ?Lesson $lesson = null,
    ) {
        $this->user = $user;
        $this->amountCents = $amountCents;
        $this->curriculum = $curriculum;
        $this->lesson = $lesson;
        $this->purchasedAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCurriculum(): ?Curriculum
    {
        return $this->curriculum;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }
}
