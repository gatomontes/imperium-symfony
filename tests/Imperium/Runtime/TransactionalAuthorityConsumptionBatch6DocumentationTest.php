<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch6DocumentationTest extends TestCase
{
    public function testEveryDeterministicDelegateSenateConsumerUsesTheExactSharedAuthorityTransition(): void
    {
        $root = dirname(__DIR__, 3);
        $services = [
            'DelegateMissionFirstQuestionCommissionDispositionService',
            'DelegateMissionQuestionDispatchAuthorizationEngine',
            'DelegateMissionQuestionDispatchEngine',
            'DelegateMissionSubsequentQuestionCommissionIssuanceEngine',
            'DelegateMissionSubsequentQuestionCommissionDispositionEngine',
            'DelegateMissionFindingAuthorityOpeningService',
            'DelegateMissionDeliberationOpeningService',
            'DelegateMissionDispositionAuthorityOpeningService',
        ];

        foreach ($services as $service) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/'.$service.'.php');
            self::assertStringContainsString('DelegateMissionSenateAuthorityTransition::run', $source, $service);
            self::assertStringContainsString('DelegateMissionSenateAuthorityTransition::put', $source, $service);
            self::assertStringContainsString('DelegateMissionSenateAuthorityTransition::isExactOrHistorical', $source, $service);
        }

        foreach (['DelegateMissionJurisdictionQuestionAuthorshipEngine', 'DelegateMissionTestimonyResponseEngine', 'DelegateMissionSenatorFindingService', 'DelegateMissionFindingReconciliationService', 'DelegateMissionSenateDispositionService'] as $deferred) {
            $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/'.$deferred.'.php');
            self::assertStringNotContainsString('DelegateMissionSenateAuthorityTransition', $source, $deferred);
        }

        $transition = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/DelegateMissionSenateAuthorityTransition.php');
        foreach (['TransactionalAuthorityConsumptionEnvelope::complete', 'ReplayFingerprint::of', "'delegate-senate-authority:'", 'ImmutableRecordStore'] as $proof) {
            self::assertStringContainsString($proof, $transition);
        }

        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-6-complete.md');
        foreach (['Five consumers', '`RECOVERY_INCOMPLETE`', 'No authority schema', 'Profile Senate migration', 'Iron Gate', 'Lazaretto', 'sortie', 'Batch 7 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
