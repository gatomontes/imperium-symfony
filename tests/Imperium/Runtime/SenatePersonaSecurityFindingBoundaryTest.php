<?php declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SenatePersonaSecurityFindingBoundaryTest extends TestCase
{
    public function testSecurityRequiresThreeFindingsAndStopsBeforeReconciliation(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaSecurityFindingService.php');
        $command = (string) file_get_contents($root.'/src/Command/SenateIssueSubordinatePersonaSecurityFindingCommand.php');
        self::assertStringContainsString("\$this->cognition->find('security'", $service);
        self::assertStringContainsString('PRACTICE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service);
        self::assertStringContainsString('GOVERNANCE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service);
        self::assertStringContainsString('CONSISTENCY_FINDING_SEALED_PENDING_SECURITY_FINDING', $service);
        self::assertStringContainsString("'pressure:security:'", $service);
        self::assertStringContainsString("'synthetic_material_only'", $service);
        self::assertStringContainsString("true === \$decision['mandatory_failure']", $service);
        self::assertStringContainsString("'FAIL' !==", $service);
        self::assertStringContainsString("'CRITICAL' !==", $service);
        self::assertStringContainsString("'remaining_jurisdictions' => []", $service);
        self::assertStringContainsString('SECURITY_FINDING_SEALED_PENDING_SEPARATE_RECONCILIATION', $service.$command);
        self::assertStringContainsString("'reconciliation_authority' => false", $service);
        self::assertStringContainsString("'senate_disposition_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
    }
}
