<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

/** Small native-process fixture; no new dependency or shell command interpolation. */
final class CitadelAuthorityProcess
{
    private mixed $process = null;
    private array $pipes = [];
    private string $out = '';
    private string $err = '';
    private string $outPath;
    private string $errPath;
    private ?int $exit = null;
    public function __construct(private array $command) {}
    public function start(): void
    {
        $env = ['APP_ENV' => 'test', 'APP_SECRET' => '', 'COMPOSER_DISABLE_NETWORK' => '1'];
        foreach (['PATH', 'SystemRoot', 'WINDIR', 'COMSPEC', 'PATHEXT', 'TEMP', 'TMP', 'SystemDrive'] as $key) {
            $value = getenv($key); if (is_string($value)) { $env[$key] = $value; }
        }
        // Windows proc pipes do not provide reliable nonblocking reads. Use fixture-local files.
        $root = realpath($this->command[array_key_last($this->command)]);
        if ($root === false || dirname($root) !== realpath(sys_get_temp_dir()) || !str_starts_with(basename($root), 'citadel-authority-synthetic-')) { throw new \RuntimeException('Synthetic process output boundary mismatch'); }
        $prefix = $root.'/process-'.bin2hex(random_bytes(6));
        $this->outPath = $prefix.'.stdout'; $this->errPath = $prefix.'.stderr';
        $this->process = proc_open($this->command, [['pipe', 'r'], ['file', $this->outPath, 'w'], ['file', $this->errPath, 'w']], $this->pipes, null, $env, ['bypass_shell' => true]);
        if (!is_resource($this->process)) { throw new \RuntimeException('Synthetic process start failed'); }
        fclose($this->pipes[0]);
    }
    public function isRunning(): bool
    {
        if (!is_resource($this->process)) { return false; }
        $status = proc_get_status($this->process);
        if (!$status['running'] && $status['exitcode'] >= 0) { $this->exit = $status['exitcode']; }
        return $status['running'];
    }
    public function getOutput(): string { $this->drain(); return $this->out; }
    public function getErrorOutput(): string { $this->drain(); return $this->err; }
    private function drain(): void
    {
        if (isset($this->outPath) && is_file($this->outPath)) { $this->out = file_get_contents($this->outPath); }
        if (isset($this->errPath) && is_file($this->errPath)) { $this->err = file_get_contents($this->errPath); }
    }
    public function wait(): int
    {
        $deadline = microtime(true) + 15;
        while ($this->isRunning()) { $this->drain(); if (microtime(true) > $deadline) { $this->stop(0); throw new \RuntimeException('Synthetic process wait expired'); } usleep(10000); }
        $this->drain(); return $this->exit ?? -1;
    }
    public function stop(int $unused): void
    {
        if (!is_resource($this->process)) { return; }
        if ($this->isRunning()) { proc_terminate($this->process); }
        $this->drain();
        proc_close($this->process); $this->process = null;
    }
}
