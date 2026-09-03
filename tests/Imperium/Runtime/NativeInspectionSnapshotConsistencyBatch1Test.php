<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class NativeInspectionSnapshotConsistencyBatch1Test extends TestCase
{
    private const string CONTRACT = 'docs/native-inspection-snapshot-consistency-contract-v1.md';

    public function testContractDefinesTheExactBoundedObservationGuarantee(): void
    {
        $contract = $this->read(self::CONTRACT);
        foreach (['NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_1_COMPLETE',
            'same manifest before and after one complete derivation', 'no more than two attempts',
            'Manifest capture failure counts as an unstable attempt', 'not linearizable after return',
            'not execution retry', 'UNKNOWN_REPLAY_PROHIBITED'] as $required) {
            self::assertStringContainsStringIgnoringCase($required, $contract, $required);
        }
    }

    public function testContractNamesEveryManifestBaseAndRejectsAliases(): void
    {
        $contract = $this->read(self::CONTRACT);
        foreach (['deterministic-execution-claims', 'outbound-email-authorization-issuances',
            'deterministic-effect-start-journals', 'native-provider-transition',
            'NativeState::SOURCES', 'transition-trust', 'legacy-provider-transitions',
            'Symlinks', '.lock', 'Every other regular file is hashed'] as $required) {
            self::assertStringContainsStringIgnoringCase($required, $contract, $required);
        }
    }

    public function testContractPreservesEveryPublicProjectionAndClassification(): void
    {
        $contract = $this->read(self::CONTRACT);
        foreach (['interpret()', 'forClaim()', 'forJournal()', 'read()', 'reconstruct()',
            'provider_effect_permitted=false', 'execution_authority=false',
            'provider_effect_started=false', 'LEGACY_UNBOUND', 'BOUND_INACTIVE',
            'COMMITTED_CURRENT', 'COMMITTED_NOT_CURRENT', 'INCOMPLETE', 'CORRUPT',
            'UNRELATED_OPERATION', 'ABSENT', 'COMMITTED', 'NOT_IMPLEMENTED'] as $required) {
            self::assertStringContainsString($required, $contract, $required);
        }
    }

    public function testContractSeparatesAlreadyLockedAndUnlockedCallers(): void
    {
        $contract = $this->read(self::CONTRACT);
        foreach (['DeterministicJournalBoundCredentialBroker::invoke()',
            'DeterministicEffectStartJournalService::start()',
            'AgentMailIdempotencyHeaderAdapter::invoke()', 'NativeConsumer::execute()',
            'without reacquiring any production lock', '--inspect-claim',
            'read-only and non-authorizing'] as $required) {
            self::assertStringContainsString($required, $contract, $required);
        }
    }

    public function testHandoffStopsAtTheBatchTwoGate(): void
    {
        $handoff = $this->read('docs/handoffs/native-inspection-snapshot-consistency-batch-1-complete.md');
        foreach (['NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_BATCH_1_COMPLETE',
            self::CONTRACT, 'Four stages remain', 'Batch 2', 'production lock',
            'php vendor/bin/phpunit tests/Imperium/Runtime/NativeInspectionSnapshotConsistencyBatch1Test.php'] as $required) {
            self::assertStringContainsString($required, $handoff, $required);
        }
    }

    private function read(string $relative): string
    {
        $bytes = file_get_contents(dirname(__DIR__, 3).'/'.$relative);
        self::assertNotFalse($bytes);
        return $bytes;
    }
}
