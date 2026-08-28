<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch4DocumentationTest extends TestCase
{
    public function testGovernanceClaimAdoptsCompleteResolverBoundTransactionWithoutMovingProviderIo(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Clavium/GovernanceCognitionInvocationClaimService.php');
        $test = (string) file_get_contents($root.'/tests/Imperium/Runtime/GovernanceCognitionAccessSubstrateTest.php');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-4-complete.md');

        foreach (['TransactionalAuthorityConsumptionEnvelope', 'ReplayFingerprint::of', "'governance_authority' => \$authority", 'transactional_consumption'] as $proof) {
            self::assertStringContainsString($proof, $service);
        }
        self::assertLessThan(strpos($service, "'gca-lease:'"), strpos($service, "'gca-authority:'"));
        foreach (['testStructurallyDivergentTransactionalEnvelopeFailsReplayStopped', 'testHistoricalClaimWithoutTransactionalEnvelopeRemainsExactReplay', 'testTwoProcessesConvergeOnOneTransactionalClaim'] as $proof) {
            self::assertStringContainsString($proof, $test);
        }
        foreach (['No authority schema', 'provider journal', 'Iron Gate', 'Lazaretto', 'sortie', 'Batch 5 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
