<?php

declare(strict_types=1);

namespace App\Bootstrap;

final readonly class StateStore
{
    private string $statePath;
    private string $lockPath;

    public function __construct(string $projectDir)
    {
        $this->statePath = $projectDir.'/var/imperium/bootstrap-state.json';
        $this->lockPath = $projectDir.'/var/imperium/bootstrap.lock';
    }

    public function read(): ?array
    {
        if (!is_file($this->statePath)) {
            return null;
        }
        $contents = file_get_contents($this->statePath);
        if (false === $contents) {
            throw new \RuntimeException('Bootstrap state cannot be read.');
        }
        return json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    }

    public function locked(callable $operation): mixed
    {
        $directory = dirname($this->lockPath);
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new \RuntimeException('Bootstrap state directory cannot be created.');
        }
        $handle = fopen($this->lockPath, 'c+');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            throw new \RuntimeException('Bootstrap lock cannot be acquired.');
        }
        try {
            return $operation();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function write(array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
        $temporary = $this->statePath.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $this->statePath)) {
            @unlink($temporary);
            throw new \RuntimeException('Bootstrap state cannot be committed atomically.');
        }
    }
}
