<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Deployment;

use App\Imperium\Runtime\Onboarding\DeepSeek\KeySource;

/** Infrastructure-owned immutable generations. See docs/provider-production-custody.md. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FileKeySource implements KeySource
{
    public function __construct(private string $directory) {}

    private function path(string $name): string
    {
        $root = $this->directory;
        if (preg_match('~\A(?:[A-Za-z]:[/\\\\]|/)~', $root) !== 1
            || str_contains($root, "\0") || preg_match('~(?:^|/)\.\.?(?:/|$)~', str_replace('\\', '/', $root))) {
            throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED');
        }
        $path = $root.'/'.$name;
        for ($p = $path; ; $p = dirname($p)) {
            clearstatcache(true, $p);
            $target = @readlink($p);
            $normalize = static fn(string $v): string => strtolower(rtrim(str_replace('\\', '/', $v), '/'));
            if (is_link($p) || ($target !== false && !(PHP_OS_FAMILY === 'Windows' && $normalize($target) === $normalize($p)))) {
                throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED');
            }
            if (dirname($p) === $p) { break; }
        }
        return $path;
    }

    private function read(string $name, int $limit): string
    {
        $path = $this->path($name);
        if (!is_file($path)) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
        $file = @fopen($path, 'rb');
        if ($file === false) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
        try { $bytes = stream_get_contents($file, $limit + 1); }
        finally { fclose($file); }
        if (!is_string($bytes) || strlen($bytes) > $limit) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
        return $bytes;
    }

    private function locked(callable $read): void
    {
        $path = $this->path('custody.lock');
        if (!is_file($path)) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
        $lock = @fopen($path, 'rb');
        if ($lock === false) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
        try {
            // Bound waiting. A busy or interrupted publisher never triggers repair.
            if (!flock($lock, LOCK_SH | LOCK_NB)) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
            $read();
        } finally { flock($lock, LOCK_UN); fclose($lock); }
    }

    private function current(): string
    {
        $generation = $this->read('generation', 128);
        if (preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9_-]{0,127}\z/', $generation) !== 1) {
            throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED');
        }
        return $generation;
    }

    public function generation(): string
    {
        $generation = '';
        $this->locked(function() use (&$generation): void { $generation = $this->current(); });
        return $generation;
    }

    public function withKey(callable $delivery): void
    {
        try {
            $this->locked(function() use ($delivery): void {
                $generation = $this->current();
                $key = $this->read('keys/'.$generation, 4096);
                if (preg_match('/\A[\x21-\x7e]{1,4096}\z/', $key) !== 1) {
                    throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED');
                }
                // Hold shared custody through delivery; cooperative rotation takes exclusive custody.
                try { $delivery($key); }
                finally { unset($key); }
            });
        } catch (\Throwable) { throw new \RuntimeException('O3_KEY_CUSTODY_REFUSED'); }
    }
}
