<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260907124648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE certifications (id INT AUTO_INCREMENT NOT NULL, obtained_at DATETIME NOT NULL, user_id INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_3B0D76D5A76ED395 (user_id), INDEX IDX_3B0D76D559027487 (theme_id), UNIQUE INDEX one_certification_per_theme_user (user_id, theme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE curricula (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, price_cents INT NOT NULL, theme_id INT NOT NULL, INDEX IDX_463CC9FC59027487 (theme_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lesson_progress (id INT AUTO_INCREMENT NOT NULL, is_validated TINYINT NOT NULL, validated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, lesson_id INT NOT NULL, INDEX IDX_6A46B85FA76ED395 (user_id), INDEX IDX_6A46B85FCDF80196 (lesson_id), UNIQUE INDEX one_progress_per_lesson_user (user_id, lesson_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE lessons (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(180) NOT NULL, position INT NOT NULL, price_cents INT NOT NULL, content LONGTEXT NOT NULL, video_url VARCHAR(500) DEFAULT NULL, curriculum_id INT NOT NULL, INDEX IDX_3F4218D95AEA4428 (curriculum_id), UNIQUE INDEX lesson_position_per_curriculum (curriculum_id, position), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE purchases (id INT AUTO_INCREMENT NOT NULL, amount_cents INT NOT NULL, status VARCHAR(30) NOT NULL, purchased_at DATETIME NOT NULL, user_id INT NOT NULL, curriculum_id INT DEFAULT NULL, lesson_id INT DEFAULT NULL, INDEX IDX_AA6431FEA76ED395 (user_id), INDEX IDX_AA6431FE5AEA4428 (curriculum_id), INDEX IDX_AA6431FECDF80196 (lesson_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE themes (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, UNIQUE INDEX UNIQ_154232DE5E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, verification_token VARCHAR(64) DEFAULT NULL, UNIQUE INDEX UNIQ_1483A5E9E7927C74 (email), UNIQUE INDEX UNIQ_1483A5E9C1CC006B (verification_token), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE certifications ADD CONSTRAINT FK_3B0D76D5A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE certifications ADD CONSTRAINT FK_3B0D76D559027487 FOREIGN KEY (theme_id) REFERENCES themes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE curricula ADD CONSTRAINT FK_463CC9FC59027487 FOREIGN KEY (theme_id) REFERENCES themes (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85FA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lesson_progress ADD CONSTRAINT FK_6A46B85FCDF80196 FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE lessons ADD CONSTRAINT FK_3F4218D95AEA4428 FOREIGN KEY (curriculum_id) REFERENCES curricula (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchases ADD CONSTRAINT FK_AA6431FEA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchases ADD CONSTRAINT FK_AA6431FE5AEA4428 FOREIGN KEY (curriculum_id) REFERENCES curricula (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE purchases ADD CONSTRAINT FK_AA6431FECDF80196 FOREIGN KEY (lesson_id) REFERENCES lessons (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE certifications DROP FOREIGN KEY FK_3B0D76D5A76ED395');
        $this->addSql('ALTER TABLE certifications DROP FOREIGN KEY FK_3B0D76D559027487');
        $this->addSql('ALTER TABLE curricula DROP FOREIGN KEY FK_463CC9FC59027487');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FA76ED395');
        $this->addSql('ALTER TABLE lesson_progress DROP FOREIGN KEY FK_6A46B85FCDF80196');
        $this->addSql('ALTER TABLE lessons DROP FOREIGN KEY FK_3F4218D95AEA4428');
        $this->addSql('ALTER TABLE purchases DROP FOREIGN KEY FK_AA6431FEA76ED395');
        $this->addSql('ALTER TABLE purchases DROP FOREIGN KEY FK_AA6431FE5AEA4428');
        $this->addSql('ALTER TABLE purchases DROP FOREIGN KEY FK_AA6431FECDF80196');
        $this->addSql('DROP TABLE certifications');
        $this->addSql('DROP TABLE curricula');
        $this->addSql('DROP TABLE lesson_progress');
        $this->addSql('DROP TABLE lessons');
        $this->addSql('DROP TABLE purchases');
        $this->addSql('DROP TABLE themes');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
