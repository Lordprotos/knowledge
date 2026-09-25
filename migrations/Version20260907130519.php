<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute les dates et auteurs d’audit aux tables métier existantes.
 */
final class Version20260907130519 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Applique cette évolution au schéma de la base.
        $this->addSql(
            'ALTER TABLE certifications
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE curricula
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE lesson_progress
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE lessons
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE purchases
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE themes
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
        $this->addSql(
            'ALTER TABLE users
            ADD created_at DATETIME NOT NULL,
            ADD updated_at DATETIME NOT NULL,
            ADD created_by VARCHAR(180) DEFAULT NULL,
            ADD updated_by VARCHAR(180) DEFAULT NULL',
        );
    }

    public function down(Schema $schema): void
    {
        // Annule cette évolution ; les données des colonnes ou tables supprimées sont perdues.
        $this->addSql(
            'ALTER TABLE certifications
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE curricula
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE lessons
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE lesson_progress
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE purchases
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE themes
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
        $this->addSql(
            'ALTER TABLE users
            DROP created_at,
            DROP updated_at,
            DROP created_by,
            DROP updated_by',
        );
    }
}
