<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch8DocumentationTest extends TestCase
{
    public function testOnlyTheTwoSingleResultOperationalAdoptionConsumersUseTheSharedTransition(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['OperationalAdoptionReconciliationService', 'OperationalAdoptionDispositionService'] as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/'.$service.'.php');
            self::assertStringContainsString('OperationalAdoptionAuthorityTransition::run', $source, $service);
            self::assertStringContainsString('OperationalAdoptionAuthorityTransition::put', $source, $service);
            self::assertStringContainsString('OperationalAdoptionAuthorityTransition::isExactOrHistorical', $source, $service);
        }

        foreach (['OperationalAdoptionIntakeDispositionService', 'OperationalAdoptionIndependentAssessmentService'] as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/'.$service.'.php');
            self::assertStringNotContainsString('OperationalAdoptionAuthorityTransition', $source, $service);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Curia/OperationalAdoptionAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'operational-adoption-authority:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-8-complete.md');
        foreach (['exactly two', '`EXISTS_CANONICALLY`', '`ABSENT`', '`EXISTS_FRAGMENTED`', '`TRANSACTIONAL_CANONICAL`', '`RECOVERY_INCOMPLETE`', 'Iron Gate', 'Lazaretto', 'sortie', 'Five estimated batches remain', 'Batch 9 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
