<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch9DocumentationTest extends TestCase
{
    public function testOnlyTheTwoCompleteDelegateModelGovernanceConsumersUseTheSharedTransition(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['DelegateMissionModelCriteriaRequestService', 'DelegateMissionModelSelectionDecisionService'] as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/'.$service.'.php');
            self::assertStringContainsString('DelegateMissionModelGovernanceAuthorityTransition::run', $source, $service);
            self::assertStringContainsString('DelegateMissionModelGovernanceAuthorityTransition::put', $source, $service);
            self::assertStringContainsString('DelegateMissionModelGovernanceAuthorityTransition::isExactOrHistorical', $source, $service);
        }

        $excluded = [
            $root.'/src/Imperium/Runtime/Oracle/ModelEligibilityFindingService.php',
            $root.'/src/Imperium/Runtime/Oracle/ModelRecommendationService.php',
            $root.'/src/Imperium/Runtime/Curia/ModelSelectionPlanningDecisionService.php',
            $root.'/src/Imperium/Runtime/Curia/DelegateMissionOracleCommissionIssuanceService.php',
            $root.'/src/Imperium/Runtime/Conscription/DelegateMissionModelBindingSealingService.php',
            $root.'/src/Imperium/Runtime/Clavium/DelegateMissionModelAccessAttestationService.php',
        ];
        foreach ($excluded as $path) {
            self::assertStringNotContainsString('DelegateMissionModelGovernanceAuthorityTransition', (string) file_get_contents($path), $path);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/DelegateMissionModelGovernanceAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'delegate-model-governance-authority:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-9-complete.md');
        foreach (['exactly two', '`EXISTS_CANONICALLY`', '`ABSENT`', '`EXISTS_FRAGMENTED`', '`DEFERRED_BOUNDARY`', '`TRANSACTIONAL_CANONICAL`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`', 'Four estimated batches remain', 'Batch 10 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
