<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionBatch3DocumentationTest extends TestCase
{
    public function testOperationalClaimCommitBoundariesAreProvedWithoutOpeningExternalEffects(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Clavium/OperationalCognitionInvocationClaimService.php');
        $test = (string) file_get_contents($root.'/tests/Imperium/Runtime/OperationalCognitionInvocationClaimServiceTest.php');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/transactional-authority-consumption-batch-3-complete.md');

        foreach (['PREPARED', 'CONSUMPTION_COMMITTED', 'RESULT_COMMITTED', 'COMPLETE'] as $checkpoint) {
            self::assertStringContainsString("after('".$checkpoint."')", $service);
            self::assertStringContainsString("['".$checkpoint."'", $test);
        }
        foreach (['provider_invoked', 'credential_resolved', 'network_access_performed'] as $field) {
            self::assertStringContainsString("self::assertFalse(\$recovered['".$field."'])", $test);
        }
        foreach (['No authority schema', 'provider journal', 'Iron Gate', 'Lazaretto', 'sortie', 'Batch 4 is not authorized'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }
}
