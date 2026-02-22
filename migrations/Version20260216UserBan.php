<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260216UserBan extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add banned_until and ban_reason columns to users table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD banned_until DATETIME DEFAULT NULL, ADD ban_reason VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN banned_until, DROP COLUMN ban_reason');
    }
}

