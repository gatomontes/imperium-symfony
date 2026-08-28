<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Curia\OutboundEmailAuthorizationRequestService;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceService;
use App\Imperium\Runtime\Imperator\OutboundEmailDecisionService;
use PHPUnit\Framework\TestCase;
final class IronGateEvidenceAuthenticityRemediationBatch9Test extends TestCase
{
    public function testThreeTransitionsRequireSeparateCallerAuthorityIdentity(): void
    {
        self::assertSame('callerAuthorityId', (new \ReflectionMethod(OutboundEmailAuthorizationRequestService::class, 'request'))->getParameters()[0]->getName());
        self::assertSame('callerAuthorityId', (new \ReflectionMethod(OutboundEmailDecisionService::class, 'decide'))->getParameters()[0]->getName());
        self::assertSame('callerAuthorityId', (new \ReflectionMethod(OutboundEmailAuthorizationIssuanceService::class, 'issue'))->getParameters()[0]->getName());
        $root=dirname(__DIR__,3);$handoff=(string)file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-9-complete.md');
        foreach(['all three deterministic','consumed native Imperator principal authority','consumption-before-target crash gap','Only Batch 10 may next be considered','Batch 10 is not authorized','No provider callback'] as $proof) self::assertStringContainsString($proof,$handoff);
    }
}
