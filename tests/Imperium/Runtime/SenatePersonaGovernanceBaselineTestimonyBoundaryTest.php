<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaGovernanceBaselineTestimonyBoundaryTest extends TestCase
{
    public function testGovernanceTestimonyConsumesOnlyItsExactQuestion():void
    {
        $root=dirname(__DIR__,3);
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaGovernanceBaselineTestimonyService.php');
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');
        self::assertStringContainsString("'testimony_authority_consumed'=>true",$service);
        self::assertStringContainsString('GOVERNANCE_BASELINE_TESTIMONY_SEALED_PENDING_CONSISTENCY_QUESTION',$service);
        self::assertStringContainsString("'testimony-'.\$jurisdiction",$gateway);
        self::assertStringNotContainsString('senate.committee.consistency',$service);
        self::assertStringNotContainsString('senate.committee.security',$service);
        self::assertStringContainsString("'execution_authority'=>false",$service);
    }
}
