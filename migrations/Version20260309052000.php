<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260309052000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user security settings and audit log tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE user_security_settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, app_lock_enabled TINYINT(1) NOT NULL DEFAULT 0, biometric_enabled TINYINT(1) NOT NULL DEFAULT 0, mpin_hash VARCHAR(255) DEFAULT NULL, mpin_updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', UNIQUE INDEX uniq_user_security_settings_user_id (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE user_security_settings ADD CONSTRAINT FK_5E52AD88A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');

        $this->addSql("CREATE TABLE user_security_settings_log (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, action VARCHAR(80) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, details JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX idx_user_security_settings_log_user_action_created (user_id, action, created_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE user_security_settings_log ADD CONSTRAINT FK_A66A39F5A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_security_settings DROP FOREIGN KEY FK_5E52AD88A76ED395');
        $this->addSql('ALTER TABLE user_security_settings_log DROP FOREIGN KEY FK_A66A39F5A76ED395');

        $this->addSql('DROP TABLE user_security_settings');
        $this->addSql('DROP TABLE user_security_settings_log');
    }
}
