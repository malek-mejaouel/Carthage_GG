<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260214000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create commentaires table for news comments';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS commentaires (
            commentaire_id INT AUTO_INCREMENT NOT NULL,
            news_id INT NOT NULL,
            contenu LONGTEXT NOT NULL,
            date_commentaire DATETIME NOT NULL,
            gif_url VARCHAR(255) NULL,
            upvotes INT DEFAULT 0,
            downvotes INT DEFAULT 0,
            PRIMARY KEY(commentaire_id),
            FOREIGN KEY(news_id) REFERENCES news(news_id) ON DELETE CASCADE,
            INDEX idx_date (date_commentaire),
            INDEX idx_news (news_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS commentaires');
    }
}
