<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

/** Local disposable PHP worker; no shell, network, or new package dependency. */
final class ConsumerProcess
{
    private mixed $process = null;
    private ?int $exitCode = null;
    private string $stdout;
    private string $stderr;

    public function __construct(private readonly array $command, string $root, string $label)
    {
        $this->stdout = $root.'/worker-'.$label.'.stdout';
        $this->stderr = $root.'/worker-'.$label.'.stderr';
    }

    public function start(): void
    {
        $this->process = proc_open($this->command, [0 => ['pipe', 'r'], 1 => ['file', $this->stdout, 'w'], 2 => ['file', $this->stderr, 'w']], $pipes, null, null, ['bypass_shell' => true]);
        if (!is_resource($this->process)) { throw new \RuntimeException('Worker start failed'); }
        fclose($pipes[0]);
    }

    public function isRunning(): bool
    {
        if (null !== $this->exitCode || !is_resource($this->process)) { return false; }
        $status = proc_get_status($this->process);
        if (!$status['running']) { $this->exitCode = $status['exitcode']; }
        return $status['running'];
    }

    public function wait(): int
    {
        $deadline = microtime(true) + 25;
        while ($this->isRunning()) {
            if (microtime(true) > $deadline) { $this->stop(0); throw new \RuntimeException('Worker timeout'); }
            usleep(10000);
        }
        if (is_resource($this->process)) { proc_close($this->process); $this->process = null; }
        return $this->exitCode ?? -1;
    }

    public function run(): int { $this->start(); return $this->wait(); }
    public function getOutput(): string { return is_file($this->stdout) ? (string) file_get_contents($this->stdout) : ''; }
    public function getErrorOutput(): string { return is_file($this->stderr) ? (string) file_get_contents($this->stderr) : ''; }
    public function stop(int $timeout): void
    {
        if ($this->isRunning()) { proc_terminate($this->process); }
        if (is_resource($this->process)) { proc_close($this->process); $this->process = null; }
    }
    public function __destruct() { $this->stop(0); }
}
