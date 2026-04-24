<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for fast country/category list queries.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_country_active_name ON country (is_active, name)');
        $this->addSql('CREATE INDEX idx_finance_category_active_name ON finance_category (is_active, name)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_country_active_name ON country');
        $this->addSql('DROP INDEX idx_finance_category_active_name ON finance_category');
    }
}
