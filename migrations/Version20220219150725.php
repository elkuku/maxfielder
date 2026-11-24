<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220219150725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "system_user" ALTER git_hub_id TYPE INT USING (git_hub_id::integer)');
        $this->addSql('ALTER TABLE "system_user" ALTER git_hub_id DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE "system_user" ALTER git_hub_id TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE "system_user" ALTER git_hub_id DROP DEFAULT');
    }
}
