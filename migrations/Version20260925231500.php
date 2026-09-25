<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Generated from ORM metadata with Doctrine's PostgreSQL platform.
 */
final class Version20260925231500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add explicit resource and external-effect authorization records';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('CREATE TABLE authorization (id VARCHAR(36) NOT NULL, proposal_id VARCHAR(36) NOT NULL, resources JSON NOT NULL, effects JSON NOT NULL, limits JSON NOT NULL, status VARCHAR(32) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, decided_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7A6D8BEFF4792058 ON authorization (proposal_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_authorization_proposal ON authorization (proposal_id)');
        $this->addSql('ALTER TABLE authorization ADD CONSTRAINT FK_7A6D8BEFF4792058 FOREIGN KEY (proposal_id) REFERENCES proposal (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('DROP TABLE authorization');
    }
}
