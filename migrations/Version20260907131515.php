<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajoute la progression des cursus et son unicité par utilisateur et cursus.
 */
final class Version20260907131515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Applique cette évolution au schéma de la base.
        $this->addSql('CREATE TABLE curriculum_progress (
            id INT AUTO_INCREMENT NOT NULL,
            validated_at DATETIME NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            created_by VARCHAR(180) DEFAULT NULL,
            updated_by VARCHAR(180) DEFAULT NULL,
            user_id INT NOT NULL,
            curriculum_id INT NOT NULL,
            INDEX IDX_D8D92C02A76ED395 (user_id),
            INDEX IDX_D8D92C025AEA4428 (curriculum_id),
            UNIQUE INDEX one_curriculum_progress_per_user (user_id, curriculum_id),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE curriculum_progress
            ADD CONSTRAINT FK_D8D92C02A76ED395
            FOREIGN KEY (user_id) REFERENCES users (id)
            ON DELETE CASCADE');
        $this->addSql('ALTER TABLE curriculum_progress
            ADD CONSTRAINT FK_D8D92C025AEA4428
            FOREIGN KEY (curriculum_id) REFERENCES curricula (id)
            ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Annule cette évolution ; les données des colonnes ou tables supprimées sont perdues.
        $this->addSql('ALTER TABLE curriculum_progress
            DROP FOREIGN KEY FK_D8D92C02A76ED395');
        $this->addSql('ALTER TABLE curriculum_progress
            DROP FOREIGN KEY FK_D8D92C025AEA4428');
        $this->addSql('DROP TABLE curriculum_progress');
    }
}
