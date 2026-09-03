<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{NativeBindingReader, NativeConsumer, NativeState};
use App\Tests\Imperium\Runtime\Support\ConsumerProcess;

require_once __DIR__.'/NativeTransitionBatch4Test.php';
require_once __DIR__.'/Support/ConsumerProcess.php';

final class NativeInspectionSnapshotConsistencyBatch3Test extends NativeTransitionBatch4Test
{
    public function testSeparateProcessTransitionAndMigrationPublicationCannotEscapeAsAMixedResult(): void
    {
        [$authority, $at] = $this->readyTransition(true);
        $sync = $this->sync();
        $publisher = new ConsumerProcess([PHP_BINARY, __DIR__.'/Support/canonical_consumer_worker.php', 'hold', $this->root, $authority, (string) $at], $this->root, 'snapshot-publisher');
        $inspector = $this->worker('inspect-once', $at, $sync, 'snapshot-inspector');
        try {
            $publisher->start();
            $this->until(fn (): bool => is_file($this->root.'/process-held'));
            $inspector->start();
            $this->until(fn (): bool => is_file($sync.'/inspector-1'));
            file_put_contents($this->root.'/process-release', 'release');
            self::assertSame(0, $publisher->wait(), $publisher->getErrorOutput());
            file_put_contents($sync.'/release-1', 'release');
            self::assertSame(0, $inspector->wait(), $inspector->getErrorOutput());
            self::assertSame('COMMITTED_CURRENT', $this->decodeOutput($inspector)['classification']);
            self::assertFileExists($this->root.'/var/imperium/runtime/legacy-provider-transitions/old-store/retirement.json');
        } finally {
            $publisher->stop(0);
            $inspector->stop(0);
        }
    }

