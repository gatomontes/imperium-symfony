<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer, TransitionContract};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

final class ExecutableTransitionBatch5Test extends TestCase
{
    public static function cuts(): iterable
    {
        foreach (['authority', 'journal', 'commit'] as $record) {
            foreach (['before_open', 'before_publish', 'after_publish'] as $cut) {
                yield $record.'.'.$cut => [$record, $cut];
            }
        }
    }

    #[DataProvider('cuts')]
    public function testProcessTerminationLeavesAnUnambiguousPublishedOrIncompleteState(string $record, string $cut): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant(); $pin = TransitionContract::digest($grant);
            $store = new TransitionStore($directory);
            $store->locked(fn () => $store->put('grant', $grant));
            if ($record !== 'authority') { (new TransitionAuthority($store, $pin))->issue(150); }
            $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/fixtures/executable-transition-interruption-worker.php',
                $directory, $pin, $record.'.'.$cut], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            self::assertSame('', stream_get_contents($pipes[1])); self::assertSame('', stream_get_contents($pipes[2]));
            fclose($pipes[1]); fclose($pipes[2]); self::assertSame(73, proc_close($process));
            $restarted = new TransitionStore($directory);
            self::assertSame($cut === 'after_publish', null !== $restarted->read($record));
            self::assertSame($cut === 'before_publish', $restarted->pending($record));
            if ($record === 'commit' && $cut === 'after_publish') {
                self::assertCount(7, $restarted->read('commit')['records']);
            } else {
                self::assertNull($restarted->read('commit'));
            }
            if ($record === 'commit' && $cut !== 'after_publish') {
                try { (new TransitionConsumer($restarted, new TransitionAuthority($restarted, $pin)))->execute($pin, 150); self::fail(); }
                catch (\RuntimeException $e) { self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $e->getMessage()); }
                self::assertNull($restarted->read('commit'));
            }
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public static function lifecycle(): iterable
    {
        yield 'expired' => [200, false, 'EAT_AUTHORITY_NOT_CURRENT'];
        yield 'revoked' => [150, true, 'EAT_AUTHORITY_REVOKED'];
    }

    #[DataProvider('lifecycle')]
    public function testExpiryAndRevocationCannotConsume(int $at, bool $revoke, string $reason): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant(); $pin = TransitionContract::digest($grant);
            $store = new TransitionStore($directory); $store->locked(fn () => $store->put('grant', $grant));
            $custody = new TransitionAuthority($store, $pin); $custody->issue(150);
            if ($revoke) { $custody->revoke(); }
            try { (new TransitionConsumer($store, $custody))->execute($pin, $at); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame($reason, $e->getMessage()); }
            self::assertNull($store->read('commit'));
            self::assertNull($store->read('journal'));
            self::assertSame($reason, $store->read('refusal')['reason']);
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }
}
