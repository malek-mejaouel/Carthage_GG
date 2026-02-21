<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218RoleVerification extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_verified, verified_role_badge, verification_date to users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users ADD is_verified BOOLEAN DEFAULT 0, ADD verified_role_badge VARCHAR(120) DEFAULT NULL, ADD verification_date DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE users DROP COLUMN is_verified, DROP COLUMN verified_role_badge, DROP COLUMN verification_date');
    }
}

