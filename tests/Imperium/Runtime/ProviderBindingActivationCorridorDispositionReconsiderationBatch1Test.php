<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionEligibilityContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionEvidenceDossierContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionTargetContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationBatch1Test extends TestCase
{
    public function testThreeContractsAreDistinctVersionedAndAuthorityEmpty(): void
    {
        $contracts = [
            ActivationCorridorDispositionTargetContract::class,
            ActivationCorridorDispositionEvidenceDossierContract::class,
            ActivationCorridorDispositionEligibilityContract::class,
        ];

        self::assertCount(3, array_unique(array_map(static fn (string $contract): string => $contract::SCHEMA, $contracts)));
        foreach ($contracts as $contract) {
            self::assertSame(1, $contract::VERSION);
            self::assertNotSame('', $contract::PRODUCER_POSTURE);
            self::assertNotEmpty($contract::CONSUMER_POSTURES);
            self::assertContains('record_digest', $contract::REQUIRED_FIELDS);
            foreach ($contract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
        }
    }

    public function testDossierAndEligibilityPreserveExactEvidenceAndRefusal(): void
    {
        self::assertSame(6, ActivationCorridorDispositionEvidenceDossierContract::REQUIRED_INTERRUPTION_EVIDENCE_COUNT);
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', ActivationCorridorDispositionEvidenceDossierContract::CONTINUING_CUSTODY_REFUSAL);
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', ActivationCorridorDispositionEligibilityContract::CONTINUING_CUSTODY_REFUSAL);
        self::assertSame(['QUARANTINED_PENDING_REMEDIATION', 'RETIRE_CORRIDOR'], ActivationCorridorDispositionEligibilityContract::DISPOSITIONS);
        self::assertContains('principal_effectively_active', ActivationCorridorDispositionEligibilityContract::REQUIRED_PREDICATE_FIELDS);
        self::assertContains('principal_corridor_disposition_authority', ActivationCorridorDispositionEligibilityContract::REQUIRED_PREDICATE_FIELDS);
        self::assertContains('future_reconsideration_requires_new_authority', ActivationCorridorDispositionEligibilityContract::REQUIRED_QUARANTINE_CONSEQUENCE_FIELDS);
        self::assertContains('replacement_corridor_requires_new_authority', ActivationCorridorDispositionEligibilityContract::REQUIRED_RETIREMENT_CONSEQUENCE_FIELDS);
    }

    public function testDocumentationAuthorizesOnlyCallerAuthorityContractsAndValidatorsNext(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-binding-activation-corridor-disposition-reconsideration-contracts.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-batch-1-complete.md'));

        foreach (['BATCH_1_AUTHORITY_EMPTY_TARGET_DOSSIER_AND_ELIGIBILITY_CONTRACTS_COMPLETE', 'Every `NON_AUTHORITIES` value is false', 'Repository schemas and test fixtures are not instance evidence', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'No principal or binding is activated', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $proof) self::assertNotFalse(stripos($contracts, $proof), $proof);
        foreach (['Only Batch 2 is authorized', 'caller-authority transition', 'contracts and validators only', 'may not issue or consume authority', 'effectively `ACTIVE`', 'corridor_disposition_authority=true', 'may not identify a live target', 'seal a corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
