<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;

final class SenatePersonaFreshConsistencyQuestionSeamTest extends TestCase
{
    public function testFreshConsistencyQuestionStopsBeforeTestimony():void
    {
        $root=dirname(__DIR__,3);
        $service=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaFreshConsistencyQuestionService.php');
        $resolver=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaFreshConsistencyQuestionGovernanceCognitionAuthorityResolver.php');
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');
        self::assertStringContainsString("'question-fresh-consistency'",$service);
        self::assertStringContainsString("'FRESH_INSTANCE_CONSISTENCY_TRIAL'",$service);
        self::assertStringContainsString("'testimony'=>null",$service);
        self::assertStringContainsString("'authority_single_use'=>true",$service);
        self::assertStringContainsString('FRESH_INSTANCE_CONSISTENCY_QUESTION_SEALED_PENDING_TESTIMONY_COGNITION_AUTHORIZATION',$service);
        self::assertStringNotContainsString('->answer(',$service);
        self::assertStringNotContainsString('->find(',$service);
        self::assertStringContainsString("'question-fresh-consistency'===$authorityType",$resolver);
        self::assertStringContainsString('/fresh-consistency-questions/*.json',$resolver);
        self::assertStringContainsString("'consistency' => 'question-fresh-consistency'",$gateway);
    }
}
