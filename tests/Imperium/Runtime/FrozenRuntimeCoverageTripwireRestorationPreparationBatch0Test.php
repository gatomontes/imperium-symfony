<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class FrozenRuntimeCoverageTripwireRestorationPreparationBatch0Test extends TestCase
{
    public function testInventoryNamesEveryObservedTripwireRegression(): void
    {
        $inventory = $this->document('docs/frozen-runtime-coverage-tripwire-restoration-preparation-inventory.md');
        foreach ([
            'REGRESSED_ONE_WAY_SUBSET_CHECK', 'REMOVED_COARSE_TRIPWIRE',
            'REGRESSED_EXPECTED_SUBSET_ONLY', 'REGRESSED_SNAPSHOT_LIMITED_SCAN',
            'PARTIALLY_REGRESSED', 'exact bidirectional path-set comparisons',
            'complete live perimeter', 'Silent regeneration is forbidden',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testBatch1SourceRestoresTheClassifiedTripwires(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/tests/Imperium/Runtime/TransactionalAuthorityConsumptionBatch12CoverageTest.php',
        );
        self::assertStringContainsString('assertSame($expectedCandidates, $candidates)', $source);
        self::assertStringContainsString('assertSame($expectedStoreUsers, $storeUsers)', $source);
        self::assertStringContainsString('assertSame($expectedPerimeter, $perimeter)', $source);
        self::assertStringContainsString('foreach (array_merge($perimeter', $source);
        self::assertStringContainsString(
            'frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv',
            $source,
        );
    }

    public function testBatchBoundaryAuthorizesOnlyTripwireRestoration(): void
    {
        $handoff = $this->document(
            'docs/handoffs/frozen-runtime-coverage-tripwire-restoration-preparation-batch-0-complete.md',
        );
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_FROZEN_RUNTIME_TRIPWIRE_REGRESSION_CLASSIFIED',
            'Only Frozen Runtime Coverage Tripwire Restoration Batch 1',
            'may modify the coverage test and versioned inventory documentation only',
            'without changing runtime behavior', 'remains Batch 2',
            'may not restore evidence closure', 'Estimated campaign countdown after Preparation Batch 0: three batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
