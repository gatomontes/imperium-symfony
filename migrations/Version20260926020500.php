<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260926020500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Version authorization records and allow explicit replacement requests';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $this->addSql('ALTER TABLE mission_authorization ADD version INT DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE mission_authorization ALTER version DROP DEFAULT');
        $this->addSql('DROP INDEX uniq_authorization_proposal');
        $this->addSql('CREATE UNIQUE INDEX uniq_authorization_proposal_version ON mission_authorization (proposal_id, version)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform, 'This migration requires PostgreSQL.');
        $multipleEvidenceVersions = (int) $this->connection->fetchOne(
            'SELECT COUNT(*)
             FROM (
                 SELECT authorization.proposal_id
                 FROM mission_authorization authorization
                 JOIN execution_attempt execution
                   ON execution.authorization_id = authorization.id
                 GROUP BY authorization.proposal_id
                 HAVING COUNT(*) > 1
             ) evidence_conflicts'
        );
        $this->abortIf(
            $multipleEvidenceVersions > 0,
            'Cannot downgrade authorization versioning when one proposal retains execution evidence on multiple authorization versions.'
        );

        // The old schema can retain only one authorization row per proposal.
        // When exactly one version owns execution evidence, preserve that row and
        // discard only its non-evidence siblings. For proposals without execution
        // evidence, keep the newest version.
        $this->addSql(
            'DELETE FROM mission_authorization sibling
             USING mission_authorization keeper
             WHERE sibling.proposal_id = keeper.proposal_id
               AND sibling.id <> keeper.id
               AND EXISTS (
                   SELECT 1 FROM execution_attempt execution
                   WHERE execution.authorization_id = keeper.id
               )
               AND NOT EXISTS (
                   SELECT 1 FROM execution_attempt execution
                   WHERE execution.authorization_id = sibling.id
               )'
        );
        $this->addSql(
            'DELETE FROM mission_authorization older
             USING mission_authorization newer
             WHERE older.proposal_id = newer.proposal_id
               AND older.version < newer.version
               AND NOT EXISTS (
                   SELECT 1 FROM execution_attempt execution
                   WHERE execution.authorization_id = older.id
               )
               AND NOT EXISTS (
                   SELECT 1 FROM execution_attempt execution
                   WHERE execution.authorization_id = newer.id
               )'
        );
        $this->addSql('DROP INDEX uniq_authorization_proposal_version');
        $this->addSql('ALTER TABLE mission_authorization DROP version');
        $this->addSql('CREATE UNIQUE INDEX uniq_authorization_proposal ON mission_authorization (proposal_id)');
    }
}
