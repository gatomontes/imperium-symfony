<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary and structural preparation evidence only; no runtime service is executed. */
final class NativeInspectionSnapshotConsistencyPreparationBatch0Test extends TestCase
{
    private const string PREFIX = 'native-inspection-snapshot-consistency';
    private const string MARKER = 'NATIVE_INSPECTION_SNAPSHOT_CONSISTENCY_PREPARATION_BATCH_0_COMPLETE';

    public function testLedgerPinsTheRequiredDocumentsAndCompleteTracingSources(): void
    {
        $ledger = json_decode($this->read('docs/'.self::PREFIX.'-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('aff1017f456b35110d0e64b07cf6e89990d71cc0', $ledger['baseline']);
        self::assertGreaterThanOrEqual(55, count($ledger['sources']));
        $successor = json_decode($this->read('docs/'.self::PREFIX.'-terminal-reading-ledger-v1.json'), true, 512, JSON_THROW_ON_ERROR);
        $reviewedSuccessors = array_column($successor['sources'], null, 'path');
        $paths = [];
        foreach ($ledger['sources'] as [$path, $role, $status, $hash, $lines]) {
            self::assertNotContains($path, $paths);
            $paths[] = $path;
            self::assertSame('FULLY_READ', $status, $path);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $hash, $path);
            self::assertGreaterThan(0, $lines, $path);
            self::assertNotSame('', $role);
            $actual = hash('sha256', $this->read($path));
            if ($hash !== $actual) {
                self::assertArrayHasKey($path, $reviewedSuccessors, $path.' changed without terminal successor review');
                self::assertSame($hash, $reviewedSuccessors[$path]['predecessor_sha256']);
                self::assertSame($actual, $reviewedSuccessors[$path]['normalized_sha256']);
            }
        }
        foreach ([
            'docs/next-campaign-native-inspection-snapshot-consistency.md',
            'docs/executable-atomic-transition-canonical-consumer-integration-correction-reading-ledger-v1.json',
            'docs/executable-atomic-transition-canonical-consumer-integration-correction-reading-ledger-v2.json',
            'docs/executable-atomic-transition-canonical-consumer-integration-correction-reading-ledger-v3.json',
            'src/Imperium/Runtime/ProviderTransition/NativeBindingReader.php',
            'src/Imperium/Runtime/ProviderTransition/NativeReconstructor.php',
            'src/Imperium/Runtime/ProviderTransition/NativeState.php',
            'src/Imperium/Runtime/Persistence/AtomicTransition.php',
            'src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php',
            'src/Command/AgentMailEmailSendCommand.php',
            'tests/fixtures/native-transition-worker.php',
        ] as $required) {
            self::assertContains($required, $paths);
        }
    }

    public function testInventoryClassifiesEveryRequiredSurfaceAndSelectsTheBoundedSnapshot(): void
    {
        $inventory = $this->read('docs/'.self::PREFIX.'-preparation-inventory-v1.md');
        foreach (['C' => 14, 'R' => 16, 'P' => 11, 'L' => 9, 'T' => 10] as $prefix => $count) {
            preg_match_all('/^\| ('.$prefix.'\d{2}) \|.*$/m', $inventory, $rows);
            self::assertCount($count, $rows[0], $prefix);
            self::assertCount($count, array_unique($rows[1]), $prefix);
            foreach ($rows[0] as $row) {
                self::assertMatchesRegularExpression('/\| (EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY) \|/', $row);
            }
        }
        foreach (['optimistic coherent snapshot with bounded conservative refusal', 'at most two attempts',
            'not execution retry or recovery authorization', 'Five stages remain',
            'Batch 1 — define', 'Batch 5 — conduct', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED',
            'UNKNOWN_REPLAY_PROHIBITED', 'bounded pre-effect'] as $claim) {
            self::assertStringContainsStringIgnoringCase($claim, $inventory, $claim);
        }
    }

    public function testCallerAndLockGraphClaimsMatchTheUnchangedRuntime(): void
    {
        $reader = $this->read('src/Imperium/Runtime/ProviderTransition/NativeBindingReader.php');
        foreach (['public function interpret(', 'public function forClaim(', 'public function forJournal(',
            'public function read(', "run('native-provider-transition'", 'private static array $legacyScopes'] as $edge) {
            self::assertStringContainsString($edge, $reader);
        }
        $broker = $this->read('src/Imperium/Runtime/Clavium/DeterministicJournalBoundCredentialBroker.php');
        $outer = strpos($broker, 'return $this->bindingReader->legacy(');
        $inspection = strpos($broker, '$interpretation = $this->inspectClaim(');
        $credentials = strpos($broker, '$this->credentials->consume(');
        self::assertNotFalse($outer);
        self::assertNotFalse($inspection);
        self::assertNotFalse($credentials);
        self::assertLessThan($credentials, $inspection);
        $state = $this->read('src/Imperium/Runtime/ProviderTransition/NativeState.php');
        self::assertStringContainsString("run('native-provider-transition'", $state);
        self::assertStringContainsString("'immutable:'", $state);
        self::assertStringContainsString('sort($scopes);', $state);
        $atomic = $this->read('src/Imperium/Runtime/Persistence/AtomicTransition.php');
        self::assertStringContainsString('mkdir($this->locks', $atomic);
        self::assertStringContainsString("fopen(\$path, 'c+')", $atomic);
        self::assertStringContainsString('flock($handle, LOCK_EX)', $atomic);
    }

    public function testRaceMatrixRequiresSeparateProcessesAndPreservesNoEffectBoundary(): void
    {
        $matrix = $this->read('docs/'.self::PREFIX.'-race-matrix-v1.md');
        preg_match_all('/^\| (X\d{2}) \|.*$/m', $matrix, $rows);
        self::assertCount(23, $rows[0]);
        self::assertSame(array_map(static fn (int $i): string => sprintf('X%02d', $i), range(0, 22)), $rows[1]);
        foreach (['separate PHP processes', 'manifest A', 'manifest B', 'before-publish',
            'terminate the inspector', 'at most two attempts', 'zero file delta',
            'credential', 'provider', 'Iron Gate', 'Lazaretto', 'external I/O'] as $requirement) {
            self::assertStringContainsStringIgnoringCase($requirement, $matrix, $requirement);
        }
    }

    public function testCompletionHandoffStopsAfterPreparationBatchZero(): void
    {
        $handoff = $this->read('docs/handoffs/'.self::PREFIX.'-preparation-batch-0-complete.md');
        foreach ([self::MARKER, 'OPTIMISTIC_WHOLE_READ_SET_WITH_BOUNDED_REFUSAL_SELECTED',
            'No runtime behavior or production wiring changed', 'Five stages remain',
            'No later stage is authorized', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED',
            'UNKNOWN_REPLAY_PROHIBITED',
            'php vendor/bin/phpunit tests/Imperium/Runtime/NativeInspectionSnapshotConsistencyPreparationBatch0Test.php'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
