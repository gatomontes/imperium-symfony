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
        $evidenceBearingOlderVersion = (bool) $this->connection->fetchOne(
            'SELECT EXISTS (
                SELECT 1
                FROM mission_authorization older
                JOIN mission_authorization newer
                  ON older.proposal_id = newer.proposal_id
                 AND older.version < newer.version
                JOIN execution_attempt execution
                  ON execution.authorization_id = older.id
            )'
        );
        $this->abortIf(
            $evidenceBearingOlderVersion,
            'Cannot downgrade authorization versioning while an older authorization retains execution evidence.'
        );
        $this->addSql('DELETE FROM mission_authorization older USING mission_authorization newer WHERE older.proposal_id = newer.proposal_id AND older.version < newer.version');
        $this->addSql('DROP INDEX uniq_authorization_proposal_version');
        $this->addSql('ALTER TABLE mission_authorization DROP version');
        $this->addSql('CREATE UNIQUE INDEX uniq_authorization_proposal ON mission_authorization (proposal_id)');
    }
}
