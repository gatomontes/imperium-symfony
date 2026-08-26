<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SenatePersonaConfirmationQuestionGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;

final class SenatePersonaQuestionGovernanceCognitionBoundaryTest extends TestCase
{
    public function testResolverOwnsExactlyFourQuestionStages():void
    {
        $resolver=new SenatePersonaConfirmationQuestionGovernanceCognitionAuthorityResolver(sys_get_temp_dir());
        foreach(['practice','governance','consistency','security']as$jurisdiction)self::assertTrue($resolver->supports('senate-persona-confirmation','question-'.$jurisdiction));
        self::assertFalse($resolver->supports('senate-persona-confirmation','testimony-practice'));
        self::assertFalse($resolver->supports('senate-persona-confirmation','finding-practice'));
        self::assertFalse($resolver->supports('senate-profile-examination','question-practice'));
    }

    public function testQuestionGatewayHasNoDirectAgentOrCredentialPath():void
    {
        $root=dirname(__DIR__,3);
        $gateway=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');
        $services=(string)file_get_contents($root.'/config/services.yaml');
        $agents=(string)file_get_contents($root.'/config/packages/ai.yaml');

        self::assertStringContainsString("'senate-persona-confirmation', 'question-'.\$jurisdiction",$gateway);
        self::assertStringContainsString('GovernanceCognitionInvoker',$gateway);
        self::assertStringNotContainsString('AgentInterface',$gateway);
        self::assertStringNotContainsString('MessageBag',$gateway);
        foreach(['senator_practice','senator_governance','senator_consistency','senator_security']as$agent){
            self::assertStringNotContainsString('ai.agent.'.$agent,$gateway.$services);
            self::assertStringNotContainsString($agent.':',$agents);
        }
        self::assertStringNotContainsString('DEEPSEEK_API_KEY',$gateway);
    }

    public function testNativeAuthorityAndOrderedLineageAreReconstructed():void
    {
        $root=dirname(__DIR__,3);
        $resolver=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaConfirmationQuestionGovernanceCognitionAuthorityResolver.php');
        self::assertStringContainsString("'question-practice'=>'practice'",$resolver);
        self::assertStringContainsString("'question-security'=>'security'",$resolver);
        self::assertStringContainsString("3===count(\$prior)",$resolver);
        self::assertStringContainsString('referencedTurn',$resolver);
        self::assertStringContainsString('requireCarriedPrior',$resolver);
        self::assertStringContainsString("CanonicalJson::encode([\$assignment,\$context,\$witness])",$resolver);
        self::assertStringContainsString("'consumed'=>\$this->consumed(\$jurisdiction,\$authorityId)",$resolver);
    }
}
