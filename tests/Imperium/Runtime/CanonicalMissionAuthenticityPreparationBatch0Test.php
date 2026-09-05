<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalMissionAuthenticityPreparationBatch0Test extends TestCase
{
    public function testInventoryFreezesEntryAuthorityBoundaryAndAdverseEvidenceClassifications(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-mission-authenticity-preparation-batch-0-inventory.md');

        foreach ([
            'b267e2c2b6a122694418ce59d2bf16319e602b07',
            '2527b33925bf3ef47d029786e60a6aefe752737b',
            '3c4890ffd30f403f72a35b92f1e639d51c8c98f8',
            'RECOVERABLE_SHAPE',
            'AUTHORITY_COUNTERFEIT',
            'SIMULATED_EVIDENCE',
            'PROCESS_LOCAL_ONLY',
            'REIMPLEMENT_REQUIRED',
            'persisted Mission Authorization identifier',
            'real independent PHP processes',
        ] as $required) {
            self::assertStringContainsString($required, $inventory);
        }
    }

    public function testInventoryPreservesTheMandatoryOperatorGate(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-mission-authenticity-preparation-batch-0-inventory.md');

        self::assertStringContainsString('Preparation Batch 0 and Batches 1–3 only', $inventory);
        self::assertStringContainsString('Reference mission | Not authorized', $inventory);
        self::assertStringContainsString('no execution in Batches 0–3', $inventory);
    }
}
