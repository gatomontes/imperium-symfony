<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class DelegateMissionOperationalTransitionConcurrencyTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-contention-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testTwoProcessesProduceOneImmutableQualificationWinner(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/operational-folium-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $pipes = [];
        $processes = [];
        for ($i = 0; $i < 2; ++$i) {
            $processes[$i] = proc_open([PHP_BINARY, $worker, $this->root, $gate, (string) $i], $descriptors, $pipes[$i]);
            self::assertIsResource($processes[$i]);
        }
        if (!is_dir($this->root)) {
            mkdir($this->root, 0770, true);
        }
        touch($gate);
        $results = [];
        for ($i = 0; $i < 2; ++$i) {
            $results[] = stream_get_contents($pipes[$i][1]);
            $errors = stream_get_contents($pipes[$i][2]);
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            self::assertSame(0, proc_close($processes[$i]));
            self::assertSame('', $errors, 'Contending Folium commit emitted process diagnostics.');
        }
        sort($results);
        self::assertSame(['PST111_IMMUTABLE_RECORD_CONFLICT', 'STORED'], $results);
        self::assertCount(1, glob($this->root.'/var/imperium/offices/conscription/delegate-mission-operational-profile-qualifications/*.json') ?: []);

        $codex = json_decode((string) file_get_contents($this->root.'/var/imperium/codex-imperii.json'), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(1, $codex['generation']);
        self::assertCount(1, $codex['folia']);
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
