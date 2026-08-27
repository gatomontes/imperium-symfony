<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class MutableStateStore
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $root,
        private AtomicTransition $atomic,
    ) {
    }

    public function compareAndSwap(string $relativePath, ?string $expectedDigest, array $next): array
    {
        return $this->compareAndSwapGuarded($relativePath, $expectedDigest, static function (): void {}, $next);
    }

    public function compareAndSwapGuarded(string $relativePath, ?string $expectedDigest, callable $guard, array $next): array
    {
        $path = $this->path($relativePath);

        return $this->atomic->run('mutable:'.hash('sha256', $relativePath), function () use ($path, $expectedDigest, $guard, $next): array {
            $current = is_file($path) ? $this->readPath($path) : null;
            if (($current['record_digest'] ?? null) !== $expectedDigest) {
                throw new \RuntimeException('PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT');
            }
            $guard();
            unset($next['record_digest']);
            $next['record_digest'] = hash('sha256', CanonicalJson::encode($next));
            $this->commit($path, $next);

            return $next;
        });
    }

    public function read(string $relativePath): array
    {
        return $this->readPath($this->path($relativePath));
    }

    private function path(string $relativePath): string
    {
        if (!preg_match('/^[a-z0-9][a-zA-Z0-9._\/-]{2,240}\.json$/', $relativePath) || str_contains($relativePath, '..')) {
            throw new \InvalidArgumentException('PST120_MUTABLE_STATE_PATH_INVALID');
        }

        return $this->root.'/'.$relativePath;
    }

    private function readPath(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('PST122_MUTABLE_STATE_ABSENT');
        }
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        if (!is_string($digest) || !hash_equals($digest, hash('sha256', CanonicalJson::encode($record)))) {
            throw new \RuntimeException('PST123_MUTABLE_STATE_TAMPERED');
        }
        $record['record_digest'] = $digest;

        return $record;
    }

    private function commit(string $path, array $record): void
    {
        if (!is_dir(dirname($path)) && !@mkdir(dirname($path), 0770, true) && !is_dir(dirname($path))) {
            throw new \RuntimeException('PST124_MUTABLE_STATE_COMMIT_FAILED');
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('PST124_MUTABLE_STATE_COMMIT_FAILED');
        }
    }
}
