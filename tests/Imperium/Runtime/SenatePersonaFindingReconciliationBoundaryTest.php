<?php declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\SenatePersonaReconciliationGovernanceCognitionAuthorityResolver;
use PHPUnit\Framework\TestCase;

final class SenatePersonaFindingReconciliationBoundaryTest extends TestCase
{
    public function testPersonaReconciliationIsClaimBoundAndStopsBeforeDisposition(): void
    {
        $root = dirname(__DIR__, 3);
        $resolver = new SenatePersonaReconciliationGovernanceCognitionAuthorityResolver(sys_get_temp_dir());
        self::assertTrue($resolver->supports('senate-persona-confirmation', 'reconciliation'));
        self::assertFalse($resolver->supports('senate-profile-examination', 'reconciliation'));
        $gateway = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SymfonyAiPersonaFindingReconciliationCognitionGateway.php');
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaFindingReconciliationService.php');
        $config = (string) file_get_contents($root.'/config/services.yaml');
        self::assertStringContainsString('GovernanceCognitionInvoker', $gateway);
        self::assertStringContainsString("'senate-persona-confirmation', 'reconciliation'", $gateway);
        self::assertStringNotContainsString('AgentInterface', $gateway);
        self::assertStringContainsString('SenatePersonaReconciliationGovernanceCognitionAuthorityResolver', $config);
        self::assertStringContainsString("\$existing = \$this->existing(\$openingId)", $service);
        self::assertStringContainsString("'/persona-findings/'", (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SenatePersonaReconciliationGovernanceCognitionAuthorityResolver.php'));
        self::assertStringContainsString("'mandatory_security_block_preserved' => true", $service);
        self::assertStringContainsString('OPEN_ONE_PERSONA_SENATE_DISPOSITION_AUTHORITY', $service);
        self::assertStringContainsString('PERSONA_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING', $service);
        self::assertStringContainsString("'senate_disposition_authority' => false", $service);
        self::assertStringContainsString("'admission_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
    }
}
