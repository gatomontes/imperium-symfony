<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ImmutableRecordStore
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $root,
        private AtomicTransition $atomic,
    ) {
    }

    public function put(string $directory, string $id, array $record): array
    {
        $path = $this->path($directory, $id);

        return $this->atomic->run('immutable:'.hash('sha256', $directory), function () use ($path, $record): array {
            $sealed = $this->seal($record);
            if (is_file($path)) {
                $existing = $this->readPath($path);
                if (CanonicalJson::encode($existing) !== CanonicalJson::encode($sealed)) {
                    throw new \RuntimeException('PST111_IMMUTABLE_RECORD_CONFLICT');
                }

                return $existing;
            }

            $this->commit($path, $sealed);

            return $sealed;
        });
    }

    public function read(string $directory, string $id): array
    {
        return $this->readPath($this->path($directory, $id));
    }

    private function path(string $directory, string $id): string
    {
        if (!preg_match('/^[a-z0-9][a-z0-9\/-]{1,180}$/', $directory)
            || str_contains($directory, '..')
            || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,220}$/', $id)) {
            throw new \InvalidArgumentException('PST110_IMMUTABLE_RECORD_PATH_INVALID');
        }

        return $this->root.'/'.$directory.'/'.$id.'.json';
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function readPath(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('PST112_IMMUTABLE_RECORD_ABSENT');
        }
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        if (!is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($record)))) {
            throw new \RuntimeException('PST113_IMMUTABLE_RECORD_TAMPERED');
        }
        $record['record_digest'] = $digest;

        return $record;
    }

    private function commit(string $path, array $record): void
    {
        if (!is_dir(dirname($path)) && !mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('PST114_IMMUTABLE_RECORD_COMMIT_FAILED');
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('PST114_IMMUTABLE_RECORD_COMMIT_FAILED');
        }
    }
}
