<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260512135732 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE users ADD reset_code VARCHAR(6) DEFAULT NULL, ADD reset_code_expires_at DATETIME DEFAULT NULL, CHANGE picture picture VARCHAR(255) DEFAULT NULL, CHANGE credit credit INT DEFAULT 0 NOT NULL, CHANGE status status INT DEFAULT 1 NOT NULL, CHANGE roles roles JSON NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_1483A5E9E7927C74 ON users');
        $this->addSql('ALTER TABLE users DROP reset_code, DROP reset_code_expires_at, CHANGE roles roles VARCHAR(255) DEFAULT NULL, CHANGE picture picture VARCHAR(255) NOT NULL, CHANGE credit credit INT NOT NULL, CHANGE status status INT NOT NULL');
    }
}
