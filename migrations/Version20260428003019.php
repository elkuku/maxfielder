<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260428003019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maxfield ADD plan_results JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE system_user ALTER params SET DEFAULT \'[]\'');
        $this->addSql('ALTER TABLE system_user ALTER params SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE maxfield DROP plan_results');
        $this->addSql('ALTER TABLE system_user ALTER params DROP DEFAULT');
        $this->addSql('ALTER TABLE system_user ALTER params DROP NOT NULL');
    }
}
