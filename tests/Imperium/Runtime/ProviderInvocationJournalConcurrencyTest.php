<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use PHPUnit\Framework\TestCase;

final class ProviderInvocationJournalConcurrencyTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-contention-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testTwoProcessesProduceExactlyOneJournalStartWinner(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $id = 'provider-invocation-'.str_repeat('a', 20);
        $this->write($this->root.'/var/imperium/runtime/provider-invocations/'.$id.'.json', [
            'claim_id' => $id,
            'lease_consumption' => ['consumed' => true],
            'turn_authority_consumption' => ['consumed' => true],
            'provider_request' => ['idempotency_key' => 'imperium-'.$id, 'external_io_started' => false],
            'recovery' => ['automatic_replay_permitted' => false],
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
        ]);
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/provider-journal-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pipes = [];
        $processes = [];
        for ($i = 0; $i < 2; ++$i) {
            $processes[$i] = proc_open([PHP_BINARY, $worker, $this->root, $id, $gate], $descriptors, $pipes[$i]);
            self::assertIsResource($processes[$i]);
        }
        touch($gate);
        $results = [];
        for ($i = 0; $i < 2; ++$i) {
            $results[] = stream_get_contents($pipes[$i][1]);
            $errors = stream_get_contents($pipes[$i][2]);
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            self::assertSame(0, proc_close($processes[$i]));
            self::assertSame('', $errors, 'Contending transition emitted process diagnostics.');
        }
        sort($results);
        self::assertSame(['CLV412_PROVIDER_INVOCATION_ALREADY_STARTED', 'STARTED'], $results);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/provider-invocation-journal/*.json') ?: []);
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
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
