<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch10DocumentationTest extends TestCase
{
    public function testOnlyTheCompleteModelBindingConsumerUsesTheBatch10Transition(): void
    {
        $root = dirname(__DIR__, 3);
        $adopted = (string) file_get_contents($root.'/src/Imperium/Runtime/Conscription/DelegateMissionModelBindingSealingService.php');
        foreach (['DelegateMissionModelBindingAuthorityTransition::run', 'DelegateMissionModelBindingAuthorityTransition::put', 'DelegateMissionModelBindingAuthorityTransition::isExactOrHistorical'] as $call) {
            self::assertStringContainsString($call, $adopted);
        }

        $excluded = [
            $root.'/src/Imperium/Runtime/Clavium/DelegateMissionModelAccessAttestationService.php',
            $root.'/src/Imperium/Runtime/Imperator/DelegateMissionResourceInvocationDecisionService.php',
            $root.'/src/Imperium/Runtime/Clavium/DelegateMissionProviderInvocationActivationService.php',
            $root.'/src/Imperium/Runtime/Garrison/SubordinatePersonaCanonicalAdmissionService.php',
            $root.'/src/Imperium/Runtime/Garrison/SubordinatePersonaAdmissionIntakeService.php',
            $root.'/src/Imperium/Runtime/Foundry/AdversarialReviewerPersonaConstructionService.php',
            $root.'/src/Imperium/Runtime/Foundry/SubordinateConstructionCaseService.php',
        ];
        foreach ($excluded as $path) {
            self::assertStringNotContainsString('DelegateMissionModelBindingAuthorityTransition', (string) file_get_contents($path), $path);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Conscription/DelegateMissionModelBindingAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'delegate-model-binding-authority:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-10-complete.md');
        foreach (['exactly one', '`EXISTS_CANONICALLY`', '`ABSENT`', '`EXISTS_FRAGMENTED`', '`DEFERRED_BOUNDARY`', '`TRANSACTIONAL_CANONICAL`', '`RACE_EXPOSED`', '`RECOVERY_INCOMPLETE`', '`DEFERRED_EXTERNAL_BOUNDARY`', 'Three estimated batches remain', 'Batch 11 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
