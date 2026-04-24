<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260311110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add date_of_birth, gender, and profile columns to app_user.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD date_of_birth DATE DEFAULT NULL, ADD gender VARCHAR(20) DEFAULT NULL, ADD profile LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP date_of_birth, DROP gender, DROP profile');
    }
}
