<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionBoundaryRedesignBatch9Test extends TestCase
{
    public function testAssuranceReclassificationAdmitsOnlyProvenPreProviderEvidence(): void
    {
        $document = (string) file_get_contents(
            dirname(__DIR__, 3)
            .'/docs/provider-execution-assurance-redesigned-corridor-resumption.md',
        );

        foreach ([
            'BATCH_9_PROVIDER_EXECUTION_ASSURANCE_RESUMED_PRE_PROVIDER_ONLY',
            'SAME_PROCESS_GOVERNED_EXECUTOR',
            'EXISTS_CANONICALLY',
            'EXISTS_FRAGMENTED',
            'ABSENT',
            'REFUSED',
            'DEFERRED_BOUNDARY',
            'stationary credential resolution',
            'atomic authority consumption and effect-start ordering',
            'credential secret exclusion',
            'UNKNOWN_REPLAY_PROHIBITED',
            'TRUSTED_WRITER_CANONICAL_INTEGRITY',
        ] as $evidence) {
            self::assertNotFalse(stripos($document, $evidence), $evidence);
        }
    }

    public function testCrossProcessRefusalIsScopedRatherThanErased(): void
    {
        $document = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                dirname(__DIR__, 3)
                .'/docs/provider-execution-assurance-redesigned-corridor-resumption.md',
            ),
        );

        foreach ([
            'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE',
            'remains true for every posture that transfers',
            'no cross-process credential capability at all',
            'not a claim that cross-process custody became provable',
        ] as $boundary) {
            self::assertNotFalse(stripos($document, $boundary), $boundary);
        }
    }

    public function testProviderExecutionRemainsRefusedAtExactStopConditions(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/provider-execution-assurance-redesigned-corridor-resumption.md',
            ),
        );
        $handoff = preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(
                $root.'/docs/handoffs/provider-execution-boundary-redesign-batch-9-complete.md',
            ),
        );

        foreach ([
            'ATTESTED_INERT',
            'BOUND_INACTIVE',
            'no live-call runtime contract',
            'in-progress duplicate',
            'query-before-retry',
            'remote response authorship',
            'does not authorize provider execution',
            'did not activate a principal or binding',
            'issue or consume authority',
            'invoke a provider',
            'external I/O',
            'Iron Gate',
            'Lazaretto',
            'Only Batch 10 may next be considered',
        ] as $stop) {
            self::assertNotFalse(stripos($document.$handoff, $stop), $stop);
        }
    }

    public function testResumptionCreatesNoRuntimeSurface(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertFileDoesNotExist(
            $root.'/src/Imperium/Runtime/Provider/ProviderExecutionAssuranceService.php',
        );
        self::assertFileDoesNotExist(
            $root.'/src/Imperium/Runtime/Provider/ProviderLiveCallContract.php',
        );
    }
}
