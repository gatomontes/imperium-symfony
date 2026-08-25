<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use PHPUnit\Framework\TestCase;

final class TransactionalPersistencePrimitivesTest extends TestCase
{
    private string $root;
    private AtomicTransition $atomic;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-persistence-'.bin2hex(random_bytes(5));
        $this->atomic = new AtomicTransition($this->root);
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testImmutableExactReplayReturnsOneRecord(): void
    {
        $firstStore = new ImmutableRecordStore($this->root, $this->atomic);
        $secondStore = new ImmutableRecordStore($this->root, new AtomicTransition($this->root));
        $record = ['schema' => 'test/v1', 'id' => 'record-1', 'sealed' => true];

        $first = $firstStore->put('var/imperium/test/records', 'record-1', $record);
        $second = $secondStore->put('var/imperium/test/records', 'record-1', $record);

        self::assertSame($first, $second);
        self::assertCount(1, glob($this->root.'/var/imperium/test/records/*.json') ?: []);
    }

    public function testImmutableConflictingReplayFailsStopped(): void
    {
        $store = new ImmutableRecordStore($this->root, $this->atomic);
        $store->put('var/imperium/test/records', 'record-1', ['schema' => 'test/v1', 'value' => 'first']);

        $this->expectExceptionMessage('PST111_IMMUTABLE_RECORD_CONFLICT');
        $store->put('var/imperium/test/records', 'record-1', ['schema' => 'test/v1', 'value' => 'changed']);
    }

    public function testMutableCompareAndSwapRejectsStaleWriter(): void
    {
        $store = new MutableStateStore($this->root, $this->atomic);
        $initial = $store->compareAndSwap('var/imperium/test/state.json', null, ['generation' => 1, 'state' => 'OPEN']);
        $winner = $store->compareAndSwap('var/imperium/test/state.json', $initial['record_digest'], ['generation' => 2, 'state' => 'CLOSED']);

        self::assertSame(2, $winner['generation']);
        $this->expectExceptionMessage('PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT');
        $store->compareAndSwap('var/imperium/test/state.json', $initial['record_digest'], ['generation' => 2, 'state' => 'OTHER']);
    }

    public function testTamperedMutableStateCannotParticipateInTransition(): void
    {
        $store = new MutableStateStore($this->root, $this->atomic);
        $initial = $store->compareAndSwap('var/imperium/test/state.json', null, ['generation' => 1]);
        $path = $this->root.'/var/imperium/test/state.json';
        $tampered = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $tampered['generation'] = 999;
        file_put_contents($path, json_encode($tampered, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('PST123_MUTABLE_STATE_TAMPERED');
        $store->compareAndSwap('var/imperium/test/state.json', $initial['record_digest'], ['generation' => 2]);
    }

    public function testAuthorityConsumptionHasOneExactWinner(): void
    {
        $records = new ImmutableRecordStore($this->root, $this->atomic);
        $store = new AuthorityConsumptionStore($records, $this->atomic);
        $at = new \DateTimeImmutable('2026-08-25T14:00:00+00:00');
        $first = $store->consume('authority-1', 'source-1', str_repeat('a', 64), 'clavium.locksmith', $at);
        $replay = $store->consume('authority-1', 'source-1', str_repeat('a', 64), 'clavium.locksmith', $at->modify('+1 minute'));

        self::assertSame($first, $replay);
        self::assertTrue($first['consumed']);
        self::assertFalse($first['continuing_authority']);

        $this->expectExceptionMessage('PST131_AUTHORITY_CONSUMPTION_CONFLICT');
        $store->consume('authority-1', 'source-2', str_repeat('b', 64), 'another.consumer', $at);
    }

    public function testAtomicTransitionReleasesLockAfterException(): void
    {
        try {
            $this->atomic->run('test:recoverable-transition', static function (): void {
                throw new \RuntimeException('injected');
            });
        } catch (\RuntimeException $exception) {
            self::assertSame('injected', $exception->getMessage());
        }

        self::assertSame('recovered', $this->atomic->run('test:recoverable-transition', static fn (): string => 'recovered'));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
