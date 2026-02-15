<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206180011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_1`');
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY `cart_ibfk_2`');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY `commentaires_ibfk_1`');
        $this->addSql('ALTER TABLE commentaires DROP FOREIGN KEY `commentaires_ibfk_2`');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `events_ibfk_1`');
        $this->addSql('ALTER TABLE events DROP FOREIGN KEY `events_ibfk_2`');
        $this->addSql('ALTER TABLE face_authentication DROP FOREIGN KEY `face_authentication_ibfk_1`');
        $this->addSql('ALTER TABLE orders DROP FOREIGN KEY `orders_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_1`');
        $this->addSql('ALTER TABLE order_items DROP FOREIGN KEY `order_items_ibfk_2`');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY `products_ibfk_1`');
        $this->addSql('ALTER TABLE products DROP FOREIGN KEY `products_ibfk_2`');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY `reservations_ibfk_1`');
        $this->addSql('ALTER TABLE reservations DROP FOREIGN KEY `reservations_ibfk_2`');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY `reviews_ibfk_1`');
        $this->addSql('ALTER TABLE reviews DROP FOREIGN KEY `reviews_ibfk_2`');
        $this->addSql('ALTER TABLE stream_comments DROP FOREIGN KEY `stream_comments_ibfk_1`');
        $this->addSql('ALTER TABLE stream_comments DROP FOREIGN KEY `stream_comments_ibfk_2`');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE categories');
        $this->addSql('DROP TABLE commentaires');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE face_authentication');
        $this->addSql('DROP TABLE live_streams');
        $this->addSql('DROP TABLE locations');
        $this->addSql('DROP TABLE news');
        $this->addSql('DROP TABLE orders');
        $this->addSql('DROP TABLE order_items');
        $this->addSql('DROP TABLE products');
        $this->addSql('DROP TABLE reservations');
        $this->addSql('DROP TABLE reviews');
        $this->addSql('DROP TABLE spectators');
        $this->addSql('DROP TABLE stream_comments');
        $this->addSql('ALTER TABLE games CHANGE description description LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_1`');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_2`');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_3`');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_1`');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_2`');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY `match_players_ibfk_3`');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC92ABEACD6 FOREIGN KEY (match_id) REFERENCES matches (match_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9296CD8AE FOREIGN KEY (team_id) REFERENCES teams (team_id)');
        $this->addSql('DROP INDEX match_id ON match_players');
        $this->addSql('CREATE INDEX IDX_51E81CC92ABEACD6 ON match_players (match_id)');
        $this->addSql('DROP INDEX user_id ON match_players');
        $this->addSql('CREATE INDEX IDX_51E81CC9A76ED395 ON match_players (user_id)');
        $this->addSql('DROP INDEX team_id ON match_players');
        $this->addSql('CREATE INDEX IDX_51E81CC9296CD8AE ON match_players (team_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_1` FOREIGN KEY (match_id) REFERENCES matches (match_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_3` FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `matches_ibfk_1`');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `matches_ibfk_2`');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `matches_ibfk_3`');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY `matches_ibfk_4`');
        $this->addSql('ALTER TABLE matches CHANGE score_team_a score_team_a INT DEFAULT NULL, CHANGE score_team_b score_team_b INT DEFAULT NULL');
        $this->addSql('DROP INDEX tournament_id ON matches');
        $this->addSql('CREATE INDEX IDX_62615BA33D1A3E7 ON matches (tournament_id)');
        $this->addSql('DROP INDEX game_id ON matches');
        $this->addSql('CREATE INDEX IDX_62615BAE48FD905 ON matches (game_id)');
        $this->addSql('DROP INDEX team_a_id ON matches');
        $this->addSql('CREATE INDEX IDX_62615BAEA3FA723 ON matches (team_a_id)');
        $this->addSql('DROP INDEX team_b_id ON matches');
        $this->addSql('CREATE INDEX IDX_62615BAF88A08CD ON matches (team_b_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `matches_ibfk_1` FOREIGN KEY (tournament_id) REFERENCES tournaments (tournament_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `matches_ibfk_2` FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `matches_ibfk_3` FOREIGN KEY (team_a_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT `matches_ibfk_4` FOREIGN KEY (team_b_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY `team_players_ibfk_1`');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY `team_players_ibfk_2`');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY `team_players_ibfk_1`');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY `team_players_ibfk_2`');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT FK_D9373291296CD8AE FOREIGN KEY (team_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT FK_D9373291A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('DROP INDEX team_id ON team_players');
        $this->addSql('CREATE INDEX IDX_D9373291296CD8AE ON team_players (team_id)');
        $this->addSql('DROP INDEX user_id ON team_players');
        $this->addSql('CREATE INDEX IDX_D9373291A76ED395 ON team_players (user_id)');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT `team_players_ibfk_1` FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT `team_players_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE teams DROP FOREIGN KEY `teams_ibfk_1`');
        $this->addSql('DROP INDEX user_id ON teams');
        $this->addSql('CREATE INDEX IDX_96C22258A76ED395 ON teams (user_id)');
        $this->addSql('ALTER TABLE teams ADD CONSTRAINT `teams_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE tournaments DROP FOREIGN KEY `tournaments_ibfk_1`');
        $this->addSql('ALTER TABLE tournaments DROP FOREIGN KEY `tournaments_ibfk_2`');
        $this->addSql('ALTER TABLE tournaments CHANGE location location VARCHAR(10) DEFAULT NULL');
        $this->addSql('DROP INDEX game_id ON tournaments');
        $this->addSql('CREATE INDEX IDX_E4BCFAC3E48FD905 ON tournaments (game_id)');
        $this->addSql('DROP INDEX user_id ON tournaments');
        $this->addSql('CREATE INDEX IDX_E4BCFAC3A76ED395 ON tournaments (user_id)');
        $this->addSql('ALTER TABLE tournaments ADD CONSTRAINT `tournaments_ibfk_1` FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('ALTER TABLE tournaments ADD CONSTRAINT `tournaments_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE users CHANGE roles roles LONGTEXT NOT NULL, CHANGE is_active is_active TINYINT DEFAULT 1 NOT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX email ON users');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE cart (cart_id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, product_id INT DEFAULT NULL, quantity INT DEFAULT NULL, INDEX product_id (product_id), INDEX user_id (user_id), PRIMARY KEY (cart_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE categories (category_id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE commentaires (commentaire_id INT AUTO_INCREMENT NOT NULL, contenu TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP, gif_url VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, upvotes INT DEFAULT 0, downvotes INT DEFAULT 0, news_id INT DEFAULT NULL, user_id INT DEFAULT NULL, INDEX news_id (news_id), INDEX user_id (user_id), PRIMARY KEY (commentaire_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE events (event_id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, game VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, event_date DATE DEFAULT NULL, start_time TIME DEFAULT NULL, end_time TIME DEFAULT NULL, location_id INT DEFAULT NULL, max_participants INT DEFAULT NULL, organizer_id INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX organizer_id (organizer_id), INDEX location_id (location_id), PRIMARY KEY (event_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE face_authentication (id INT AUTO_INCREMENT NOT NULL, descriptor LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, enabled TINYINT DEFAULT 1, registered_at DATETIME DEFAULT CURRENT_TIMESTAMP, user_id INT DEFAULT NULL, UNIQUE INDEX user_id (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE live_streams (stream_id INT AUTO_INCREMENT NOT NULL, title VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, game VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, streamer_name VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, is_live TINYINT DEFAULT 0, start_time DATETIME DEFAULT NULL, end_time DATETIME DEFAULT NULL, PRIMARY KEY (stream_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE locations (location_id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, address VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, city VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, capacity INT DEFAULT NULL, equipments TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, price_per_day NUMERIC(10, 2) DEFAULT NULL, availability TINYINT DEFAULT 1, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (location_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE news (news_id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, contenu TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, image VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, categorie VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, date_publication DATETIME DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (news_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE orders (order_id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, payment_method VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX user_id (user_id), PRIMARY KEY (order_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE order_items (order_item_id INT AUTO_INCREMENT NOT NULL, order_id INT DEFAULT NULL, product_id INT DEFAULT NULL, quantity INT DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, INDEX order_id (order_id), INDEX product_id (product_id), PRIMARY KEY (order_item_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE products (product_id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, description TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, price NUMERIC(10, 2) DEFAULT NULL, discount NUMERIC(5, 2) DEFAULT \'0.00\', stock INT DEFAULT 0, sku VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, category_id INT DEFAULT NULL, game_id INT DEFAULT NULL, release_date DATE DEFAULT NULL, is_featured TINYINT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, UNIQUE INDEX sku (sku), INDEX category_id (category_id), INDEX game_id (game_id), PRIMARY KEY (product_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reservations (reservation_id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, event_id INT DEFAULT NULL, reservation_date DATETIME DEFAULT CURRENT_TIMESTAMP, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, INDEX user_id (user_id), INDEX event_id (event_id), PRIMARY KEY (reservation_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE reviews (review_id INT AUTO_INCREMENT NOT NULL, product_id INT DEFAULT NULL, user_id INT DEFAULT NULL, rating INT DEFAULT NULL, comment TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX product_id (product_id), INDEX user_id (user_id), PRIMARY KEY (review_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE spectators (spectator_id INT AUTO_INCREMENT NOT NULL, username VARCHAR(100) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(150) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, is_guest TINYINT DEFAULT 1, joined_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (spectator_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE stream_comments (comment_id INT AUTO_INCREMENT NOT NULL, stream_id INT DEFAULT NULL, spectator_id INT DEFAULT NULL, message TEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, sent_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, INDEX stream_id (stream_id), INDEX spectator_id (spectator_id), PRIMARY KEY (comment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT `cart_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (product_id)');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT `commentaires_ibfk_1` FOREIGN KEY (news_id) REFERENCES news (news_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE commentaires ADD CONSTRAINT `commentaires_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `events_ibfk_1` FOREIGN KEY (location_id) REFERENCES locations (location_id)');
        $this->addSql('ALTER TABLE events ADD CONSTRAINT `events_ibfk_2` FOREIGN KEY (organizer_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE face_authentication ADD CONSTRAINT `face_authentication_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE orders ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (order_id) REFERENCES orders (order_id)');
        $this->addSql('ALTER TABLE order_items ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (product_id) REFERENCES products (product_id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (category_id) REFERENCES categories (category_id)');
        $this->addSql('ALTER TABLE products ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE reservations ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (event_id) REFERENCES events (event_id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (product_id) REFERENCES products (product_id)');
        $this->addSql('ALTER TABLE reviews ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE stream_comments ADD CONSTRAINT `stream_comments_ibfk_1` FOREIGN KEY (stream_id) REFERENCES live_streams (stream_id)');
        $this->addSql('ALTER TABLE stream_comments ADD CONSTRAINT `stream_comments_ibfk_2` FOREIGN KEY (spectator_id) REFERENCES spectators (spectator_id)');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE games CHANGE description description TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BA33D1A3E7');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAE48FD905');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAEA3FA723');
        $this->addSql('ALTER TABLE matches DROP FOREIGN KEY FK_62615BAF88A08CD');
        $this->addSql('ALTER TABLE matches CHANGE score_team_a score_team_a INT DEFAULT 0, CHANGE score_team_b score_team_b INT DEFAULT 0');
        $this->addSql('DROP INDEX idx_62615baea3fa723 ON matches');
        $this->addSql('CREATE INDEX team_a_id ON matches (team_a_id)');
        $this->addSql('DROP INDEX idx_62615baf88a08cd ON matches');
        $this->addSql('CREATE INDEX team_b_id ON matches (team_b_id)');
        $this->addSql('DROP INDEX idx_62615ba33d1a3e7 ON matches');
        $this->addSql('CREATE INDEX tournament_id ON matches (tournament_id)');
        $this->addSql('DROP INDEX idx_62615bae48fd905 ON matches');
        $this->addSql('CREATE INDEX game_id ON matches (game_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BA33D1A3E7 FOREIGN KEY (tournament_id) REFERENCES tournaments (tournament_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAE48FD905 FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAEA3FA723 FOREIGN KEY (team_a_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE matches ADD CONSTRAINT FK_62615BAF88A08CD FOREIGN KEY (team_b_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC92ABEACD6');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9A76ED395');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9296CD8AE');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC92ABEACD6');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9A76ED395');
        $this->addSql('ALTER TABLE match_players DROP FOREIGN KEY FK_51E81CC9296CD8AE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_1` FOREIGN KEY (match_id) REFERENCES matches (match_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT `match_players_ibfk_3` FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_51e81cc92abeacd6 ON match_players');
        $this->addSql('CREATE INDEX match_id ON match_players (match_id)');
        $this->addSql('DROP INDEX idx_51e81cc9a76ed395 ON match_players');
        $this->addSql('CREATE INDEX user_id ON match_players (user_id)');
        $this->addSql('DROP INDEX idx_51e81cc9296cd8ae ON match_players');
        $this->addSql('CREATE INDEX team_id ON match_players (team_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC92ABEACD6 FOREIGN KEY (match_id) REFERENCES matches (match_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE match_players ADD CONSTRAINT FK_51E81CC9296CD8AE FOREIGN KEY (team_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE teams DROP FOREIGN KEY FK_96C22258A76ED395');
        $this->addSql('DROP INDEX idx_96c22258a76ed395 ON teams');
        $this->addSql('CREATE INDEX user_id ON teams (user_id)');
        $this->addSql('ALTER TABLE teams ADD CONSTRAINT FK_96C22258A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY FK_D9373291296CD8AE');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY FK_D9373291A76ED395');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY FK_D9373291296CD8AE');
        $this->addSql('ALTER TABLE team_players DROP FOREIGN KEY FK_D9373291A76ED395');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT `team_players_ibfk_1` FOREIGN KEY (team_id) REFERENCES teams (team_id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT `team_players_ibfk_2` FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE');
        $this->addSql('DROP INDEX idx_d9373291296cd8ae ON team_players');
        $this->addSql('CREATE INDEX team_id ON team_players (team_id)');
        $this->addSql('DROP INDEX idx_d9373291a76ed395 ON team_players');
        $this->addSql('CREATE INDEX user_id ON team_players (user_id)');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT FK_D9373291296CD8AE FOREIGN KEY (team_id) REFERENCES teams (team_id)');
        $this->addSql('ALTER TABLE team_players ADD CONSTRAINT FK_D9373291A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE tournaments DROP FOREIGN KEY FK_E4BCFAC3E48FD905');
        $this->addSql('ALTER TABLE tournaments DROP FOREIGN KEY FK_E4BCFAC3A76ED395');
        $this->addSql('ALTER TABLE tournaments CHANGE location location ENUM(\'online\', \'offline\') DEFAULT NULL');
        $this->addSql('DROP INDEX idx_e4bcfac3e48fd905 ON tournaments');
        $this->addSql('CREATE INDEX game_id ON tournaments (game_id)');
        $this->addSql('DROP INDEX idx_e4bcfac3a76ed395 ON tournaments');
        $this->addSql('CREATE INDEX user_id ON tournaments (user_id)');
        $this->addSql('ALTER TABLE tournaments ADD CONSTRAINT FK_E4BCFAC3E48FD905 FOREIGN KEY (game_id) REFERENCES games (game_id)');
        $this->addSql('ALTER TABLE tournaments ADD CONSTRAINT FK_E4BCFAC3A76ED395 FOREIGN KEY (user_id) REFERENCES users (user_id)');
        $this->addSql('ALTER TABLE users CHANGE roles roles JSON NOT NULL, CHANGE is_active is_active TINYINT DEFAULT 1, CHANGE created_at created_at DATETIME DEFAULT CURRENT_TIMESTAMP, CHANGE updated_at updated_at DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('DROP INDEX uniq_1483a5e9e7927c74 ON users');
        $this->addSql('CREATE UNIQUE INDEX email ON users (email)');
    }
}
