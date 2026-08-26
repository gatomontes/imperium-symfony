<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Senate\SenatePersonaFreshConsistencyTestimonyGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;

final class SenatePersonaFreshConsistencyTestimonyBoundaryTest extends TestCase
{
    public function testFreshTestimonyConsumesExactAuthorityAndStopsBeforePressure():void
    {
        $root=dirname(__DIR__,3);
        $authority=new SenatePersonaFreshConsistencyTestimonyGovernanceCognitionAuthorityResolver(sys_get_temp_dir());
        self::assertTrue($authority->supports('senate-persona-confirmation','testimony-fresh-consistency'));
        self::assertFalse($authority->supports('senate-persona-confirmation','testimony-consistency'));
        self::assertFalse($authority->supports('senate-profile-examination','testimony-fresh-consistency'));
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaFreshConsistencyTestimonyService.php');
        $resolver=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaFreshConsistencyTestimonyGovernanceCognitionAuthorityResolver.php');
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');
        self::assertStringContainsString("'testimony-fresh-consistency'",$resolver);
        self::assertStringContainsString('/fresh-consistency-questions/*.json',$resolver);
        self::assertStringContainsString('/fresh-consistency-trials/*.json',$resolver);
        self::assertStringContainsString("'testimony-fresh-consistency' === \$authorityType",$gateway);
        self::assertStringContainsString("'testimony_authority_consumed'=>true",$service);
        self::assertStringContainsString('FRESH_INSTANCE_CONSISTENCY_TRIAL_SEALED_PENDING_PRESSURE_TRIALS',$service);
        self::assertStringContainsString("'pressure_trials_required'=>true",$service);
        self::assertStringContainsString("'senator_finding'=>null",$service);
        self::assertStringContainsString("'execution_authority'=>false",$service);
        self::assertStringNotContainsString('senate.committee.governance',$service);
        self::assertStringNotContainsString('senate.committee.security',$service);
        self::assertStringNotContainsString('->find(',$service);
    }
}
