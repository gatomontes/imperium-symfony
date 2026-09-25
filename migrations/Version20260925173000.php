<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Generated from ORM metadata with Doctrine's PostgreSQL platform.
 */
final class Version20260925173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add versioned proposals linked to authorized interviews';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('CREATE TABLE proposal (id VARCHAR(36) NOT NULL, interview_id VARCHAR(36) NOT NULL, version INT NOT NULL, source_interview_version INT NOT NULL, status VARCHAR(32) NOT NULL, content JSON NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_C7CDC353659D1F46 ON proposal (interview_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_proposal_interview_version ON proposal (interview_id, version)');
        $this->addSql('ALTER TABLE proposal ADD CONSTRAINT FK_C7CDC353659D1F46 FOREIGN KEY (interview_id) REFERENCES interview (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('COMMENT ON COLUMN proposal.content IS \'(DC2Type:json)\'');
        $this->addSql('COMMENT ON COLUMN proposal.created_at IS \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('DROP TABLE proposal');
    }
}
