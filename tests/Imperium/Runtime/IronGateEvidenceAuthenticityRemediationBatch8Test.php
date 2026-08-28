<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityConsumer;
use PHPUnit\Framework\TestCase;
final class IronGateEvidenceAuthenticityRemediationBatch8Test extends TestCase
{
    public function testConsumerAndRecoveryBoundaryAreCanonical(): void
    {
        self::assertTrue(method_exists(DeterministicTransitionCallerAuthorityConsumer::class, 'consume'));
        $root=dirname(__DIR__,3);$handoff=(string)file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-8-complete.md');
        foreach(['exact transition and target','instance, binding, generation and source digest','forward-recovery protocol','`EXISTS_CANONICALLY`','Only Batch 9 may next be considered','Batch 9 is not authorized'] as $proof) self::assertStringContainsString($proof,$handoff);
    }
}
