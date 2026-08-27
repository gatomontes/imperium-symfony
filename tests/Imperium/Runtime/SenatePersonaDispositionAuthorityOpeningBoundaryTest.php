<?php declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use PHPUnit\Framework\TestCase;
final class SenatePersonaDispositionAuthorityOpeningBoundaryTest extends TestCase
{
    public function testOpeningIsMechanicalSecurityPreservingAndStopsBeforeDisposition(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaDispositionAuthorityOpeningService.php');
        self::assertStringNotContainsString('CognitionGateway', $service);
        self::assertStringNotContainsString('AgentInterface', $service);
        self::assertStringContainsString("['practice', 'governance', 'consistency', 'security']", $service);
        self::assertStringContainsString('OPEN_ONE_PERSONA_SENATE_DISPOSITION_AUTHORITY', $service);
        self::assertStringContainsString("['CONFIRMED', 'RETURN_TO_FOUNDRY', 'REFUSED', 'UNRESOLVED']", $service);
        self::assertStringContainsString("'security_block_must_be_preserved' => true", $service);
        self::assertStringContainsString("'PERSONA_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION'", $service);
        self::assertStringContainsString("'senate_disposition_authority' => true", $service);
        self::assertStringContainsString("'senate_disposition' => null", $service);
        self::assertStringContainsString("'admission_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
    }
}
