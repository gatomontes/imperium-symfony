<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Generated from ORM metadata with Doctrine's PostgreSQL platform.
 */
final class Version20260926003000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add persisted evidence for the first bounded execution attempt';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('CREATE TABLE execution_attempt (id VARCHAR(36) NOT NULL, authorization_id VARCHAR(36) NOT NULL, operation VARCHAR(64) NOT NULL, target_path VARCHAR(255) NOT NULL, content_sha256 VARCHAR(64) NOT NULL, status VARCHAR(32) NOT NULL, bytes_written INT DEFAULT NULL, failure_code VARCHAR(64) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX uniq_execution_authorization ON execution_attempt (authorization_id)');
        $this->addSql('ALTER TABLE execution_attempt ADD CONSTRAINT FK_6893A7F941754027 FOREIGN KEY (authorization_id) REFERENCES mission_authorization (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('DROP TABLE execution_attempt');
    }
}
