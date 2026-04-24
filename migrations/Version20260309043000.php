<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309043000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create country table for Sonata country management.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, iso2_code VARCHAR(2) NOT NULL, iso3_code VARCHAR(3) DEFAULT NULL, dial_code VARCHAR(10) NOT NULL, flag_emoji VARCHAR(10) DEFAULT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_country_name (name), INDEX idx_country_dial_code (dial_code), INDEX idx_country_is_active (is_active), UNIQUE INDEX uniq_country_iso2_code (iso2_code), UNIQUE INDEX uniq_country_iso3_code (iso3_code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE country');
    }
}
