<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class LaboratoriumGovernanceCognitionBoundaryTest extends TestCase
{
    public function testGatewayContainsNoDirectAgentTransientCallerOrCredentialPath(): void
    {
        $root = dirname(__DIR__, 3);
        $source = (string) file_get_contents($root.'/src/Imperium/Runtime/Laboratorium/SymfonyAiProfileElaborationCognitionGateway.php');
        foreach (['AgentInterface', 'Autowire(service:', 'BoundedTransientCognitionCaller', 'DEEPSEEK_API_KEY', '$alchemist'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        self::assertStringContainsString('GovernanceCognitionInvoker', $source);
        self::assertStringContainsString("'profile-elaboration'", $source);
        self::assertStringContainsString("'delegate-profile-elaboration'", $source);
    }

    public function testResolverOwnsOnlyTheTwoLaboratoriumElaborationAuthorities(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Laboratorium/LaboratoriumGovernanceCognitionAuthorityResolver.php');
        self::assertStringContainsString("['profile-elaboration', 'delegate-profile-elaboration']", $source);
        self::assertStringContainsString("'laboratorium.alchemist'", $source);
        self::assertStringContainsString("'elaborate-profile'", $source);
        self::assertStringContainsString('CanonicalJson::encode([$source, $context])', $source);
    }
}
