<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for creating the News entity table
 */
final class Version20260214000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create news table';
    }

    public function up(Schema $schema): void
    {
        // Create news table only if it doesn't exist
        $this->addSql('CREATE TABLE IF NOT EXISTS news (
            news_id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            contenu LONGTEXT NOT NULL,
            image VARCHAR(255) NOT NULL,
            categorie VARCHAR(100) NOT NULL,
            date_publication DATETIME NOT NULL,
            PRIMARY KEY(news_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Create index on publication date for sorting
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_news_date ON news(date_publication)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE news');
    }
}
