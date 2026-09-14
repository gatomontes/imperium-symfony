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
    private string $owner;
    private string $observationLock;
    private string $root;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->root = $root;
        $this->owner = str_replace('\\', '/', realpath($root) ?: $root);
        $this->directory = $root.'/var/imperium/citadel/formation';
        $this->atomic = new AtomicTransition($root);
        $this->observationLock = $root.'/var/imperium/runtime/transition-locks/'.hash('sha256','citadel-formation').'.lock';
    }

    /** Fixed construction identity; no store read or lock acquisition. */
    public function sameOwner(self $other): bool
    {
        return PHP_OS_FAMILY === 'Windows' ? strcasecmp($this->owner, $other->owner) === 0 : $this->owner === $other->owner;
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
        return FormationOwnerFrame::run($this->root, fn (FormationOwnerFrame $owner) => $inspection($this->latest(), $owner));
    }

    public function readInOwner(FormationOwnerFrame $owner): array
    {
        $owner->assertOwner($this);
        return $this->latest();
    }

    /** Pure observation under the existing writer fence; never initializes custody. */
    public function inspectExisting(callable $inspection): mixed
    {
        // Use the established AtomicTransition fence without changing that pinned
        // shared writer implementation or invoking its creating run() path.
        if (!is_file($this->observationLock)) { throw new \RuntimeException('O5_EXISTING_OWNER_REQUIRED'); }
        $handle = @fopen($this->observationLock,'rb');
        if ($handle === false) { throw new \RuntimeException('O5_EXISTING_OWNER_REQUIRED'); }
        try {
            if (!flock($handle,LOCK_SH)) { throw new \RuntimeException('PST102_ATOMIC_TRANSITION_LOCK_FAILED'); }
            $frame = $this->latest();
            if ($frame['generation'] === 0) {
                throw new \RuntimeException('O5_EXISTING_OWNER_REQUIRED');
            }
            return $inspection($frame);
        } finally {
            flock($handle,LOCK_UN);
            fclose($handle);
        }
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
        return $this->changeAtHead(static function (array &$state, array $head, FormationOwnerFrame $owner) use ($transition): mixed {
            return $transition($state, $owner);
        });
    }

    /** The predecessor head and mutable state are observed under the same lock.
     * The head is an observation, never competence or an execution capability.
     */
    public function changeAtHead(callable $transition): mixed
    {
        return FormationOwnerFrame::run($this->root, function (FormationOwnerFrame $owner) use ($transition): mixed {
            $prior = $this->latest();
            $state = $prior['state'];
            $result = $transition($state, ['generation' => $prior['generation'], 'digest' => $prior['record_digest']], $owner);
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
        // Pure string-encoding reuse is confined to this single chain traversal.
        $canonical = new JournalCanonicalHash();
        foreach ($paths as $path) {
            [$frame, $digest, $calculated] = $canonical->read((string) file_get_contents($path));
            if (!is_string($digest) || !hash_equals($digest, $calculated)
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
