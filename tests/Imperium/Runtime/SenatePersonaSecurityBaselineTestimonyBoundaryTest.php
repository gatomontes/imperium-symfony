<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaSecurityBaselineTestimonyBoundaryTest extends TestCase
{
    public function testSecurityTestimonyConsumesExactAuthorityAndStopsBeforeFindings():void
    {
        $root=dirname(__DIR__,3);
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaSecurityBaselineTestimonyService.php');
        $resolver=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaConfirmationGovernanceCognitionAuthorityResolver.php');
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');

        self::assertStringContainsString("'testimony-security'",$resolver);
        self::assertStringContainsString("['practice', 'governance', 'consistency', 'security']",$gateway);
        self::assertStringContainsString("3!==count(\$q['prior_testimony'])",$service);
        self::assertStringContainsString("'testimony_authority_consumed'=>true",$service);
        self::assertStringContainsString('SECURITY_BASELINE_TESTIMONY_SEALED_PENDING_FINDING_AUTHORITY_OPENING',$service);
        self::assertStringContainsString("'senator_finding'=>null",$service);
        self::assertStringContainsString("'senator_finding_authority'=>false",$service);
        self::assertStringContainsString("'execution_authority'=>false",$service);
    }
}
