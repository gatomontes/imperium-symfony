<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SenatePersonaConfirmationGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;

final class SenatePersonaConfirmationTestimonyGovernanceCognitionBoundaryTest extends TestCase
{
    public function testResolverOwnsOnlyTheOpenedTestimonyStages(): void
    {
        $resolver = new SenatePersonaConfirmationGovernanceCognitionAuthorityResolver(sys_get_temp_dir());
        self::assertTrue($resolver->supports('senate-persona-confirmation', 'testimony-practice'));
        self::assertTrue($resolver->supports('senate-persona-confirmation', 'testimony-governance'));
        self::assertTrue($resolver->supports('senate-persona-confirmation', 'testimony-consistency'));
        self::assertTrue($resolver->supports('senate-persona-confirmation', 'testimony-security'));
        self::assertFalse($resolver->supports('senate-persona-confirmation', 'finding-practice'));
        self::assertFalse($resolver->supports('senate-persona-confirmation', 'question-practice'));
        self::assertFalse($resolver->supports('senate-profile-examination', 'testimony-practice'));
    }

    public function testWitnessHasNoDirectAgentOrCredentialPath(): void
    {
        $root = dirname(__DIR__, 3);
        $gateway = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaWitnessTestimonyCognitionGateway.php');
        $services = (string) file_get_contents($root.'/config/services.yaml');
        $agents = (string) file_get_contents($root.'/config/packages/ai.yaml');

        self::assertStringContainsString("'senate-persona-confirmation', 'testimony-'.\$jurisdiction", $gateway);
        self::assertStringContainsString('GovernanceCognitionInvoker', $gateway);
        self::assertStringNotContainsString('ai.agent.persona_witness', $gateway.$services);
        self::assertStringNotContainsString('persona_witness:', $agents);
        self::assertStringNotContainsString('DEEPSEEK_API_KEY', $gateway);
    }

    public function testCompletionConsumesOnlyExactTestimonyAuthority(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaFirstTestimonyCompletionService.php');

        self::assertStringContainsString("'testimony_authority_consumed' => true", $service);
        self::assertStringContainsString("'question_dispatched_unchanged' => true", $service);
        self::assertStringContainsString("'FIRST_TESTIMONY_SEALED_PENDING_REMAINING_TRIALS'", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
    }
}
