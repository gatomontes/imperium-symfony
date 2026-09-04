<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Mission\AcceptedMission;
use App\Imperium\Runtime\Mission\CanonicalRepositoryInspectionMission;
use App\Imperium\Runtime\Mission\MissionDossier;
use App\Imperium\Runtime\Mission\OperatorMissionBoundary;
use PHPUnit\Framework\TestCase;

final class CanonicalMissionThreadBatch4And5Test extends TestCase
{
    private const string TARGET = '2527b33925bf3ef47d029786e60a6aefe752737b';

    public function testReferenceMissionReachesTruthfulTerminalReceiptWithoutTargetMutation(): void
    {
        $snapshot = $this->snapshot();
        $before = serialize($snapshot);
        $accepted = $this->accepted();
        $runner = new CanonicalRepositoryInspectionMission($this->root());
        $receipt = $runner->run($accepted, $snapshot, 100);

        self::assertSame('COMPLETED', $receipt['final_state']);
        self::assertSame($accepted->dossier->missionId(), $receipt['mission_id']);
        self::assertCount(5, $receipt['transition_history']);
        self::assertCount(5, $receipt['consumed_capabilities']);
        self::assertCount(1, $receipt['evidence_references']);
        self::assertSame($before, serialize($snapshot));
        foreach ($receipt['transition_history'] as $transition) { self::assertSame($receipt['mission_id'], $transition['mission_id']); }
        foreach ($receipt['consumed_capabilities'] as $consumption) { self::assertSame($receipt['mission_id'], $consumption['mission_id']); }

        $status = $runner->status($receipt['mission_id']);
        self::assertSame('COMPLETED', $status['state']);
        self::assertSame([], $status['remaining_acts']);
        self::assertNull($status['current_authorized_act']);
        self::assertSame($receipt['receipt_id'], $status['terminal_receipt_id']);
        try { $runner->run($accepted, $snapshot, 100); self::fail('Expected terminal rejection'); }
        catch (\RuntimeException $error) { self::assertSame('MIS304_MISSION_TERMINAL', $error->getMessage()); }
    }

    public function testRefusedAbortedAndExpiredMissionsProduceTruthfulReceipts(): void
    {
        $refused = (new CanonicalRepositoryInspectionMission($this->root()))->run($this->accepted(), ['commit' => str_repeat('b', 40), 'files' => []], 100);
        self::assertSame('REFUSED', $refused['final_state']);
        self::assertSame('MIS306_TARGET_SNAPSHOT_MISMATCH', $refused['disposition_reason']);

        $aborted = (new CanonicalRepositoryInspectionMission($this->root()))->run($this->accepted(1), $this->snapshot(), 100);
        self::assertSame('ABORTED', $aborted['final_state']);
        self::assertSame('MIS307_RESOURCE_BUDGET_EXCEEDED', $aborted['disposition_reason']);

        $expiredAccepted = $this->accepted(10, 100, 110);
        $expired = (new CanonicalRepositoryInspectionMission($this->root()))->run($expiredAccepted, $this->snapshot(), 110);
        self::assertSame('EXPIRED', $expired['final_state']);
        self::assertSame('MIS106_MISSION_EXPIRED', $expired['disposition_reason']);
    }

    public function testFixtureAdapterOutputIsDeterministicAndContainsBoundedFindingFields(): void
    {
        $fixedId = 'mission-reference-deterministic';
        $first = (new CanonicalRepositoryInspectionMission($this->root()))->run($this->accepted(5, 100, 120, $fixedId), $this->snapshot(), 100);
        $second = (new CanonicalRepositoryInspectionMission($this->root()))->run($this->accepted(5, 100, 120, $fixedId), $this->snapshot(), 100);
        self::assertSame($first['evidence_references'], $second['evidence_references']);
        self::assertSame(1, $first['budget_use']['findings']);

        $source = file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Mission/CanonicalRepositoryInspectionMission.php');
        foreach (['CredentialBroker', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER', 'AgentMail'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    private function accepted(int $maxFiles = 5, int $issuedAt = 100, int $expiresAt = 120, ?string $fixedId = null): AcceptedMission
    {
        $id = $fixedId ?? 'mission-reference-'.bin2hex(random_bytes(8));
        $target = self::TARGET;
        $grants = [];
        foreach (CanonicalRepositoryInspectionMission::TRANSITIONS as [, $action, $actor, $targetKind]) {
            $grants[] = ['action' => $action, 'actor' => $actor, 'target' => 'snapshot' === $targetKind ? $target : $id];
        }
        $dossier = MissionDossier::fromArray([
            'schema' => MissionDossier::SCHEMA, 'mission_id' => $id,
            'mission_kind' => 'repository-authority-inspection', 'mission_version' => 1,
            'operator_identity' => 'local-test-operator', 'target_snapshot' => $target,
            'requested_acts' => array_column($grants, 'action'), 'permitted_acts' => $grants,
            'prohibited_acts' => ['modify-target', 'remediate', 'network-access', 'publish-remote'],
            'success_criteria' => ['bounded-findings-dossier-produced'],
            'evidence_requirements' => ['source-location', 'violated-doctrine', 'supporting-evidence', 'recommended-disposition'],
            'time_budget_seconds' => $expiresAt - $issuedAt,
            'resource_budget' => ['max_files' => $maxFiles, 'max_findings' => 5],
            'issued_at' => $issuedAt, 'expires_at' => $expiresAt,
            'terminal_disposition_rules' => ['success' => 'COMPLETED', 'invalid' => 'REFUSED', 'budget' => 'ABORTED', 'expired' => 'EXPIRED'],
            'authorization_provenance' => ['source' => 'operator-mission-order', 'grant_id' => 'operator-grant-'.$id],
        ]);
        return (new OperatorMissionBoundary())->accept($dossier, $issuedAt);
    }

    private function snapshot(): array
    {
        return [
            'commit' => self::TARGET,
            'files' => [
                'src/UnsafeIssuer.php' => "<?php\n// AUTHORITY_FROM_LINEAGE: lineage proves competence, therefore issue\n",
                'src/SafeReader.php' => "<?php\n// provenance only\n",
            ],
        ];
    }

    private function root(): string
    {
        $root = sys_get_temp_dir().'/imperium-mission-test-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);
        return $root;
    }
}
