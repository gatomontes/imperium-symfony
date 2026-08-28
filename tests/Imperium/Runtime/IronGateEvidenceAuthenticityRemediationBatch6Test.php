<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityContract;
use PHPUnit\Framework\TestCase;
final class IronGateEvidenceAuthenticityRemediationBatch6Test extends TestCase
{
    public function testCallerAuthoritiesAreDistinctAndThreatModelIsHonest(): void
    {
        self::assertCount(3, DeterministicTransitionCallerAuthorityContract::TRANSITIONS);
        self::assertSame(3, count(array_unique(DeterministicTransitionCallerAuthorityContract::TRANSITIONS)));
        $root=dirname(__DIR__,3);$doc=(string)file_get_contents($root.'/docs/iron-gate-runtime-principal-caller-authority-and-integrity-threat-model.md');
        foreach(['The contract alone authenticates nothing','`TRUSTED_WRITER_CANONICAL_INTEGRITY`','`HOSTILE_WRITER_NON_FORGEABILITY`','`SINGLE_AUTHORITATIVE_ROOT_ONLY`','does not issue or consume caller authority'] as $proof)self::assertStringContainsString($proof,$doc);
    }
}
