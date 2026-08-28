<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Senate\DelegateMissionDeliberationOpeningService;
use App\Imperium\Runtime\Senate\DelegateMissionSenateAuthorityTransition;
use PHPUnit\Framework\TestCase;

final class DelegateMissionSenateAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-delegate-senate-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsOneExactAuthorityConsumptionAndRejectsEnvelopeDivergence(): void
    {
        $id = 'delegate-mission-profile-examination-deliberation-opening-'.str_repeat('a', 20);
        $record = $this->record($id);
        $sealed = DelegateMissionSenateAuthorityTransition::seal($id, $record, DelegateMissionDeliberationOpeningService::class);
        $transaction = $sealed['transactional_consumption'];

        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame([$record['deliberation_opening_authority']['id']], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame('delegate-senate-authority:'.hash('sha256', $record['deliberation_opening_authority']['id']), $transaction['lock_plan'][0]['scope']);
        self::assertSame(DelegateMissionDeliberationOpeningService::class, $transaction['consumer']['competent_service']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertTrue(DelegateMissionSenateAuthorityTransition::isExactOrHistorical($sealed));

        $sealed['transactional_consumption']['lock_plan'][0]['scope'] = 'invented';
        self::assertFalse(DelegateMissionSenateAuthorityTransition::isExactOrHistorical($sealed));
        self::assertTrue(DelegateMissionSenateAuthorityTransition::isExactOrHistorical($record));
    }

    public function testFaultAfterImmutableCommitRecoversOnlyTheExactResult(): void
    {
        $directory = $this->root.'/var/imperium/offices/senate/delegate-mission-profile-examination-deliberation-openings';
        $id = 'delegate-mission-profile-examination-deliberation-opening-'.str_repeat('a', 20);
        $record = $this->record($id);

        try {
            DelegateMissionSenateAuthorityTransition::run($directory, $record['deliberation_opening_authority']['id'], function () use ($directory, $id, $record): void {
                DelegateMissionSenateAuthorityTransition::put($directory, $id, $record, DelegateMissionDeliberationOpeningService::class, 'WRITE_FAILED', 'CONFLICT');
                throw new \RuntimeException('TEST_FAULT_AFTER_RESULT_COMMITTED');
            });
            self::fail('The selected fault was not injected.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_FAULT_AFTER_RESULT_COMMITTED', $exception->getMessage());
        }

        $recovered = DelegateMissionSenateAuthorityTransition::run(
            $directory,
            $record['deliberation_opening_authority']['id'],
            fn (): array => DelegateMissionSenateAuthorityTransition::put($directory, $id, $record, DelegateMissionDeliberationOpeningService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($id, $recovered['deliberation_id']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertCount(1, glob($directory.'/*.json') ?: []);
    }

    public function testTwoProcessesConvergeBeforeCompetingResultsCanCommit(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $authorityId = 'delegate-mission-deliberation-opening-authority-'.str_repeat('e', 20);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/delegate-senate-authority-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        foreach (['a', 'f'] as $index => $contender) {
            $processes[$index] = proc_open([PHP_BINARY, $worker, $this->root, $authorityId, $gate, $contender], $descriptors, $pipes[$index]);
            self::assertIsResource($processes[$index]);
        }
        touch($gate);
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
        self::assertCount(1, glob($this->root.'/var/imperium/offices/senate/delegate-mission-profile-examination-deliberation-openings/*.json') ?: []);
    }

    private function record(string $id): array
    {
        return [
            'schema' => 'imperium.senate-delegate-mission-profile-examination-deliberation-opening/v1',
            'deliberation_id' => $id,
            'instance_id' => 'imperium-test',
            'source_finding_readiness' => ['id' => 'delegate-mission-profile-examination-finding-readiness-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
            'lord_speaker' => ['seat' => 'senate.lord-speaker', 'binding_id' => 'senate-lord-speaker-binding-'.str_repeat('d', 20)],
            'deliberation_opening_authority' => ['id' => 'delegate-mission-deliberation-opening-authority-'.str_repeat('e', 20), 'consumed' => true, 'continuing_authority' => false],
            'opened_at' => '2026-08-28T12:00:00+00:00',
            'status' => 'DELEGATE_MISSION_DELIBERATION_OPENED_PENDING_FINDING_RECONCILIATION',
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
