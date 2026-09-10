<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'lessons')]
#[ORM\UniqueConstraint(name: 'lesson_position_per_curriculum', columns: ['curriculum_id', 'position'])]
class Lesson
{
    use Auditable;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne(inversedBy: 'lessons'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private Curriculum $curriculum;
    #[ORM\Column(length: 180)] private string $title;
    #[ORM\Column(type: 'integer')] private int $position;
    #[ORM\Column(type: 'integer')] private int $priceCents;
    #[ORM\Column(type: 'text')] private string $content = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';
    #[ORM\Column(length: 500, nullable: true)] private ?string $videoUrl = null;
    public function __construct(Curriculum $curriculum, string $title, int $position, int $priceCents) { $this->curriculum=$curriculum; $this->title=$title; $this->position=$position; $this->priceCents=$priceCents; }
    public function getId(): ?int { return $this->id; }
    public function getCurriculum(): Curriculum { return $this->curriculum; }
    public function getTitle(): string { return $this->title; }
    public function getPosition(): int { return $this->position; }
    public function getPriceCents(): int { return $this->priceCents; }
    public function getContent(): string { return $this->content; }
}
