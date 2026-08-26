<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaConsistencyBaselineTestimonyBoundaryTest extends TestCase
{
    public function testConsistencyTestimonyConsumesExactAuthorityAndStopsBeforeSecurity():void
    {
        $root=dirname(__DIR__,3);
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaConsistencyBaselineTestimonyService.php');
        $resolver=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaConfirmationGovernanceCognitionAuthorityResolver.php');
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');

        self::assertStringContainsString("'testimony-consistency'",$resolver);
        self::assertStringContainsString("['practice', 'governance', 'consistency']",$gateway);
        self::assertStringContainsString("2!==count(\$q['prior_testimony'])",$service);
        self::assertStringContainsString("'testimony_authority_consumed'=>true",$service);
        self::assertStringContainsString('CONSISTENCY_BASELINE_TESTIMONY_SEALED_PENDING_SECURITY_QUESTION',$service);
        self::assertStringContainsString("'senator_finding'=>null",$service);
        self::assertStringContainsString("'execution_authority'=>false",$service);
        self::assertStringNotContainsString('senate.committee.security',$service);
    }
}
