<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\DelegateMissionModelCriteriaRequestService;
use App\Imperium\Runtime\Curia\DelegateMissionModelGovernanceAuthorityTransition;
use PHPUnit\Framework\TestCase;

final class DelegateMissionModelGovernanceAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-delegate-model-governance-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsAndValidatesOneExactDelegateModelGovernanceAuthority(): void
    {
        $id = 'delegate-mission-model-criteria-request-'.str_repeat('a', 20);
        $record = $this->record($id);
        $sealed = DelegateMissionModelGovernanceAuthorityTransition::seal($id, $record, DelegateMissionModelCriteriaRequestService::class);
        $transaction = $sealed['transactional_consumption'];

        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame([$record['criteria_proposal_authority']['id']], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame('delegate-model-governance-authority:'.hash('sha256', $record['criteria_proposal_authority']['id']), $transaction['lock_plan'][0]['scope']);
        self::assertSame(DelegateMissionModelCriteriaRequestService::class, $transaction['consumer']['competent_service']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertTrue(DelegateMissionModelGovernanceAuthorityTransition::isExactOrHistorical($sealed));

        $sealed['transactional_consumption']['consumer']['competent_service'] = self::class;
        self::assertFalse(DelegateMissionModelGovernanceAuthorityTransition::isExactOrHistorical($sealed));
        self::assertTrue(DelegateMissionModelGovernanceAuthorityTransition::isExactOrHistorical($record));
    }

    public function testFaultAfterImmutableCommitRecoversTheExactResult(): void
    {
        $directory = $this->directory();
        $id = 'delegate-mission-model-criteria-request-'.str_repeat('a', 20);
        $record = $this->record($id);

        try {
            DelegateMissionModelGovernanceAuthorityTransition::run($directory, $record['criteria_proposal_authority']['id'], function () use ($directory, $id, $record): void {
                DelegateMissionModelGovernanceAuthorityTransition::put($directory, $id, $record, DelegateMissionModelCriteriaRequestService::class, 'WRITE_FAILED', 'CONFLICT');
                throw new \RuntimeException('TEST_FAULT_AFTER_RESULT_COMMITTED');
            });
            self::fail('The selected fault was not injected.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_FAULT_AFTER_RESULT_COMMITTED', $exception->getMessage());
        }

        $recovered = DelegateMissionModelGovernanceAuthorityTransition::run(
            $directory,
            $record['criteria_proposal_authority']['id'],
            fn (): array => DelegateMissionModelGovernanceAuthorityTransition::put($directory, $id, $record, DelegateMissionModelCriteriaRequestService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($id, $recovered['request_id']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertCount(1, glob($directory.'/*.json') ?: []);
    }

    public function testExactHistoricalResultReplaysWithoutRewrite(): void
    {
        $directory = $this->directory();
        self::assertTrue(mkdir($directory, 0770, true));
        $id = 'delegate-mission-model-criteria-request-'.str_repeat('a', 20);
        $record = $this->record($id);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        self::assertNotFalse(file_put_contents($directory.'/'.$id.'.json', json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX));

        $replayed = DelegateMissionModelGovernanceAuthorityTransition::run(
            $directory,
            $record['criteria_proposal_authority']['id'],
            fn (): array => DelegateMissionModelGovernanceAuthorityTransition::put($directory, $id, $record, DelegateMissionModelCriteriaRequestService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($record, $replayed);
        self::assertArrayNotHasKey('transactional_consumption', $replayed);
    }

    public function testTwoProcessesConvergeBeforeCompetingResultsCanCommit(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $authorityId = 'delegate-mission-model-criteria-authority-'.str_repeat('e', 20);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/delegate-model-governance-authority-contender.php';
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
        return $this->root.'/var/imperium/offices/curia/delegate-mission-model-criteria-requests';
    }

    private function record(string $id): array
    {
        return [
            'schema' => 'imperium.curia-delegate-mission-model-criteria-request/v1',
            'request_id' => $id,
            'instance_id' => 'imperium-test',
            'requester' => ['seat' => 'curia.seneschal', 'binding_id' => 'curia-seneschal-binding-'.str_repeat('d', 20)],
            'source_readiness' => ['id' => 'delegate-mission-resource-invocation-readiness-assessment-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
            'criteria_proposal_authority' => ['id' => 'delegate-mission-model-criteria-authority-'.str_repeat('e', 20), 'consumed' => true, 'continuing_authority' => false],
            'proposed_criteria' => ['cognitive_task' => 'Bounded Delegate turn.'],
            'presented_at' => '2026-08-28T16:00:00+00:00',
            'status' => 'DELEGATE_MISSION_MODEL_CRITERIA_PRESENTED_PENDING_IMPERATOR_DECISION',
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
