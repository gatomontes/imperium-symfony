<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch2DocumentationTest extends TestCase
{
    public function testOperationalClaimAdoptsContractsWithoutChangingLockOrExternalBoundaries(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Clavium/OperationalCognitionInvocationClaimService.php');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-2-complete.md');

        foreach (['TransactionalAuthorityConsumptionEnvelope', 'transactional_consumption', "'oca-cognition-authority:'", "'oca-lease:'", 'ReplayFingerprint::of'] as $proof) {
            self::assertStringContainsString($proof, $service);
        }
        self::assertLessThan(strpos($service, "'oca-lease:'"), strpos($service, "'oca-cognition-authority:'"));
        foreach (['credential_resolved', 'provider_invoked', 'network_access_performed'] as $field) {
            self::assertStringContainsString("'".$field."' => false", $service);
        }
        foreach (['No authority schema', 'provider journal', 'Iron Gate', 'Lazaretto', 'sortie', 'Batch 3 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
