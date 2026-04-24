<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309081000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add currency code and currency icon fields to country table.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE country ADD currency_code VARCHAR(3) DEFAULT NULL, ADD currency_icon VARCHAR(16) DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_country_currency_code ON country (currency_code)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_country_currency_code ON country');
        $this->addSql('ALTER TABLE country DROP currency_code, DROP currency_icon');
    }
}
