<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260302232655 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE blog (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, category VARCHAR(255) DEFAULT NULL, image_name VARCHAR(255) DEFAULT NULL, comment_count INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart (id INT AUTO_INCREMENT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_BA388B7A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE cart_item (id INT AUTO_INCREMENT NOT NULL, quantity INT DEFAULT NULL, added_at DATETIME DEFAULT NULL, cart_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_F0FE25271AD5CDBF (cart_id), INDEX IDX_F0FE25274584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, blog_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_9474526CDAE07E97 (blog_id), INDEX IDX_9474526CA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, max_members INT NOT NULL, logo VARCHAR(255) DEFAULT NULL, owner_id INT NOT NULL, INDEX IDX_2449BA157E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe_user (equipe_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_84DA47B76D861B89 (equipe_id), INDEX IDX_84DA47B7A76ED395 (user_id), PRIMARY KEY (equipe_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe_tournoi (equipe_id INT NOT NULL, tournoi_id INT NOT NULL, INDEX IDX_C0AFC5636D861B89 (equipe_id), INDEX IDX_C0AFC563F607770A (tournoi_id), PRIMARY KEY (equipe_id, tournoi_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE inscription_tournoi (id INT AUTO_INCREMENT NOT NULL, date_inscription DATETIME NOT NULL, tournoi_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_317E2F33F607770A (tournoi_id), INDEX IDX_317E2F336D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE jeu (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, genre VARCHAR(255) NOT NULL, plateforme VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE join_request (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, equipe_id INT NOT NULL, user_id INT NOT NULL, created_by_id INT DEFAULT NULL, updated_by_id INT DEFAULT NULL, INDEX IDX_E932E4FF6D861B89 (equipe_id), INDEX IDX_E932E4FFA76ED395 (user_id), INDEX IDX_E932E4FFB03A8386 (created_by_id), INDEX IDX_E932E4FF896DBBDE (updated_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE match_game (id INT AUTO_INCREMENT NOT NULL, date_match DATETIME DEFAULT NULL, score_team1 INT DEFAULT NULL, score_team2 INT DEFAULT NULL, statut VARCHAR(50) NOT NULL, equipe1_id INT NOT NULL, equipe2_id INT NOT NULL, tournoi_id INT NOT NULL, INDEX IDX_424480FE4265900C (equipe1_id), INDEX IDX_424480FE50D03FE2 (equipe2_id), INDEX IDX_424480FEF607770A (tournoi_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `order` (id INT AUTO_INCREMENT NOT NULL, order_number VARCHAR(255) DEFAULT NULL, total_price NUMERIC(10, 2) DEFAULT NULL, status VARCHAR(50) DEFAULT NULL, stripe_session_id VARCHAR(255) DEFAULT NULL, stripe_payment_intent_id VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(20) DEFAULT NULL, phone VARCHAR(20) DEFAULT NULL, created_at DATETIME NOT NULL, paid_at DATETIME DEFAULT NULL, user_id INT NOT NULL, INDEX IDX_F5299398A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, product_name VARCHAR(255) DEFAULT NULL, product_price NUMERIC(10, 2) DEFAULT NULL, quantity INT DEFAULT NULL, order_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_52EA1F098D9F6D38 (order_id), INDEX IDX_52EA1F094584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) DEFAULT NULL, stock INT DEFAULT NULL, image VARCHAR(255) DEFAULT NULL, category_id INT NOT NULL, INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_rating (id INT AUTO_INCREMENT NOT NULL, stars INT DEFAULT NULL, comment LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_BAF56786A76ED395 (user_id), INDEX IDX_BAF567864584665A (product_id), UNIQUE INDEX unique_rating (user_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE rating (id INT AUTO_INCREMENT NOT NULL, value INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, blog_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_D8892622DAE07E97 (blog_id), INDEX IDX_D8892622A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE result (id INT AUTO_INCREMENT NOT NULL, m VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stream (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE stream_reaction (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(50) NOT NULL, comment LONGTEXT DEFAULT NULL, username VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, stream_id INT NOT NULL, INDEX IDX_8035CDBAD0ED463E (stream_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE tournoi (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, max_participants INT DEFAULT NULL, cagnotte NUMERIC(10, 2) DEFAULT NULL, date_inscription_limite DATETIME DEFAULT NULL, frais_inscription DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, jeu_id INT DEFAULT NULL, INDEX IDX_18AFD9DF8C9E392E (jeu_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, google2fa_secret VARCHAR(255) DEFAULT NULL, is_2fa_enabled TINYINT NOT NULL, google_oauth_id VARCHAR(255) DEFAULT NULL, oauth_provider VARCHAR(50) DEFAULT NULL, face_encoding LONGTEXT DEFAULT NULL, is_face_enabled TINYINT NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), UNIQUE INDEX UNIQ_8D93D649752251B4 (google_oauth_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE wishlist (id INT AUTO_INCREMENT NOT NULL, added_at DATETIME DEFAULT NULL, user_id INT NOT NULL, product_id INT NOT NULL, INDEX IDX_9CE12A31A76ED395 (user_id), INDEX IDX_9CE12A314584665A (product_id), UNIQUE INDEX unique_wishlist (user_id, product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE cart ADD CONSTRAINT FK_BA388B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25271AD5CDBF FOREIGN KEY (cart_id) REFERENCES cart (id)');
        $this->addSql('ALTER TABLE cart_item ADD CONSTRAINT FK_F0FE25274584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CDAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id)');
        $this->addSql('ALTER TABLE comment ADD CONSTRAINT FK_9474526CA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA157E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE equipe_user ADD CONSTRAINT FK_84DA47B76D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_user ADD CONSTRAINT FK_84DA47B7A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC5636D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE equipe_tournoi ADD CONSTRAINT FK_C0AFC563F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE inscription_tournoi ADD CONSTRAINT FK_317E2F33F607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE inscription_tournoi ADD CONSTRAINT FK_317E2F336D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FF6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FFA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FFB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE join_request ADD CONSTRAINT FK_E932E4FF896DBBDE FOREIGN KEY (updated_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FE4265900C FOREIGN KEY (equipe1_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FE50D03FE2 FOREIGN KEY (equipe2_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_game ADD CONSTRAINT FK_424480FEF607770A FOREIGN KEY (tournoi_id) REFERENCES tournoi (id)');
        $this->addSql('ALTER TABLE `order` ADD CONSTRAINT FK_F5299398A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098D9F6D38 FOREIGN KEY (order_id) REFERENCES `order` (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product_rating ADD CONSTRAINT FK_BAF56786A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_rating ADD CONSTRAINT FK_BAF567864584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622DAE07E97 FOREIGN KEY (blog_id) REFERENCES blog (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE rating ADD CONSTRAINT FK_D8892622A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE stream_reaction ADD CONSTRAINT FK_8035CDBAD0ED463E FOREIGN KEY (stream_id) REFERENCES stream (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tournoi ADD CONSTRAINT FK_18AFD9DF8C9E392E FOREIGN KEY (jeu_id) REFERENCES jeu (id)');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A31A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE wishlist ADD CONSTRAINT FK_9CE12A314584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cart DROP FOREIGN KEY FK_BA388B7A76ED395');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25271AD5CDBF');
        $this->addSql('ALTER TABLE cart_item DROP FOREIGN KEY FK_F0FE25274584665A');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CDAE07E97');
        $this->addSql('ALTER TABLE comment DROP FOREIGN KEY FK_9474526CA76ED395');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA157E3C61F9');
        $this->addSql('ALTER TABLE equipe_user DROP FOREIGN KEY FK_84DA47B76D861B89');
        $this->addSql('ALTER TABLE equipe_user DROP FOREIGN KEY FK_84DA47B7A76ED395');
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC5636D861B89');
        $this->addSql('ALTER TABLE equipe_tournoi DROP FOREIGN KEY FK_C0AFC563F607770A');
        $this->addSql('ALTER TABLE inscription_tournoi DROP FOREIGN KEY FK_317E2F33F607770A');
        $this->addSql('ALTER TABLE inscription_tournoi DROP FOREIGN KEY FK_317E2F336D861B89');
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FF6D861B89');
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FFA76ED395');
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FFB03A8386');
        $this->addSql('ALTER TABLE join_request DROP FOREIGN KEY FK_E932E4FF896DBBDE');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FE4265900C');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FE50D03FE2');
        $this->addSql('ALTER TABLE match_game DROP FOREIGN KEY FK_424480FEF607770A');
        $this->addSql('ALTER TABLE `order` DROP FOREIGN KEY FK_F5299398A76ED395');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098D9F6D38');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product_rating DROP FOREIGN KEY FK_BAF56786A76ED395');
        $this->addSql('ALTER TABLE product_rating DROP FOREIGN KEY FK_BAF567864584665A');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622DAE07E97');
        $this->addSql('ALTER TABLE rating DROP FOREIGN KEY FK_D8892622A76ED395');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE stream_reaction DROP FOREIGN KEY FK_8035CDBAD0ED463E');
        $this->addSql('ALTER TABLE tournoi DROP FOREIGN KEY FK_18AFD9DF8C9E392E');
        $this->addSql('ALTER TABLE wishlist DROP FOREIGN KEY FK_9CE12A31A76ED395');
        $this->addSql('ALTER TABLE wishlist DROP FOREIGN KEY FK_9CE12A314584665A');
        $this->addSql('DROP TABLE blog');
        $this->addSql('DROP TABLE cart');
        $this->addSql('DROP TABLE cart_item');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE comment');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE equipe_user');
        $this->addSql('DROP TABLE equipe_tournoi');
        $this->addSql('DROP TABLE inscription_tournoi');
        $this->addSql('DROP TABLE jeu');
        $this->addSql('DROP TABLE join_request');
        $this->addSql('DROP TABLE match_game');
        $this->addSql('DROP TABLE `order`');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_rating');
        $this->addSql('DROP TABLE rating');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE result');
        $this->addSql('DROP TABLE stream');
        $this->addSql('DROP TABLE stream_reaction');
        $this->addSql('DROP TABLE tournoi');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE wishlist');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
