<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;
final class SenatePersonaConsistencyBaselineQuestionSeamTest extends TestCase
{
 public function testConsistencyQuestionRequiresTwoPriorTurnsAndStopsBeforeWitness():void
 {
  $root=dirname(__DIR__,3);$s=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaConsistencyBaselineQuestionService.php');
  self::assertStringContainsString("'jurisdiction'=>'consistency'",$s);
  self::assertStringContainsString("'prior_testimony_digests'=>array_column(\$prior,'turn_digest')",$s);
  self::assertStringContainsString('CONSISTENCY_BASELINE_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',$s);
  self::assertStringContainsString("'testimony'=>null",$s);
  self::assertStringNotContainsString('->answer(',$s);
  self::assertStringNotContainsString('senate.committee.security',$s);
 }
}
