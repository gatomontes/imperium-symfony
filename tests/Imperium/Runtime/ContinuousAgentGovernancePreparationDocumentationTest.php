<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ContinuousAgentGovernancePreparationDocumentationTest extends TestCase
{
    public function testEveryTodoRequirementHasExactlyOnePreparationClassification(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/continuous-agent-governance-controls-preparation-inventory.md');

        for ($number = 1; $number <= 56; ++$number) {
            $id = sprintf('CAG-%02d', $number);
            self::assertSame(1, substr_count($inventory, '| '.$id.' |'), $id.' must occur exactly once.');
        }

        preg_match_all('/\| CAG-\d{2} \|[^\n]+\| `(EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY)` \|/', $inventory, $matches);
        self::assertCount(56, $matches[0]);
        self::assertStringContainsString('The actual next implementation boundary is Batch 1 only.', $inventory);
        self::assertStringContainsString('This document changes no runtime behavior', $inventory);
    }
}
