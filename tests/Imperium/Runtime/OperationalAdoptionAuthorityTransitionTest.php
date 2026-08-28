<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalAdoptionAuthorityTransition;
use App\Imperium\Runtime\Curia\OperationalAdoptionReconciliationService;
use PHPUnit\Framework\TestCase;

final class OperationalAdoptionAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-adoption-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsAndValidatesOneExactOperationalAdoptionAuthority(): void
    {
        $id = 'legate-result-adoption-reconciliation-'.str_repeat('a', 20);
        $record = $this->record($id);
        $sealed = OperationalAdoptionAuthorityTransition::seal($id, $record, OperationalAdoptionReconciliationService::class);
        $transaction = $sealed['transactional_consumption'];

        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame([$record['reconciliation_authority']['id']], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame('operational-adoption-authority:'.hash('sha256', $record['reconciliation_authority']['id']), $transaction['lock_plan'][0]['scope']);
        self::assertSame(OperationalAdoptionReconciliationService::class, $transaction['consumer']['competent_service']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertTrue(OperationalAdoptionAuthorityTransition::isExactOrHistorical($sealed));

        $sealed['transactional_consumption']['consumer']['competent_service'] = self::class;
        self::assertFalse(OperationalAdoptionAuthorityTransition::isExactOrHistorical($sealed));
        self::assertTrue(OperationalAdoptionAuthorityTransition::isExactOrHistorical($record));
    }

    public function testFaultAfterImmutableCommitRecoversTheExactResult(): void
    {
        $directory = $this->directory();
        $id = 'legate-result-adoption-reconciliation-'.str_repeat('a', 20);
        $record = $this->record($id);

        try {
            OperationalAdoptionAuthorityTransition::run($directory, $record['reconciliation_authority']['id'], function () use ($directory, $id, $record): void {
                OperationalAdoptionAuthorityTransition::put($directory, $id, $record, OperationalAdoptionReconciliationService::class, 'WRITE_FAILED', 'CONFLICT');
                throw new \RuntimeException('TEST_FAULT_AFTER_RESULT_COMMITTED');
            });
            self::fail('The selected fault was not injected.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_FAULT_AFTER_RESULT_COMMITTED', $exception->getMessage());
        }

        $recovered = OperationalAdoptionAuthorityTransition::run(
            $directory,
            $record['reconciliation_authority']['id'],
            fn (): array => OperationalAdoptionAuthorityTransition::put($directory, $id, $record, OperationalAdoptionReconciliationService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($id, $recovered['reconciliation_id']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertCount(1, glob($directory.'/*.json') ?: []);
    }

    public function testExactHistoricalResultReplaysWithoutRewrite(): void
    {
        $directory = $this->directory();
        self::assertTrue(mkdir($directory, 0770, true));
        $id = 'legate-result-adoption-reconciliation-'.str_repeat('a', 20);
        $record = $this->record($id);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        self::assertNotFalse(file_put_contents($directory.'/'.$id.'.json', json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX));

        $replayed = OperationalAdoptionAuthorityTransition::run(
            $directory,
            $record['reconciliation_authority']['id'],
            fn (): array => OperationalAdoptionAuthorityTransition::put($directory, $id, $record, OperationalAdoptionReconciliationService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($record, $replayed);
        self::assertArrayNotHasKey('transactional_consumption', $replayed);
    }

    public function testTwoProcessesConvergeBeforeCompetingResultsCanCommit(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $authorityId = 'legate-result-adoption-reconciliation-authority-'.str_repeat('e', 20);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/operational-adoption-authority-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        foreach (['a', 'f'] as $index => $contender) {
            $processes[$index] = proc_open([PHP_BINARY, $worker, $this->root, $authorityId, $gate, $contender], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        self::assertTrue(is_dir($this->root) || mkdir($this->root, 0770, true));
        self::assertTrue(touch($gate));
        $results = [];
        for ($index = 0; $index < 2; ++$index) {
            $results[] = stream_get_contents($pipes[$index][1]);
            $errors = stream_get_contents($pipes[$index][2]);
            fclose($pipes[$index][1]);
            fclose($pipes[$index][2]);
            self::assertSame(0, proc_close($processes[$index]));
            self::assertSame('', $errors);
        }

        self::assertSame($results[0], $results[1]);
        self::assertCount(1, glob($this->directory().'/*.json') ?: []);
    }

    private function directory(): string
    {
        return $this->root.'/var/imperium/operational/legate-result-adoption-reconciliations';
    }

    private function record(string $id): array
    {
        return [
            'schema' => 'imperium.legate-result-adoption-reconciliation/v1',
            'reconciliation_id' => $id,
            'instance_id' => 'imperium-test',
            'source_reconciliation_opening' => ['id' => 'legate-result-adoption-reconciliation-opening-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
            'reconciler' => ['seat' => 'curia.seneschal', 'binding_id' => 'curia-seneschal-binding-'.str_repeat('d', 20)],
            'reconciliation_authority' => ['id' => 'legate-result-adoption-reconciliation-authority-'.str_repeat('e', 20), 'single_use' => true, 'consumed' => true, 'continuing_authority' => false],
            'reconciliation' => ['summary' => 'Exact bounded reconciliation.'],
            'reconciled_at' => '2026-08-28T15:00:00+00:00',
            'status' => 'ADOPTION_ASSESSMENTS_RECONCILED_NO_DISPOSITION_PENDING_ADOPTION_DECISION_OPENING',
            'sealed' => true,
        ];
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
