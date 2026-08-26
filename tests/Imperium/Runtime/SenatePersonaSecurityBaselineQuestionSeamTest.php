<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaSecurityBaselineQuestionSeamTest extends TestCase
{
    public function testSecurityQuestionRequiresThreePriorTurnsAndStopsBeforeWitness():void
    {
        $root=dirname(__DIR__,3);
        $source=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaSecurityBaselineQuestionService.php');

        self::assertStringContainsString("'jurisdiction'=>'security'",$source);
        self::assertStringContainsString("2!==count(\$c['prior_testimony'])",$source);
        self::assertStringContainsString("'prior_testimony_digests'=>array_column(\$prior,'turn_digest')",$source);
        self::assertStringContainsString('SECURITY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',$source);
        self::assertStringContainsString("'testimony'=>null",$source);
        self::assertStringContainsString("'authority_single_use'=>true",$source);
        self::assertStringNotContainsString('->answer(',$source);
    }
}
