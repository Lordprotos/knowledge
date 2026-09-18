<?php
namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'themes')]
class Theme
{
    use Auditable;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 120, unique: true)]
    private string $name;
    /** @var Collection<int, Curriculum> */
    #[ORM\OneToMany(mappedBy: 'theme', targetEntity: Curriculum::class, orphanRemoval: true)]
    private Collection $curricula;
    public function __construct(string $name) { $this->name = $name; $this->curricula = new ArrayCollection(); }
    public function getId(): ?int { return $this->id; }
    public function getName(): string { return $this->name; }
    public function setName(string $name): void { $this->name = $name; }
    /** @return Collection<int, Curriculum> */ public function getCurricula(): Collection { return $this->curricula; }
}
