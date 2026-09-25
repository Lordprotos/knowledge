<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** Formation achetable en entier, rattachée à un thème et composée de leçons. */
#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'curricula')]
class Curriculum
{
    use Auditable;

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'curricula'), ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Theme $theme;

    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(type: 'integer')]
    private int $priceCents;

    /** @var Collection<int, Lesson> */
    #[ORM\OneToMany(mappedBy: 'curriculum', targetEntity: Lesson::class, orphanRemoval: true)]
    private Collection $lessons;

    public function __construct(Theme $theme, string $title, int $priceCents)
    {
        $this->theme = $theme;
        $this->title = $title;
        $this->priceCents = $priceCents;
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function setTheme(Theme $theme): void
    {
        $this->theme = $theme;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getPriceCents(): int
    {
        return $this->priceCents;
    }

    public function setPriceCents(int $priceCents): void
    {
        $this->priceCents = $priceCents;
    }

    /** @return Collection<int, Lesson> */ public function getLessons(): Collection
    {
        return $this->lessons;
    }
}
