<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\{TransitionStore, TransitionAuthority, TransitionContract};
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/ExecutableTransitionBatch1Test.php';

final class ExecutableTransitionBatch4Test extends TestCase
{
    public function testSeparateProcessesHaveOneWinnerAndOneRefusal(): void
    {
        self::assertTrue(function_exists('proc_open'), 'Separate-process proof is required, not skippable.');
        $directory = sys_get_temp_dir().'/eat-'.bin2hex(random_bytes(8)); mkdir($directory);
        $processes = []; $pipes = [];
        try {
            $grant = ExecutableTransitionBatch1Test::grant();
            $pin = TransitionContract::digest($grant);
            $store = new TransitionStore($directory);
            $store->locked(fn () => $store->put('grant', $grant));
            (new TransitionAuthority($store, $pin))->issue(150);
            for ($i = 0; $i < 2; ++$i) {
                // Alternate spellings resolve to the same physical storage and lock.
                $alias = $i === 0 ? $directory : $directory.'/.';
                $processes[$i] = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/fixtures/executable-transition-worker.php',
                    $alias, $pin, $directory.'/go'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes[$i]);
                self::assertIsResource($processes[$i]);
            }
            touch($directory.'/go');
            $results = [];
            foreach ($processes as $i => $process) {
                $results[] = stream_get_contents($pipes[$i][1]);
                self::assertSame('', stream_get_contents($pipes[$i][2]));
                fclose($pipes[$i][1]); fclose($pipes[$i][2]);
                self::assertSame(0, proc_close($process)); unset($processes[$i]);
            }
            sort($results);
            self::assertSame(['COMMITTED', 'EAT_ALREADY_COMMITTED_READ_ONLY_REPLAY'], $results);
            self::assertCount(1, glob($directory.'/commit.json'));
            self::assertCount(7, $store->read('commit')['records']);
        } finally {
            foreach ($processes as $process) { proc_terminate($process); proc_close($process); }
            foreach (glob($directory.'/*') as $file) { unlink($file); } rmdir($directory);
        }
    }
}
