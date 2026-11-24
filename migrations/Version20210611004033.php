<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210611004033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_9c5f65bfe7927c74');
        $this->addSql('ALTER TABLE "system_user" RENAME COLUMN email TO identifier');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9C5F65BF772E836A ON "system_user" (identifier)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP INDEX UNIQ_9C5F65BF772E836A');
        $this->addSql('ALTER TABLE "system_user" RENAME COLUMN identifier TO email');
        $this->addSql('CREATE UNIQUE INDEX uniq_9c5f65bfe7927c74 ON "system_user" (email)');
    }
}
