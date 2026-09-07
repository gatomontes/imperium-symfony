<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Immutable generations; one publish point for all changes in a transition.
 * Trusted runtime storage is the custody boundary, not the unkeyed digest.
 * No callback may perform external I/O or cognition while holding this lock.
 * inspect() permits bounded local child publication under the same revocation fence.
 */
final readonly class FormationJournal
{
    private string $directory;
    private AtomicTransition $atomic;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->directory = $root.'/var/imperium/citadel/formation';
        $this->atomic = new AtomicTransition($root);
    }

    public static function digest(mixed $value): string
    {
        return hash('sha256', CanonicalJson::encode($value));
    }

    public static function keys(array $object, array $expected): bool
    {
        $actual = array_keys($object);
        sort($actual, SORT_STRING); sort($expected, SORT_STRING);
        return $actual === $expected;
    }

    public function read(): array
    {
        return $this->atomic->run('citadel-formation', fn () => $this->latest());
    }

    public function inspect(callable $inspection): mixed
    {
        return $this->atomic->run('citadel-formation', fn () => $inspection($this->latest()));
    }

    /** Resolve only a retained frame in the verified trusted-custody chain. */
    public function historical(int $generation, string $digest): array
    {
        return $this->inspect(function (array $latest) use ($generation, $digest): array {
            if ($generation < 1 || $generation > $latest['generation']) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE');
            }
            $frame = json_decode((string) file_get_contents(sprintf('%s/%012d.json', $this->directory, $generation)), true, 512, JSON_THROW_ON_ERROR);
            $content = $frame;
            unset($content['record_digest']);
            if (($frame['record_digest'] ?? null) !== $digest || self::digest($content) !== $digest) {
                throw new \RuntimeException('CMF130_HISTORICAL_PUBLICATION_AUTHORITY_UNVERIFIABLE');
            }
            return $frame;
        });
    }

    public function change(callable $transition): mixed
    {
        return $this->atomic->run('citadel-formation', function () use ($transition): mixed {
            $prior = $this->latest();
            $state = $prior['state'];
            $result = $transition($state);
            if (self::digest($state) === self::digest($prior['state'])) {
                return $result;
            }
            $frame = [
                'schema' => 'imperium.citadel-formation-frame/v1',
                'generation' => $prior['generation'] + 1,
                'previous_digest' => $prior['record_digest'],
                'state' => $state,
            ];
            $frame['record_digest'] = self::digest($frame);
            if (!is_dir($this->directory) && !@mkdir($this->directory, 0700, true) && !is_dir($this->directory)) {
                throw new \RuntimeException('CMF001_STORAGE_UNAVAILABLE');
            }
            $path = sprintf('%s/%012d.json', $this->directory, $frame['generation']);
            $temporary = $path.'.pending.'.bin2hex(random_bytes(12));
            $handle = fopen($temporary, 'xb');
            if (false === $handle) {
                throw new \RuntimeException('CMF001_STORAGE_UNAVAILABLE');
            }
            try {
                $bytes = json_encode($frame, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $written = fwrite($handle, substr($bytes, $offset));
                    if (false === $written || 0 === $written) {
                        throw new \RuntimeException('CMF002_COMMIT_FAILED');
                    }
                    $offset += $written;
                }
                if (!fflush($handle) || !fsync($handle)) {
                    throw new \RuntimeException('CMF002_COMMIT_FAILED');
                }
            } finally {
                fclose($handle);
            }
            if (is_file($path) || !rename($temporary, $path)) {
                throw new \RuntimeException('CMF002_COMMIT_FAILED');
            }
            return $result;
        });
    }

    private function latest(): array
    {
        $prior = ['generation' => 0, 'record_digest' => null, 'state' => []];
        $paths = glob($this->directory.'/*.json') ?: [];
        sort($paths, SORT_STRING);
        foreach ($paths as $path) {
            $frame = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $digest = $frame['record_digest'] ?? null;
            unset($frame['record_digest']);
            if (!is_string($digest) || !hash_equals($digest, self::digest($frame))
                || ($frame['schema'] ?? null) !== 'imperium.citadel-formation-frame/v1'
                || ($frame['generation'] ?? null) !== $prior['generation'] + 1
                || ($frame['previous_digest'] ?? null) !== $prior['record_digest']
                || basename($path) !== sprintf('%012d.json', $frame['generation'])
                || !is_array($frame['state'] ?? null)) {
                throw new \RuntimeException('CMF003_JOURNAL_CHAIN_INVALID');
            }
            $prior = [...$frame, 'record_digest' => $digest];
        }
        return $prior;
    }
}
