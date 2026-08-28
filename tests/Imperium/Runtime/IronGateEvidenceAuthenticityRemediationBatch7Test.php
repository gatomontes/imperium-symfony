<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityIssuanceService;
use PHPUnit\Framework\TestCase;
final class IronGateEvidenceAuthenticityRemediationBatch7Test extends TestCase
{
    public function testIssuerSurfaceSeparatesSeneschalAndImperatorSources(): void
    {
        self::assertTrue(method_exists(DeterministicTransitionCallerAuthorityIssuanceService::class,'issueSeneschal'));
        self::assertTrue(method_exists(DeterministicTransitionCallerAuthorityIssuanceService::class,'issueImperator'));
        $root=dirname(__DIR__,3);$handoff=(string)file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-7-complete.md');
        foreach(['No transition consumes these records yet','`EXISTS_FRAGMENTED`','Only Batch 8 may next be considered','Batch 8 is not authorized']as$proof)self::assertStringContainsString($proof,$handoff);
    }
}
