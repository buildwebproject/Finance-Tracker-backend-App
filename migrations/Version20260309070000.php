<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309070000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wallet, bank_account and finance_transaction tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE wallet (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, name VARCHAR(120) NOT NULL, starting_balance NUMERIC(14, 2) NOT NULL DEFAULT '0.00', current_balance NUMERIC(14, 2) NOT NULL DEFAULT '0.00', color_value VARCHAR(32) DEFAULT NULL, icon_code_point INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_wallet_user_created (user_id, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE wallet ADD CONSTRAINT FK_B9D40DE6A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE bank_account (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, bank_name VARCHAR(120) NOT NULL, nickname VARCHAR(120) DEFAULT NULL, starting_balance NUMERIC(14, 2) NOT NULL DEFAULT '0.00', current_balance NUMERIC(14, 2) NOT NULL DEFAULT '0.00', is_default TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_bank_account_user_default (user_id, is_default), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE bank_account ADD CONSTRAINT FK_D7610A95A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE finance_transaction (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, wallet_id INT DEFAULT NULL, bank_account_id INT DEFAULT NULL, amount NUMERIC(14, 2) NOT NULL, is_income TINYINT(1) NOT NULL, payment_type VARCHAR(10) NOT NULL, category VARCHAR(120) NOT NULL, note VARCHAR(1000) DEFAULT NULL, occurred_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', is_system_generated TINYINT(1) NOT NULL DEFAULT 0, source_type VARCHAR(80) DEFAULT NULL, source_id VARCHAR(120) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_finance_transaction_user_occurred (user_id, occurred_at), INDEX idx_finance_transaction_payment_type (payment_type), INDEX idx_finance_transaction_category (category), INDEX IDX_03E495C8A76ED395 (user_id), INDEX IDX_03E495C8727942D3 (wallet_id), INDEX IDX_03E495C841EB108A (bank_account_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE finance_transaction ADD CONSTRAINT FK_03E495C8A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finance_transaction ADD CONSTRAINT FK_03E495C8727942D3 FOREIGN KEY (wallet_id) REFERENCES wallet (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE finance_transaction ADD CONSTRAINT FK_03E495C841EB108A FOREIGN KEY (bank_account_id) REFERENCES bank_account (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE finance_transaction DROP FOREIGN KEY FK_03E495C8A76ED395');
        $this->addSql('ALTER TABLE finance_transaction DROP FOREIGN KEY FK_03E495C8727942D3');
        $this->addSql('ALTER TABLE finance_transaction DROP FOREIGN KEY FK_03E495C841EB108A');
        $this->addSql('ALTER TABLE wallet DROP FOREIGN KEY FK_B9D40DE6A76ED395');
        $this->addSql('ALTER TABLE bank_account DROP FOREIGN KEY FK_D7610A95A76ED395');

        $this->addSql('DROP TABLE finance_transaction');
        $this->addSql('DROP TABLE wallet');
        $this->addSql('DROP TABLE bank_account');
    }
}

