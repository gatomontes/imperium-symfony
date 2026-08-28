<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionPreparationDocumentationTest extends TestCase
{
    public function testBatchZeroInventoriesConsumersAndKeepsMigrationClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/transactional-authority-consumption-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-preparation-batch-0-complete.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach (['`TRANSACTIONAL_CANONICAL`', '`LOCKED_FRAGMENTED`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`'] as $posture) {
            self::assertStringContainsString($posture, $inventory);
        }
        foreach ([
            'AuthorityConsumptionStore',
            'ReplayFingerprint',
            'oca-cognition-authority:<sha256 authorityId>',
            'oca-lease:<sha256 leaseId>',
            'GovernanceCognitionInvocationClaimService',
            'ProviderInvocationClaimService',
            'DelegateMissionTurnRecoveryService',
            'claim/interruption single-winner behavior',
            'PREPARED → CUSTODY_RESTORED → BINDING_RETIRED → TERMINAL_RECORDED → COMPLETE',
            'credential_resolved=false',
            'provider_journal_created=false',
            'network_access_performed=false',
            'No step is authorized by this inventory.',
        ] as $proof) {
            self::assertStringContainsString($proof, $inventory);
        }
        foreach (['revocation', 'telemetry', 'reassessment', 'containment', 'incidents', 'Iron Gate', 'Lazaretto', 'sorties', 'external receipts', 'credential-platform work'] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
        self::assertStringContainsString('Runtime behavior is unchanged.', $handoff);
        self::assertStringContainsString('Batch 1 is not authorized by this handoff', $handoff);
    }
}
