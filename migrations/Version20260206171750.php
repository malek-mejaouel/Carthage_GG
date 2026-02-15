<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206171750 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Non-destructive sync - minimal safe updates for our mapped entities
        
        // Update games table description type
        $this->addSql('ALTER TABLE games CHANGE description description LONGTEXT DEFAULT NULL');
        
        // Update matches score columns to allow NULL
        $this->addSql('ALTER TABLE matches CHANGE score_team_a score_team_a INT DEFAULT NULL, CHANGE score_team_b score_team_b INT DEFAULT NULL');
        
        // Update tournaments location to VARCHAR
        $this->addSql('ALTER TABLE tournaments CHANGE location location VARCHAR(10) DEFAULT NULL');
        
        // Update users table columns
        $this->addSql('ALTER TABLE users CHANGE roles roles LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE updated_at updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // Revert back to original state
        $this->addSql('ALTER TABLE games CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches CHANGE score_team_a score_team_a INT DEFAULT 0, CHANGE score_team_b score_team_b INT DEFAULT 0');
        $this->addSql('ALTER TABLE tournaments CHANGE location location ENUM(\'online\', \'offline\') DEFAULT NULL');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL');
        $this->addSql('ALTER TABLE users CHANGE is_active is_active TINYINT DEFAULT 1');
        $this->addSql('ALTER TABLE users CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE users CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
    }
}
