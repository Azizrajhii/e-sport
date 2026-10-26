<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260618120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add image column to jeu and set images for CS2 and FIFA';
    }

    public function up(Schema $schema): void
    {
        // Add nullable image column
        $this->addSql('ALTER TABLE jeu ADD image VARCHAR(255) DEFAULT NULL');

        // Set images for games whose name contains cs2 or fifa (case-insensitive)
        $this->addSql("UPDATE jeu SET image = 'public/img/games/CS2.png' WHERE LOWER(nom) LIKE '%cs2%'");
        $this->addSql("UPDATE jeu SET image = 'public/img/games/fifa.png' WHERE LOWER(nom) LIKE '%fifa%'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE jeu DROP COLUMN image');
    }
}
