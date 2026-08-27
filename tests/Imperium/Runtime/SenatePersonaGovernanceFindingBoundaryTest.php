<?php declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class SenatePersonaGovernanceFindingBoundaryTest extends TestCase
{
    public function testGovernanceFindingRequiresPracticeAndStopsBeforeConsistency(): void
    {
        $root = dirname(__DIR__, 3);
        $service = (string) file_get_contents($root.'/src/Imperium/Runtime/Senate/SubordinatePersonaGovernanceFindingService.php');
        $command = (string) file_get_contents($root.'/src/Command/SenateIssueSubordinatePersonaGovernanceFindingCommand.php');

        self::assertStringContainsString("\$this->cognition->find('governance'", $service);
        self::assertStringContainsString('PRACTICE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service);
        self::assertStringContainsString("'pressure_trial' => \$pressure", $service);
        self::assertStringContainsString("'pressure:governance:'", $service);
        self::assertStringContainsString("'remaining_jurisdictions' => ['consistency', 'security']", $service);
        self::assertStringContainsString('GOVERNANCE_FINDING_SEALED_PENDING_REMAINING_FINDINGS', $service.$command);
        self::assertStringContainsString("'senate_disposition_authority' => false", $service);
        self::assertStringContainsString("'admission_authority' => false", $service);
        self::assertStringContainsString("'execution_authority' => false", $service);
        self::assertStringNotContainsString("->find('consistency'", $service);
        self::assertStringNotContainsString("->find('security'", $service);
    }
}
