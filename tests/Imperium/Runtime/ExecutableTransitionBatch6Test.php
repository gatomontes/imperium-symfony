<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionConsumer, TransitionContract, TransitionReconstructor};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

final class ExecutableTransitionBatch6Test extends TestCase
{
    public static function states(): iterable
    {
        foreach (['ABSENT', 'COMMITTED', 'REFUSED', 'INCOMPLETE', 'UNKNOWN_REPLAY_PROHIBITED'] as $state) { yield $state => [$state]; }
    }

    #[DataProvider('states')]
    public function testReadOnlyReconstructionDistinguishesEveryOutcome(string $state): void
    {
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        try {
            $grant = ExecutableTransitionBatch1Test::grant(); $pin = TransitionContract::digest($grant);
            $store = new TransitionStore($directory); $store->locked(fn () => $store->put('grant', $grant));
            $custody = new TransitionAuthority($store, $pin); $custody->issue(150);
            if ($state === 'COMMITTED') { (new TransitionConsumer($store, $custody))->execute($pin, 150); }
            if ($state === 'REFUSED') {
                try { (new TransitionConsumer($store, $custody))->execute($pin, 200); } catch (\RuntimeException) {}
            }
            if ($state === 'INCOMPLETE') { $store->locked(fn () => $store->put('journal', ['interrupted' => true])); }
            if ($state === 'UNKNOWN_REPLAY_PROHIBITED') { file_put_contents($directory.'/commit.json', '{'); }
            $before = $this->hashes($directory);
            $result = (new TransitionReconstructor(new TransitionStore($directory), $pin))->reconstruct();
            self::assertSame($state, $result['status']);
            self::assertFalse($result['retry_authorized']);
            self::assertFalse($result['automatic_repair_permitted']);
            self::assertSame($before, $this->hashes($directory));
        } finally { foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory); }
    }

    private function hashes(string $directory): array
    {
        $hashes = [];
        foreach (glob($directory.'/*') as $file) { $hashes[basename($file)] = hash_file('sha256', $file); }
        return $hashes;
    }
}
