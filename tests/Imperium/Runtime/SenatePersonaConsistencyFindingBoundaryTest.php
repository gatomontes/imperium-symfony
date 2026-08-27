<?php declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SenatePersonaConsistencyFindingBoundaryTest extends TestCase
{
    public function testConsistencyRequiresPracticeAndGovernanceAndStopsBeforeSecurity(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaConsistencyFindingService.php');
        $command = (string) file_get_contents($root.'/src/Command/SenateIssueSubordinatePersonaConsistencyFindingCommand.php');
        self::assertStringContainsString("\$this->cognition->find('consistency'", $service);
        self::assertStringContainsString('PRACTICE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service);
        self::assertStringContainsString('GOVERNANCE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service);
        self::assertStringContainsString("'fresh_consistency_trial' => \$freshTrial", $service);
        self::assertStringContainsString("'fresh-consistency:'", $service);
        self::assertStringContainsString("'remaining_jurisdictions' => ['security']", $service);
        self::assertStringContainsString('CONSISTENCY_FINDING_SEALED_PENDING_SECURITY_FINDING', $service.$command);
        self::assertStringContainsString("'senate_disposition_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
        self::assertStringNotContainsString("->find('security'", $service);
    }
}
