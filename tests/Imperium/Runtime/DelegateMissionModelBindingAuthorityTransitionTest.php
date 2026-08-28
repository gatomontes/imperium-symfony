<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\DelegateMissionModelBindingAuthorityTransition;
use App\Imperium\Runtime\Conscription\DelegateMissionModelBindingSealingService;
use PHPUnit\Framework\TestCase;

final class DelegateMissionModelBindingAuthorityTransitionTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-delegate-model-binding-transition-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testSealsAndValidatesOneExactModelBindingAuthority(): void
    {
        $id = 'delegate-mission-model-binding-'.str_repeat('a', 20);
        $record = $this->record($id);
        $sealed = DelegateMissionModelBindingAuthorityTransition::seal($id, $record, DelegateMissionModelBindingSealingService::class);
        $transaction = $sealed['transactional_consumption'];

        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame([$record['binding_authority']['id']], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame('delegate-model-binding-authority:'.hash('sha256', $record['binding_authority']['id']), $transaction['lock_plan'][0]['scope']);
        self::assertSame(DelegateMissionModelBindingSealingService::class, $transaction['consumer']['competent_service']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertTrue(DelegateMissionModelBindingAuthorityTransition::isExactOrHistorical($sealed));

        $sealed['transactional_consumption']['consumer']['competent_service'] = self::class;
        self::assertFalse(DelegateMissionModelBindingAuthorityTransition::isExactOrHistorical($sealed));
        self::assertTrue(DelegateMissionModelBindingAuthorityTransition::isExactOrHistorical($record));
    }

    public function testFaultAfterImmutableCommitRecoversTheExactResult(): void
    {
        $directory = $this->directory();
        $id = 'delegate-mission-model-binding-'.str_repeat('a', 20);
        $record = $this->record($id);

        try {
            DelegateMissionModelBindingAuthorityTransition::run($directory, $record['binding_authority']['id'], function () use ($directory, $id, $record): void {
                DelegateMissionModelBindingAuthorityTransition::put($directory, $id, $record, DelegateMissionModelBindingSealingService::class, 'WRITE_FAILED', 'CONFLICT');
                throw new \RuntimeException('TEST_FAULT_AFTER_RESULT_COMMITTED');
            });
            self::fail('The selected fault was not injected.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_FAULT_AFTER_RESULT_COMMITTED', $exception->getMessage());
        }

        $recovered = DelegateMissionModelBindingAuthorityTransition::run(
            $directory,
            $record['binding_authority']['id'],
            fn (): array => DelegateMissionModelBindingAuthorityTransition::put($directory, $id, $record, DelegateMissionModelBindingSealingService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($id, $recovered['binding_id']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertCount(1, glob($directory.'/*.json') ?: []);
    }

    public function testExactHistoricalResultReplaysWithoutRewrite(): void
    {
        $directory = $this->directory();
        self::assertTrue(mkdir($directory, 0770, true));
        $id = 'delegate-mission-model-binding-'.str_repeat('a', 20);
        $record = $this->record($id);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        self::assertNotFalse(file_put_contents($directory.'/'.$id.'.json', json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX));

        $replayed = DelegateMissionModelBindingAuthorityTransition::run(
            $directory,
            $record['binding_authority']['id'],
            fn (): array => DelegateMissionModelBindingAuthorityTransition::put($directory, $id, $record, DelegateMissionModelBindingSealingService::class, 'WRITE_FAILED', 'CONFLICT'),
        );

        self::assertSame($record, $replayed);
        self::assertArrayNotHasKey('transactional_consumption', $replayed);
    }

    public function testTwoProcessesConvergeBeforeCompetingBindingsCanCommit(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $authorityId = 'delegate-mission-model-binding-sealing-authority-'.str_repeat('e', 20);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/delegate-model-binding-authority-contender.php';
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
        return $this->root.'/var/imperium/offices/conscription/delegate-mission-model-bindings';
    }

    private function record(string $id): array
    {
        return [
            'schema' => 'imperium.conscription-delegate-mission-model-binding/v1',
            'binding_id' => $id,
            'instance_id' => 'imperium-test',
            'binder' => ['seat' => 'conscription.recruiter', 'manifestation_id' => 'manifestation-test', 'occupancy_generation' => 1],
            'source_selection_decision' => ['id' => 'delegate-mission-model-selection-decision-'.str_repeat('b', 20), 'digest' => str_repeat('c', 64)],
            'binding_authority' => ['id' => 'delegate-mission-model-binding-sealing-authority-'.str_repeat('e', 20), 'consumed' => true, 'continuing_authority' => false],
            'sealed_at' => '2026-08-28T17:00:00+00:00',
            'status' => 'DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION',
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
