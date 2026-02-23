<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218145018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY `FK_commentaires_user`');
        $this->addSql('DROP INDEX idx_commentaires_user_id ON commentaires');
        $this->addSql('CREATE INDEX IDX_D9BEC0C4A76ED395 ON commentaires (user_id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT `FK_commentaires_user` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE face_authentication DROP INDEX IDX_FACE_USER, ADD UNIQUE INDEX UNIQ_B2B8ED7FA76ED395 (user_id)');
        $this->addSql('ALTER TABLE face_authentication CHANGE descriptor descriptor LONGTEXT DEFAULT NULL, CHANGE enabled enabled TINYINT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `FK_62615BAE48FD905`');
        $this->addSql('DROP INDEX IDX_62615BAE48FD905 ON matches');
        $this->addSql('ALTER TABLE matches DROP game_id');
        $this->addSql('ALTER TABLE users ADD receive_news_emails TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4A76ED395');
        $this->addSql('DROP INDEX idx_d9bec0c4a76ed395 ON commentaires');
        $this->addSql('CREATE INDEX IDX_commentaires_user_id ON commentaires (user_id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE face_authentication DROP INDEX UNIQ_B2B8ED7FA76ED395, ADD INDEX IDX_FACE_USER (user_id)');
        $this->addSql('ALTER TABLE face_authentication CHANGE descriptor descriptor LONGTEXT NOT NULL, CHANGE enabled enabled TINYINT DEFAULT 1 NOT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `FK_62615BAE48FD905` FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('CREATE INDEX IDX_62615BAE48FD905 ON matches (game_id)');
        $this->addSql('ALTER TABLE users DROP receive_news_emails');
    }
}
