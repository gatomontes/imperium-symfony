<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch11DocumentationTest extends TestCase
{
    public function testOnlyOracleEligibilityUsesTheBatch11RecoveryTransition(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Oracle/ModelEligibilityFindingService.php');
        foreach (['OracleEligibilityAuthorityTransition', "'FINDING_COMMITTED'", "'PHASE_RECONCILED'", "'TRANSACTION_COMMITTED'", 'closeIfComplete'] as $proof) {
            self::assertStringContainsString($proof, $service);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Oracle/OracleEligibilityAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'oracle-eligibility-case:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $excluded = [
            $root.'/src/Imperium/Runtime/Curia/OperationalAdoptionIndependentAssessmentService.php',
            $root.'/src/Imperium/Runtime/Curia/ModelRequirementCommissionService.php',
            $root.'/src/Imperium/Runtime/Curia/DelegateMissionOracleCommissionIssuanceService.php',
            $root.'/src/Imperium/Runtime/Garrison/SubordinatePersonaCanonicalAdmissionService.php',
            $root.'/src/Imperium/Runtime/Conscription/LegateRuntimeActivationService.php',
        ];
        foreach ($excluded as $path) {
            self::assertStringNotContainsString('OracleEligibilityAuthorityTransition', (string) file_get_contents($path), $path);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-11-complete.md');
        foreach (['exactly one', '`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`', '`TRANSACTIONAL_CANONICAL`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`', 'Two estimated batches remain', 'Batch 12 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
