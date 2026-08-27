<?php declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SenatePersonaReconciliationOpeningBoundaryTest extends TestCase
{
    public function testOpeningAdmitsFourFindingsWithoutCognitionOrDisposition(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaReconciliationOpeningService.php');
        $command = (string) file_get_contents($root.'/src/Command/SenateOpenSubordinatePersonaReconciliationCommand.php');
        foreach (['practice', 'governance', 'consistency', 'security'] as $jurisdiction) self::assertStringContainsString("'".$jurisdiction."'", $service);
        self::assertStringContainsString("'admitted_findings' => \$admitted", $service);
        self::assertStringContainsString('RECONCILE_FOUR_UNCHANGED_PERSONA_FINDINGS', $service);
        self::assertStringContainsString("'authority_single_use' => true", $service);
        self::assertStringContainsString("'security_block_must_be_preserved' => true", $service);
        self::assertStringContainsString("'voting_included' => false", $service);
        self::assertStringContainsString("'aggregation_included' => false", $service);
        self::assertStringContainsString('PERSONA_FINDINGS_ADMITTED_UNCHANGED_RECONCILIATION_AUTHORITY_OPENED', $service.$command);
        self::assertStringNotContainsString('CognitionGateway', $service);
        self::assertStringNotContainsString('->reconcile(', $service);
        self::assertStringContainsString("'senate_disposition_authority' => false", $service);
        self::assertStringContainsString("'admission_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
    }
}
