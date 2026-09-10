<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity, ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'certifications')]
#[ORM\UniqueConstraint(name: 'one_certification_per_theme_user', columns: ['user_id', 'theme_id'])]
class Certification
{
    use Auditable;
    #[ORM\Id, ORM\GeneratedValue, ORM\Column] private ?int $id = null;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private User $user;
    #[ORM\ManyToOne, ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')] private Theme $theme;
    #[ORM\Column] private \DateTimeImmutable $obtainedAt;
    public function __construct(User $user, Theme $theme) { $this->user=$user; $this->theme=$theme; $this->obtainedAt=new \DateTimeImmutable(); }
    public function getTheme(): Theme { return $this->theme; }
}
