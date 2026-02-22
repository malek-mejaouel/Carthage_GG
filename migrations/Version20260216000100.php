<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add user_id nullable foreign key to commentaires referencing users.user_id';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('commentaires');
        if (!$table->hasColumn('user_id')) {
            $this->addSql('ALTER TABLE commentaires ADD user_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_commentaires_user FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL');
            $this->addSql('CREATE INDEX IDX_commentaires_user_id ON commentaires (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_commentaires_user');
        $this->addSql('DROP INDEX IDX_commentaires_user_id ON commentaires');
        $this->addSql('ALTER TABLE commentaires DROP COLUMN user_id');
    }
}
