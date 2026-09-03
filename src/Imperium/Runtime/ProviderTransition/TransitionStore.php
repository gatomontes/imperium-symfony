<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Cooperative single-host storage. No power-loss or hostile-writer guarantee. */
final class TransitionStore
{
    private string $directory;
    private bool $locked = false;

    public function __construct(string $directory, private readonly ?\Closure $checkpoint = null)
    {
        $resolved = realpath($directory);
        if (false === $resolved || !is_dir($resolved)) {
            throw new \RuntimeException('EAT_STORAGE_NOT_PROVISIONED');
        }
        $this->directory = $resolved;
    }

    public function identity(): string
    {
        $path = str_replace('\\', '/', $this->directory);
        return hash('sha256', PHP_OS_FAMILY === 'Windows' ? strtolower($path) : $path);
    }

    /** One domain lock also prevents cross-root authority reuse within this store. */
    public function locked(callable $action): mixed
    {
        if ($this->locked) {
            throw new \RuntimeException('EAT_NESTED_LOCK_REFUSED');
        }
        $path = $this->path('domain.lock');
        $handle = @fopen($path, 'c+b');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            if (is_resource($handle)) { fclose($handle); }
            throw new \RuntimeException('EAT_LOCK_FAILED');
        }
        $this->locked = true;
        try {
            return $action();
        } finally {
            $this->locked = false;
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function read(string $name): ?array
    {
        $path = $this->path($name.'.json');
        if (!file_exists($path)) {
            return null;
        }
        try {
            $raw = @file_get_contents($path);
            if (false === $raw || strlen($raw) > 65536) { throw new \RuntimeException(); }
            $record = json_decode($raw, true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($record)) { throw new \RuntimeException(); }
            TransitionContract::keys($record, ['body', 'digest']);
            if (!is_array($record['body']) || !is_string($record['digest'])
                || !hash_equals(TransitionContract::digest($record['body']), $record['digest'])) {
                throw new \RuntimeException();
            }
            return $record['body'];
        } catch (\Throwable) {
            throw new \RuntimeException('EAT_CORRUPT_RECORD');
        }
    }

    public function pending(string $name): bool
    {
        return file_exists($this->path($name.'.pending'));
    }

    public function assertNotRetired(): void
    {
        if ($this->pending('retirement') || null !== $this->read('retirement')) {
            throw new \RuntimeException('EAT_NATIVE_PROTOCOL_RETIRED_NO_RETRY');
        }
    }

    /** Exact replay only; interrupted publication is never silently replaced. */
    public function put(string $name, array $body): array
    {
        if (!$this->locked) { throw new \RuntimeException('EAT_WRITE_WITHOUT_LOCK'); }
        $existing = $this->read($name);
        if (null !== $existing) {
            if (TransitionContract::digest($existing) !== TransitionContract::digest($body)) {
                throw new \RuntimeException('EAT_IMMUTABLE_CONFLICT');
            }
            return $existing;
        }
        $pending = $this->path($name.'.pending');
        $this->observe($name.'.before_open');
        $handle = @fopen($pending, 'x+b');
        if (false === $handle) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
        try {
            $bytes = json_encode(['body' => $body, 'digest' => TransitionContract::digest($body)], JSON_THROW_ON_ERROR);
            if (strlen($bytes) > 65536 || strlen($bytes) !== fwrite($handle, $bytes)
                || !fflush($handle) || !fsync($handle)) {
                throw new \RuntimeException('EAT_WRITE_FAILED');
            }
        } finally {
            fclose($handle);
        }
        $this->observe($name.'.before_publish');
        if (!@rename($pending, $this->path($name.'.json'))) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        $this->observe($name.'.after_publish');
        return $body;
    }

    /** Trusted fault harness only; never bound from request or persisted data. */
    private function observe(string $cut): void
    {
        if (null !== $this->checkpoint) { ($this->checkpoint)($cut); }
    }

    private function path(string $name): string
    {
        if (!preg_match('/^(?:grant|authority|journal|commit|revocation|refusal|retirement)\.(?:json|pending)$|^domain\.lock$/D', $name)) {
            throw new \RuntimeException('EAT_STORAGE_NAME_INVALID');
        }
        $path = $this->directory.DIRECTORY_SEPARATOR.$name;
        if (is_link($path) || (file_exists($path) && realpath(dirname($path)) !== $this->directory)) {
            throw new \RuntimeException('EAT_STORAGE_ALIAS_REFUSED');
        }
        return $path;
    }
}
