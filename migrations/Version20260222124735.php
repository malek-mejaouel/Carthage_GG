<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260222124735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_1`');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_2`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_2`');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('ALTER TABLE categories CHANGE description description LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY `FK_commentaires_user`');
        $this->addSql('DROP INDEX idx_commentaires_user_id ON commentaires');
        $this->addSql('CREATE INDEX IDX_D9BEC0C4A76ED395 ON commentaires (user_id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT `FK_commentaires_user` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE face_authentication DROP INDEX IDX_FACE_USER, ADD UNIQUE INDEX UNIQ_B2B8ED7FA76ED395 (user_id)');
        $this->addSql('ALTER TABLE face_authentication CHANGE descriptor descriptor LONGTEXT DEFAULT NULL, CHANGE enabled enabled TINYINT NOT NULL, CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `FK_62615BAE48FD905`');
        $this->addSql('DROP INDEX IDX_62615BAE48FD905 ON matches');
        $this->addSql('ALTER TABLE matches DROP game_id');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY `orders_ibfk_1`');
        $this->addSql('ALTER TABLE orders MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE orders ADD currency VARCHAR(3) NOT NULL, ADD amount DOUBLE PRECISION NOT NULL, ADD items JSON NOT NULL, ADD shipping_method VARCHAR(20) NOT NULL, ADD shipping DOUBLE PRECISION NOT NULL, ADD tax DOUBLE PRECISION NOT NULL, ADD stripe_session_id VARCHAR(100) DEFAULT NULL, ADD stripe_payment_intent_id VARCHAR(100) DEFAULT NULL, DROP total_price, DROP payment_method, DROP shipping_address, DROP city, DROP postal_code, DROP country, CHANGE status status VARCHAR(50) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE id order_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (order_id)');
        $this->addSql('DROP INDEX user_id ON orders');
        $this->addSql('CREATE INDEX IDX_E52FFDEEA76ED395 ON orders (user_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY `products_ibfk_1`');
        $this->addSql('ALTER TABLE products CHANGE description description LONGTEXT DEFAULT NULL, CHANGE discount discount NUMERIC(5, 2) NOT NULL, CHANGE stock stock INT NOT NULL, CHANGE average_rating average_rating NUMERIC(2, 1) NOT NULL, CHANGE is_featured is_featured TINYINT NOT NULL, CHANGE status status VARCHAR(10) NOT NULL, CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME NOT NULL, CHANGE slug slug VARCHAR(180) NOT NULL, CHANGE sales_count sales_count INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5A989D9B62 ON products (slug)');
        $this->addSql('DROP INDEX sku ON products');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B3BA5A5AF9038C4 ON products (sku)');
        $this->addSql('DROP INDEX category_id ON products');
        $this->addSql('CREATE INDEX IDX_B3BA5A5A12469DE2 ON products (category_id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY `reviews_ibfk_1`');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY `reviews_ibfk_2`');
        $this->addSql('ALTER TABLE reviews CHANGE comment comment LONGTEXT DEFAULT NULL, CHANGE created_at created_at DATETIME NOT NULL');
        $this->addSql('DROP INDEX product_id ON reviews');
        $this->addSql('CREATE INDEX IDX_6970EB0F4584665A ON reviews (product_id)');
        $this->addSql('DROP INDEX user_id ON reviews');
        $this->addSql('CREATE INDEX IDX_6970EB0FA76ED395 ON reviews (user_id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, quantity INT DEFAULT 1, added_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX user_id (user_id, product_id), INDEX product_id (product_id), INDEX IDX_BA388B7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE order_items (id INT AUTO_INCREMENT NOT NULL, order_id INT NOT NULL, product_id INT NOT NULL, quantity INT NOT NULL, price NUMERIC(10, 2) NOT NULL, INDEX order_id (order_id), INDEX product_id (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (order_id) REFERENCES orders (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE categories CHANGE description description TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY FK_D9BEC0C4A76ED395');
        $this->addSql('DROP INDEX idx_d9bec0c4a76ed395 ON commentaires');
        $this->addSql('CREATE INDEX IDX_commentaires_user_id ON commentaires (user_id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT FK_D9BEC0C4A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE face_authentication DROP INDEX UNIQ_B2B8ED7FA76ED395, ADD INDEX IDX_FACE_USER (user_id)');
        $this->addSql('ALTER TABLE face_authentication CHANGE descriptor descriptor LONGTEXT NOT NULL, CHANGE enabled enabled TINYINT DEFAULT 1 NOT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD game_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `FK_62615BAE48FD905` FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('CREATE INDEX IDX_62615BAE48FD905 ON matches (game_id)');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY FK_E52FFDEEA76ED395');
        $this->addSql('ALTER TABLE orders MODIFY order_id INT NOT NULL');
        $this->addSql('ALTER TABLE orders ADD total_price NUMERIC(10, 2) NOT NULL, ADD payment_method ENUM(\'card\', \'paypal\', \'cash_on_delivery\') DEFAULT NULL, ADD shipping_address VARCHAR(255) DEFAULT NULL, ADD city VARCHAR(100) DEFAULT NULL, ADD postal_code VARCHAR(20) DEFAULT NULL, ADD country VARCHAR(100) DEFAULT NULL, DROP currency, DROP amount, DROP items, DROP shipping_method, DROP shipping, DROP tax, DROP stripe_session_id, DROP stripe_payment_intent_id, CHANGE status status ENUM(\'pending\', \'paid\', \'shipped\', \'completed\', \'cancelled\') DEFAULT \'pending\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE order_id id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('DROP INDEX idx_e52ffdeea76ed395 ON orders');
        $this->addSql('CREATE INDEX user_id ON orders (user_id)');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT FK_E52FFDEEA76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX UNIQ_B3BA5A5A989D9B62 ON products');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY FK_B3BA5A5A12469DE2');
        $this->addSql('ALTER TABLE products CHANGE slug slug VARCHAR(180) DEFAULT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE discount discount NUMERIC(5, 2) DEFAULT \'0.00\', CHANGE stock stock INT DEFAULT 0, CHANGE average_rating average_rating NUMERIC(2, 1) DEFAULT \'0.0\', CHANGE sales_count sales_count INT DEFAULT 0, CHANGE is_featured is_featured TINYINT DEFAULT 0, CHANGE status status ENUM(\'active\', \'inactive\') DEFAULT \'active\', CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX uniq_b3ba5a5af9038c4 ON products');
        $this->addSql('CREATE UNIQUE INDEX sku ON products (sku)');
        $this->addSql('DROP INDEX idx_b3ba5a5a12469de2 ON products');
        $this->addSql('CREATE INDEX category_id ON products (category_id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT FK_B3BA5A5A12469DE2 FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0F4584665A');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY FK_6970EB0FA76ED395');
        $this->addSql('ALTER TABLE reviews CHANGE comment comment TEXT DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('DROP INDEX idx_6970eb0f4584665a ON reviews');
        $this->addSql('CREATE INDEX product_id ON reviews (product_id)');
        $this->addSql('DROP INDEX idx_6970eb0fa76ed395 ON reviews');
        $this->addSql('CREATE INDEX user_id ON reviews (user_id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0F4584665A FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT FK_6970EB0FA76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
    }
}
