<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaJurisdictionBaselineAggregationBoundaryTest extends TestCase
{
    public function testAggregationRequiresFourSealedTurnsAndOpensNoAuthority():void
    {
        $root=dirname(__DIR__,3);
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaJurisdictionBaselineAggregationService.php');
        self::assertStringContainsString("['practice','governance','consistency','security']",$service);
        self::assertStringContainsString('SECURITY_BASELINE_TESTIMONY_SEALED_PENDING_BASELINE_AGGREGATION',$service);
        self::assertStringContainsString('REQUIRED_JURISDICTION_BASELINE_COMPLETE_PENDING_ADDITIONAL_TRIALS',$service);
        self::assertStringContainsString("'additional_trials_required'=>true",$service);
        self::assertStringContainsString("'senator_findings'=>[]",$service);
        self::assertStringContainsString("'execution_authority'=>false",$service);
        self::assertStringNotContainsString('CognitionGateway',$service);
        self::assertStringNotContainsString('->find(',$service);
        self::assertStringNotContainsString('->authorQuestion(',$service);
        self::assertStringNotContainsString('->answer(',$service);
    }
}
