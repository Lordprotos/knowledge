<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/** Common audit columns required on every business record. */
trait Auditable
{
    #[ORM\Column(name: 'created_at')] private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at')] private \DateTimeImmutable $updatedAt;
    #[ORM\Column(name: 'created_by', length: 180, nullable: true)] private ?string $createdBy = null;
    #[ORM\Column(name: 'updated_by', length: 180, nullable: true)] private ?string $updatedBy = null;

    #[ORM\PrePersist] public function initializeAuditDates(): void { $this->createdAt = $this->updatedAt = new \DateTimeImmutable(); }
    #[ORM\PreUpdate] public function updateAuditDate(): void { $this->updatedAt = new \DateTimeImmutable(); }
    public function setAuditUser(?string $email): void { $this->createdBy = $this->updatedBy = $email; }
}
