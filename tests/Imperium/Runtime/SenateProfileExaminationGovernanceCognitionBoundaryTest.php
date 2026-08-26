<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Senate\SenateProfileExaminationGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;
final class SenateProfileExaminationGovernanceCognitionBoundaryTest extends TestCase
{
    public function testResolverOwnsExactlyTheNineProfileExaminationStages():void
    {
        $resolver=new SenateProfileExaminationGovernanceCognitionAuthorityResolver(sys_get_temp_dir());
        foreach(['question-trust','question-security','question-usability','testimony','finding-trust','finding-security','finding-usability','reconciliation','disposition']as$type)self::assertTrue($resolver->supports('senate-profile-examination',$type));
        self::assertFalse($resolver->supports('senate-persona-confirmation','testimony'));self::assertFalse($resolver->supports('senate-profile-examination','question-practice'));
    }
    public function testGatewaysContainNoDirectAgentOrEnvironmentCredentialPath():void
    {
        $root=dirname(__DIR__,3);foreach(['Question','Testimony','Finding','Reconciliation','Disposition']as$stage){$source=(string)file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiProfileExamination'.$stage.'CognitionGateway.php');self::assertStringContainsString('GovernanceCognitionInvoker',$source);self::assertStringNotContainsString('AgentInterface',$source);self::assertStringNotContainsString('Autowire(service:',$source);self::assertStringNotContainsString('BoundedTransientCognitionCaller',$source);self::assertStringNotContainsString('DEEPSEEK_API_KEY',$source);}
    }
}
