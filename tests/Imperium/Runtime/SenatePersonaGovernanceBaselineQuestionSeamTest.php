<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaGovernanceBaselineQuestionSeamTest extends TestCase
{
    public function testGovernanceQuestionStopsBeforeWitness():void
    {
        $root=dirname(__DIR__,3);
        $source=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaJurisdictionBaselineService.php');
        self::assertStringContainsString('GOVERNANCE_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',$source);
        self::assertStringContainsString("'jurisdiction'=>'governance'",$source);
        self::assertStringContainsString("'testimony'=>null",$source);
        self::assertStringContainsString("'authority_single_use'=>true",$source);
        self::assertStringNotContainsString('->answer(',$source);
        self::assertStringNotContainsString("['governance','consistency','security']",str_replace(' ','',$source));
    }
}
