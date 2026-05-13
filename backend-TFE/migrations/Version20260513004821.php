<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260513004821 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anouncement ADD reserved_for_user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE anouncement ADD CONSTRAINT FK_7DE47A3EFF1D5A96 FOREIGN KEY (reserved_for_user_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_7DE47A3EFF1D5A96 ON anouncement (reserved_for_user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE anouncement DROP FOREIGN KEY FK_7DE47A3EFF1D5A96');
        $this->addSql('DROP INDEX IDX_7DE47A3EFF1D5A96 ON anouncement');
        $this->addSql('ALTER TABLE anouncement DROP reserved_for_user_id');
    }
}
