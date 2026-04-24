<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260424151949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make system_user.params non-nullable, backfilling NULL rows with empty JSON array';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE system_user SET params = '[]' WHERE params IS NULL");
        $this->addSql('ALTER TABLE system_user ALTER params SET DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE system_user ALTER params SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE system_user ALTER params DROP DEFAULT');
        $this->addSql('ALTER TABLE system_user ALTER params DROP NOT NULL');
    }
}
