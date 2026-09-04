<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\MissionDossier;
use App\Imperium\Runtime\Mission\MissionState;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionThreadBatch1Test extends TestCase
{
    public function testDossierIsDeterministicAndAuthorityMutationChangesIdentity(): void
    {
        $input = $this->valid();
        $first = MissionDossier::fromArray($input);
        self::assertSame($first->canonicalJson(), MissionDossier::fromArray($input)->canonicalJson());
        $input['expires_at']--;
        self::assertNotSame($first->identity(), MissionDossier::fromArray($input)->identity());
    }

    public function testMalformedIncompleteExpiredAndOverbroadMissionsFailClosed(): void
    {
        $incomplete = $this->valid(); unset($incomplete['mission_id']);
        $this->invalid('MIS101_DOSSIER_FIELDS_INVALID', $incomplete);
        $malformed = $this->valid(); $malformed['target_snapshot'] = 'main';
        $this->invalid('MIS102_DOSSIER_VALUE_INVALID', $malformed);
        $overbroad = $this->valid(); $overbroad['permitted_acts'][0]['action'] = 'modify-target';
        $this->invalid('MIS104_DOSSIER_OVERBROAD', $overbroad);
        $mission = MissionDossier::fromArray($this->valid());
        try { $mission->validateAt(1_700_000_060); self::fail('Expected expiry'); }
        catch (\RuntimeException $error) { self::assertSame('MIS106_MISSION_EXPIRED', $error->getMessage()); }
    }

    public function testLifecycleVocabularyHasFourTerminalDispositions(): void
    {
        self::assertFalse(MissionState::EVIDENCE_ASSEMBLED->terminal());
        foreach ([MissionState::COMPLETED, MissionState::REFUSED, MissionState::ABORTED, MissionState::EXPIRED] as $state) {
            self::assertTrue($state->terminal());
        }
    }

    private function valid(): array
    {
        return [
            'schema' => MissionDossier::SCHEMA,
            'mission_id' => 'mission-canonical-reference-0001',
            'mission_kind' => 'repository-authority-inspection',
            'mission_version' => 1,
            'operator_identity' => 'local-test-operator',
            'target_snapshot' => str_repeat('a', 40),
            'requested_acts' => ['authorize', 'admit', 'inspect', 'assemble-evidence', 'complete'],
            'permitted_acts' => [['action' => 'inspect', 'actor' => 'repository-inspector', 'target' => str_repeat('a', 40)]],
            'prohibited_acts' => ['modify-target', 'network-access', 'publish-remote'],
            'success_criteria' => ['bounded-findings-produced'],
            'evidence_requirements' => ['source-location', 'violated-doctrine', 'supporting-evidence', 'recommended-disposition'],
            'time_budget_seconds' => 60,
            'resource_budget' => ['max_files' => 10, 'max_findings' => 10],
            'issued_at' => 1_700_000_000,
            'expires_at' => 1_700_000_060,
            'terminal_disposition_rules' => ['success' => 'COMPLETED', 'invalid' => 'REFUSED'],
            'authorization_provenance' => ['source' => 'operator-mission-order', 'grant_id' => 'operator-grant-0001'],
        ];
    }

    private function invalid(string $message, array $input): void
    {
        try { MissionDossier::fromArray($input); self::fail('Expected '.$message); }
        catch (\InvalidArgumentException $error) { self::assertSame($message, $error->getMessage()); }
    }
}

