<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309084000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create finance_category table and link finance_transaction to it.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE finance_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, icon_name VARCHAR(255) DEFAULT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_finance_category_name (name), INDEX idx_finance_category_is_active (is_active), INDEX idx_finance_category_created (created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE finance_transaction ADD finance_category_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_finance_transaction_finance_category ON finance_transaction (finance_category_id)');
        $this->addSql('ALTER TABLE finance_transaction ADD CONSTRAINT FK_03E495C8B6E1A0A FOREIGN KEY (finance_category_id) REFERENCES finance_category (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE finance_transaction DROP FOREIGN KEY FK_03E495C8B6E1A0A');
        $this->addSql('DROP INDEX idx_finance_transaction_finance_category ON finance_transaction');
        $this->addSql('ALTER TABLE finance_transaction DROP finance_category_id');
        $this->addSql('DROP TABLE finance_category');
    }
}
