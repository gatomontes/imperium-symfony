<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class NativeInspectionSnapshotConsistencyBatch5TerminalAuditTest extends TestCase
{
    public function testAuditStartsFromTheExactCleanBatchFourBaselineAndUsesBlackquillStructure(): void
    {
        $audit = $this->read('docs/native-inspection-snapshot-consistency-terminal-audit-v1.md');
        foreach (['f44a4cf5375bf592fa6da6a51ce95a34b2fb645a', '## Claim',
            '## Weak points challenged', '## Verdict', '## If a stronger version is wanted',
            'NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_TERMINAL_AUDIT_ACCEPTED'] as $required) {
            self::assertStringContainsString($required, $audit, $required);
        }
    }

    public function testAuditChallengesEveryMaterialClaimAndStatesTheLimits(): void
    {
        $audit = $this->read('docs/native-inspection-snapshot-consistency-terminal-audit-v1.md');
        foreach (['Whole read set', 'Nested snapshots', 'MAX_ATTEMPTS = 2',
            'attempt-bounded, not a wall-clock', 'AtomicTransition', 'counterfeit authority',
            'sibling PHP processes', 'real Symfony-discovered', 'stale immediately after return',
            'DEFERRED_BOUNDARY', 'same-content ABA', 'no material defect remains'] as $required) {
            self::assertStringContainsStringIgnoringCase($required, $audit, $required);
        }
    }

    public function testCommittedImplementationMatchesTheAcceptedStructuralBoundary(): void
    {
        $snapshot = $this->read('src/Imperium/Runtime/ProviderTransition/NativeInspectionSnapshot.php');
        self::assertStringContainsString('MAX_ATTEMPTS = 2', $snapshot);
        self::assertStringContainsString('private static array $active', $snapshot);
        self::assertStringContainsString('finally {', $snapshot);
        foreach (['AtomicTransition', 'flock(', 'file_put_contents(', 'mkdir('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $snapshot, $forbidden);
        }
        $reader = $this->read('src/Imperium/Runtime/ProviderTransition/NativeBindingReader.php');
        foreach (['public function interpret(', 'public function forClaim(', 'public function forJournal(',
            'public function read(', '->observe('] as $edge) {
            self::assertStringContainsString($edge, $reader, $edge);
        }
        self::assertStringContainsString('->observe(', $this->read('src/Imperium/Runtime/ProviderTransition/NativeReconstructor.php'));
        self::assertStringNotContainsString('inspectionCheckpoint', $this->read('config/services.yaml'));
    }

    public function testAllBatchEvidenceAndCompletionMarkersRemainPresent(): void
    {
        foreach ([
            'docs/handoffs/native-inspection-snapshot-consistency-preparation-batch-0-complete.md' => 'PREPARATION_BATCH_0_COMPLETE',
            'docs/handoffs/native-inspection-snapshot-consistency-batch-1-complete.md' => 'BATCH_1_COMPLETE',
            'docs/handoffs/native-inspection-snapshot-consistency-batch-2-complete.md' => 'BATCH_2_COMPLETE',
            'docs/handoffs/native-inspection-snapshot-consistency-batch-3-complete.md' => 'BATCH_3_COMPLETE',
            'docs/handoffs/native-inspection-snapshot-consistency-batch-4-complete.md' => 'BATCH_4_COMPLETE',
        ] as $path => $marker) {
            self::assertStringContainsString($marker, $this->read($path), $path);
        }
        $complete = $this->read('docs/handoffs/native-inspection-snapshot-consistency-campaign-complete.md');
        self::assertStringContainsString('NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_CAMPAIGN_COMPLETE', $complete);
        self::assertStringContainsString('No further stage remains', $complete);
        self::assertStringContainsString('php vendor/bin/phpunit tests', $complete);
    }

    public function testTerminalLedgerPinsTheReviewedBatchFourImplementationAndSupersedesOldHashesExplicitly(): void
    {
        $ledger = json_decode($this->read('docs/native-inspection-snapshot-consistency-terminal-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('f44a4cf5375bf592fa6da6a51ce95a34b2fb645a', $ledger['audited_main']);
        self::assertGreaterThanOrEqual(12, count($ledger['sources']));
        foreach ($ledger['sources'] as $source) {
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $source['normalized_sha256']);
            self::assertSame($source['normalized_sha256'], hash('sha256', $this->read($source['path'])), $source['path']);
        }
        $sources = array_column($ledger['sources'], null, 'path');
        self::assertSame('241eec57f8696fe88a428cb265d0601b2fefa81b484dadfa0b7966d0581d70e5', $sources['src/Imperium/Runtime/ProviderTransition/NativeBindingReader.php']['predecessor_sha256']);
        self::assertSame('06da05963ecfef1d25631ef59ff2a0e1010cc6e43a61fffb7601139ad2dfe640', $sources['src/Imperium/Runtime/ProviderTransition/NativeReconstructor.php']['predecessor_sha256']);
    }

    public function testClosurePreservesTheNonAuthorizingAndHistoricalBoundaries(): void
    {
        $audit = $this->read('docs/native-inspection-snapshot-consistency-terminal-audit-v1.md');
        foreach (['BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED',
            'bounded pre-effect', 'Iron Gate', 'Lazaretto', 'non-authorizing'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $audit, $boundary);
        }
    }

    private function read(string $relative): string
    {
        $bytes = file_get_contents(dirname(__DIR__, 3).'/'.$relative);
        self::assertNotFalse($bytes);
        return $bytes;
    }
}
