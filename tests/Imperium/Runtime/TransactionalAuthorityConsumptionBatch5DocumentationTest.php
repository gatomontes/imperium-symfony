<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch5DocumentationTest extends TestCase
{
    public function testDelegateClaimAdoptsCompleteCompositeLockTransactionWithoutMovingProviderIo(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Clavium/ProviderInvocationClaimService.php');
        $test = (string) file_get_contents($root.'/tests/Imperium/Runtime/ProviderInvocationClaimServiceTest.php');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-5-complete.md');

        foreach (['TransactionalAuthorityConsumptionEnvelope', 'ReplayFingerprint::of', "'activation' => \$activation", 'transactional_consumption'] as $proof) {
            self::assertStringContainsString($proof, $service);
        }
        self::assertSame(2, substr_count($service, "'provider-invocation-claim:'"));
        foreach (['testStructurallyDivergentTransactionalEnvelopeFailsReplayStopped', 'testHistoricalClaimWithoutTransactionalEnvelopeRemainsExactReplay', 'testTwoProcessesConvergeOnOneTransactionalClaim'] as $proof) {
            self::assertStringContainsString($proof, $test);
        }
        foreach (['one physical lock', 'provider-journal', 'unknown outcomes', 'Iron Gate', 'Lazaretto', 'sortie', 'Batch 6 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
