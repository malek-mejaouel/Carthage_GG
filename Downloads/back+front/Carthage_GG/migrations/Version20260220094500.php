<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260220094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure products table has all columns used by the Product entity (adds missing columns safely)';
    }

    public function up(Schema $schema): void
    {
        // Add missing columns if they do not exist
        $this->addSql("ALTER TABLE products
            ADD COLUMN IF NOT EXISTS slug VARCHAR(180) NULL,
            ADD COLUMN IF NOT EXISTS discount DECIMAL(5,2) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS stock INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS sku VARCHAR(50) NULL,
            ADD COLUMN IF NOT EXISTS image VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS average_rating DECIMAL(2,1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS sales_count INT DEFAULT 0,
            ADD COLUMN IF NOT EXISTS is_featured TINYINT(1) DEFAULT 0,
            ADD COLUMN IF NOT EXISTS status VARCHAR(10) DEFAULT 'active',
            ADD COLUMN IF NOT EXISTS category_id INT NULL
        ");
    }

    public function down(Schema $schema): void
    {
        // No-op: do not drop columns to avoid data loss
    }
}
