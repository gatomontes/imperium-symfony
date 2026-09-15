<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Citadel\Formation\FormationFreshEstablishment as P;
final class FreshEstablishmentProcesses
{
    public array $children = [];
    private array $publicInitialFrame;
    public function __construct(private FreshEstablishmentFixture $f) { $this->publicInitialFrame = $f->journal->read(); }
    public function start(string $operation, array $options = []): int
    {
        $id = count($this->children); $base = $this->f->root.'/ppc7-child-'.$id;
        $pins = array_filter($this->f->fresh->d->f->sources, static fn(array $h): bool =>
            in_array($h['body']['kind'] ?? '', ['augur-base-facts', 'augur-base-observation', 'synthetic-provider-bytes'], true));
        $input = [...$options, 'operation' => $operation, 'root' => $this->f->root, 'now' => $this->f->clock->at,
            'identities' => [$this->f->store->instance, $this->f->store->citadel, $this->f->store->operator, $this->f->store->sourceCommit],
            'pins' => $pins, 'terms' => $options['terms'] ?? $this->f->terms, 'operator' => $options['operator'] ?? $this->f->operator,
            'public_initial_frame' => $this->publicInitialFrame,
            'formation' => $options['formation'] ?? $this->f->formation, 'reference' => $this->f->reservation === null ? null : P::reference($this->f->reservation),
            'events' => $base.'.events', 'ready' => $base.'.ready', 'release' => $base.'.release'];
        file_put_contents($base.'.json', json_encode($input, JSON_THROW_ON_ERROR));
        $process = proc_open([PHP_BINARY, __DIR__.'/fresh-establishment-worker.php', $base.'.json'],
            [1 => ['file', $base.'.out', 'w'], 2 => ['file', $base.'.err', 'w']], $pipes);
        if (!is_resource($process)) { throw new \RuntimeException('PPC7_TEST_PROCESS'); }
        $this->children[$id] = ['process' => $process, 'base' => $base, 'started_utc' => gmdate('c'), 'input' => $input]; return $id;
    }
    public function ready(int $id): void
    {
        $deadline = microtime(true) + 45; $base = $this->children[$id]['base'];
        while (!is_file($base.'.ready')) {
            if (microtime(true) >= $deadline) { throw new \RuntimeException('PPC7_TEST_READY_TIMEOUT:'.@file_get_contents($base.'.err')); } usleep(1000);
        }
    }
    public function release(int $id): void { file_put_contents($this->children[$id]['base'].'.release', 'release'); }
    public function finish(int $id): array
    {
        $child = $this->children[$id]; $deadline = microtime(true) + 60;
        do { $status = proc_get_status($child['process']); if (!$status['running']) { break; } usleep(1000); } while (microtime(true) < $deadline);
        if ($status['running']) { proc_terminate($child['process']); throw new \RuntimeException('PPC7_TEST_PROCESS_TIMEOUT'); }
        $closed = proc_close($child['process']); $this->children[$id]['process'] = null;
        $row = ['started_utc' => $child['started_utc'], 'ended_utc' => gmdate('c'), 'native_exit' => $status['exitcode'] < 0 ? $closed : $status['exitcode'],
            'input' => $child['input'], 'output' => file_get_contents($child['base'].'.out'), 'stderr' => file_get_contents($child['base'].'.err'),
            'events' => is_file($child['base'].'.events') ? file_get_contents($child['base'].'.events') : ''];
        $evidence = getenv('PPC7_PUBLIC_EVIDENCE');
        if (is_string($evidence) && $evidence !== '') {
            $directory = $evidence.'/processes'; if (!is_dir($directory)) { mkdir($directory, 0700, true); }
            file_put_contents($directory.'/'.hash('sha256', $child['base']).'.json', json_encode($row, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        }
        return $row;
    }
    public function close(): void { foreach ($this->children as $child) { if (is_resource($child['process'])) { proc_terminate($child['process']); proc_close($child['process']); } } }
}
