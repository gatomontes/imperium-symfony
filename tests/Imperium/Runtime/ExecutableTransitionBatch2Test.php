<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\TransitionStore;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 3).'/vendor/autoload.php';

final class ExecutableTransitionBatch2Test extends TestCase
{
    public function testStoreRequiresLockAndPreservesExactImmutableWinner(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        $store = new TransitionStore($directory);
        try {
            try { $store->put('journal', ['phase' => 'PREPARED']); self::fail('Unlocked write accepted'); }
            catch (\RuntimeException $e) { self::assertSame('EAT_WRITE_WITHOUT_LOCK', $e->getMessage()); }
            $stored = $store->locked(fn () => $store->put('journal', ['phase' => 'PREPARED']));
            self::assertSame($stored, $store->read('journal'));
            self::assertSame($stored, $store->locked(fn () => $store->put('journal', $stored)));
            try { $store->locked(fn () => $store->put('journal', ['phase' => 'OTHER'])); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_IMMUTABLE_CONFLICT', $e->getMessage()); }
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    public function testInterruptedFileAndCorruptionRefuse(): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        $store = new TransitionStore($directory);
        try {
            file_put_contents($directory.'/journal.pending', '{');
            try { $store->locked(fn () => $store->put('journal', [])); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $e->getMessage()); }
            self::assertNull($store->read('journal'));
            file_put_contents($directory.'/commit.json', '{');
            try { $store->read('commit'); self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('EAT_CORRUPT_RECORD', $e->getMessage()); }
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }
}