    public function testSeparateProcessRevocationCrossingRetriesToStableNoncurrent(): void
    {
        [$authority, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn (): int => $at))->execute($authority);
        $sync = $this->sync();
        $act = $this->act;
        $act['action'] = 'REVOKE';
        $act['act_id'] = 'snapshot-revoke-test';
        file_put_contents($sync.'/signed-revocation.json', json_encode($this->sign($act), JSON_THROW_ON_ERROR));
        $inspector = $this->worker('inspect-once', $at + 2, $sync, 'revocation-inspector');
        $revoker = $this->worker('revoke', $at + 1, $sync, 'revocation-writer');
        try {
            $inspector->start();
            $this->until(fn (): bool => is_file($sync.'/inspector-1'));
            $revoker->start();
            self::assertSame(0, $revoker->wait(), $revoker->getErrorOutput());
            self::assertSame(0, $inspector->wait(), $inspector->getErrorOutput());
            self::assertSame('COMMITTED_NOT_CURRENT', $this->decodeOutput($inspector)['classification']);
        } finally {
            $revoker->stop(0);
            $inspector->stop(0);
        }
    }

    public function testSeparateProcessContinuousChurnExhaustsExactlyTwoAttempts(): void
    {
        [, $at] = $this->readyTransition();
        $sync = $this->sync();
        $inspector = $this->worker('inspect-each', $at, $sync, 'churn-inspector');
        $writer = $this->worker('churn', $at, $sync, 'churn-writer');
        try {
            $inspector->start();
            $writer->start();
            self::assertSame(0, $writer->wait(), $writer->getErrorOutput());
            self::assertSame(0, $inspector->wait(), $inspector->getErrorOutput());
            $result = $this->decodeOutput($inspector);
            self::assertSame('INCOMPLETE', $result['classification']);
            self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $result['recovery']);
            self::assertFileExists($sync.'/inspector-1');
            self::assertFileExists($sync.'/inspector-2');
            self::assertFileDoesNotExist($sync.'/inspector-3');
        } finally {
            $writer->stop(0);
            $inspector->stop(0);
        }
    }

    public function testInterruptedJournalIsRepeatableAcrossFreshProcesses(): void
    {
        [$authority, $at] = $this->readyTransition();
        $cut = new ConsumerProcess([PHP_BINARY, __DIR__.'/Support/canonical_consumer_worker.php', 'cut', $this->root, $authority, (string) $at], $this->root, 'snapshot-cut');
        self::assertSame(23, $cut->run(), $cut->getErrorOutput());
        $sync = $this->sync();
        $first = $this->worker('inspect', $at, $sync, 'repeat-first');
        $second = $this->worker('inspect', $at, $sync, 'repeat-second');
        self::assertSame(0, $first->run(), $first->getErrorOutput());
        self::assertSame(0, $second->run(), $second->getErrorOutput());
        self::assertSame($this->decodeOutput($first), $this->decodeOutput($second));
        self::assertSame('INCOMPLETE', $this->decodeOutput($first)['classification']);
    }

    public function testExpiryUsesTheSameSuppliedTimeAcrossFreshProcesses(): void
    {
        [$authority, $at] = $this->readyTransition();
        (new NativeConsumer($this->state, static fn (): int => $at))->execute($authority);
        $current = $this->worker('inspect', $at, $this->sync(), 'expiry-current');
        $expired = $this->worker('inspect', $at + 601, $this->sync(), 'expiry-expired');
        self::assertSame(0, $current->run(), $current->getErrorOutput());
        self::assertSame(0, $expired->run(), $expired->getErrorOutput());
        self::assertSame('COMMITTED_CURRENT', $this->decodeOutput($current)['classification']);
        self::assertSame('COMMITTED_NOT_CURRENT', $this->decodeOutput($expired)['classification']);
    }

    public function testTerminatedInspectorLeavesSemanticStateByteIdentical(): void
    {
        [, $at] = $this->readyTransition();
        $sync = $this->sync();
        $before = $this->semanticFiles();
        $inspector = $this->worker('inspect-each', $at, $sync, 'terminated-inspector');
        $inspector->start();
        $this->until(fn (): bool => is_file($sync.'/inspector-1'));
        $inspector->stop(0);
        self::assertSame($before, $this->semanticFiles());
        self::assertSame([], glob($this->root.'/var/imperium/runtime/transition-locks/*inspection*'));
    }

    private function worker(string $mode, int $at, string $sync, string $label): ConsumerProcess
    {
        return new ConsumerProcess([PHP_BINARY, __DIR__.'/Support/native_inspection_worker.php', $mode, $this->root, (string) $at, $sync], $this->root, $label);
    }

    private function sync(): string
    {
        $sync = $this->root.'/snapshot-sync';
        if (!is_dir($sync)) { mkdir($sync, 0770, true); }
        return $sync;
    }

    private function until(callable $condition): void
    {
        $deadline = microtime(true) + 15;
        while (!$condition()) {
            if (microtime(true) > $deadline) { self::fail('Process rendezvous timeout'); }
            usleep(10000);
            clearstatcache();
        }
    }

    private function decodeOutput(ConsumerProcess $process): array
    {
        return json_decode(trim($process->getOutput()), true, 32, JSON_THROW_ON_ERROR);
    }

    private function semanticFiles(): array
    {
        $files = [];
        foreach ([NativeState::DIRECTORY, ...array_values(NativeState::SOURCES), NativeState::TRUST,
            'var/imperium/runtime/legacy-provider-transitions',
            'var/imperium/la-cortine/deterministic-execution-claims',
            'var/imperium/imperator/outbound-email-authorization-issuances',
            'var/imperium/la-cortine/deterministic-effect-start-journals'] as $relative) {
            $base = $this->root.'/'.$relative;
            if (!is_dir($base)) { continue; }
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS)) as $file) {
                if ($file->isFile() && !str_ends_with($file->getPathname(), '.lock')) {
                    $files[str_replace('\\', '/', substr($file->getPathname(), strlen($this->root) + 1))] = hash_file('sha256', $file->getPathname());
                }
            }
        }
        ksort($files);
        return $files;
    }
}
