<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;

/** Cooperative local native event store. No live root is provisioned here. */
final class NativeState
{
    public const string DIRECTORY = 'var/imperium/runtime/native-provider-transition';
    public const string TRUST = 'var/imperium/operator-root/transition-trust';
    public const array SOURCES = [
        'principal' => 'var/imperium/runtime/imperator-principal-versions',
        'lifecycle' => 'var/imperium/evidence/imperator-principal-provenance/lifecycle-dispositions',
        'binding' => 'var/imperium/offices/la-cortine/provider-implementation-bindings',
        'activation' => 'var/imperium/runtime/provider-executor-principal-activations',
        'boundary' => 'var/imperium/offices/la-cortine/provider-execution-boundaries',
        'attestation' => 'var/imperium/offices/la-cortine/provider-executor-principal-attestations',
        'production' => 'var/imperium/runtime/principal-activation-decision-authority-provenance-productions',
        'assurance' => 'var/imperium/evidence/provider-execution-effect-readiness/assurance-admissions',
    ];
    private bool $locked = false;
    public readonly string $root;

    public function __construct(string $root, private readonly ?\Closure $checkpoint = null)
    {
        $resolved = realpath($root);
        if (false === $resolved || !is_dir($resolved)) { throw new \RuntimeException('NIR_STORAGE_ABSENT'); }
        $this->root = $resolved;
    }

    public function identity(): string
    {
        $path = str_replace('\\', '/', $this->root);
        return hash('sha256', 'Windows' === PHP_OS_FAMILY ? strtolower($path) : $path);
    }

    public function locked(callable $action): mixed
    {
        if ($this->locked) { throw new \RuntimeException('NIR_NESTED_LOCK'); }
        $atomic = new AtomicTransition($this->root);
        $scopes = array_map(static fn (string $dir): string => 'immutable:'.hash('sha256', $dir), [...array_values(self::SOURCES), self::TRUST]);
        sort($scopes);
        $enter = function (int $n) use (&$enter, $scopes, $atomic, $action): mixed {
            if (isset($scopes[$n])) { return $atomic->run($scopes[$n], fn () => $enter($n + 1)); }
            $this->locked = true;
            try { return $action(); } finally { $this->locked = false; }
        };
        return $atomic->run('native-provider-transition', fn () => $enter(0));
    }

    public function get(string $kind, string $id): ?array
    {
        $path = $this->eventPath($kind, $id);
        if (!file_exists($path)) { return null; }
        if (!is_dir($path)) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
        $store = new TransitionStore($path);
        if ($store->pending('commit')) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
        return $store->read('commit') ?? throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
    }

    public function put(string $kind, string $id, array $record, ?callable $beforePublish = null): array
    {
        if (!$this->locked) { throw new \RuntimeException('NIR_WRITE_WITHOUT_LOCK'); }
        $path = $this->eventPath($kind, $id);
        if (file_exists($path)) {
            $existing = $this->get($kind, $id);
            if ($existing !== $record) { throw new \RuntimeException('NIR_IMMUTABLE_CONFLICT'); }
            return $existing;
        }
        if (!is_dir($path) && !mkdir($path, 0770, true) && !is_dir($path)) { throw new \RuntimeException('NIR_STORAGE_FAILED'); }
        $callback = function (string $cut) use ($kind, $beforePublish): void {
            if (null !== $this->checkpoint) { ($this->checkpoint)($kind.'.'.$cut); }
            if ('commit.before_publish' === $cut && null !== $beforePublish) { $beforePublish(); }
        };
        $store = new TransitionStore($path, $callback);
        return $store->locked(fn () => $store->put('commit', $record));
    }

    public function ids(string $kind): array
    {
        $base = dirname($this->eventPath($kind, 'probe'));
        $ids = [];
        foreach (glob($base.'/*') ?: [] as $path) {
            $this->safe($path);
            if (!is_dir($path)) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            $ids[] = basename($path);
        }
        sort($ids);
        return $ids;
    }

    public function source(string $kind, array $ref): array
    {
        self::reference($ref);
        if (!isset(self::SOURCES[$kind])) { throw new \RuntimeException('NIR_SOURCE_KIND'); }
        $r = $this->json(self::SOURCES[$kind].'/'.$ref['id'].'.json');
        $plain = $r; unset($plain['record_digest']);
        if (($r['record_digest'] ?? null) !== TransitionContract::digest($plain)
            || $r['record_digest'] !== $ref['digest'] || ($r['schema'] ?? null) !== $ref['schema']) {
            throw new \RuntimeException('NIR_SOURCE_CHANGED');
        }
        return $r;
    }

    public function json(string $relative): array
    {
        if (!preg_match('~^[a-zA-Z0-9/._-]+$~D', $relative) || str_contains($relative, '..')) { throw new \RuntimeException('NIR_PATH'); }
        $path = $this->root.'/'.$relative;
        $this->safe($path);
        $bytes = @file_get_contents($path);
        if (false === $bytes || strlen($bytes) > 65536) { throw new \RuntimeException('NIR_SOURCE_ABSENT'); }
        try { $record = json_decode($bytes, true, 32, JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new \RuntimeException('NIR_SOURCE_INVALID'); }
        if (!is_array($record)) { throw new \RuntimeException('NIR_SOURCE_INVALID'); }
        return $record;
    }

    public static function id(mixed $id): void
    {
        if (!is_string($id) || !preg_match('/^[a-z0-9][a-z0-9._-]{2,180}$/D', $id) || str_contains($id, '..')) {
            throw new \RuntimeException('NIR_IDENTIFIER');
        }
    }

    public static function reference(mixed $ref): void
    {
        if (!is_array($ref)) { throw new \RuntimeException('NIR_REFERENCE'); }
        TransitionContract::keys($ref, ['id', 'schema', 'digest']);
        self::id($ref['id']);
        if (!is_string($ref['schema']) || !preg_match('~^imperium\.[a-z0-9./-]{2,180}$~D', $ref['schema'])
            || !is_string($ref['digest']) || !preg_match('/^[a-f0-9]{64}$/D', $ref['digest'])) { throw new \RuntimeException('NIR_REFERENCE'); }
    }

    public static function ref(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    public static function seal(array $r): array
    {
        unset($r['record_digest']);
        $r['record_digest'] = TransitionContract::digest($r);
        return $r;
    }

    private function eventPath(string $kind, string $id): string
    {
        if (!in_array($kind, ['principals', 'activations', 'revocations', 'decisions', 'authorities', 'successors', 'journals', 'transitions'], true)) {
            throw new \RuntimeException('NIR_EVENT_KIND');
        }
        self::id($id);
        $path = $this->root.'/'.self::DIRECTORY.'/'.$kind.'/'.$id;
        $this->safe($path);
        return $path;
    }

    private function safe(string $path): void
    {
        while (strlen($path) > strlen($this->root)) {
            if (is_link($path)) { throw new \RuntimeException('NIR_STORAGE_ALIAS'); }
            $path = dirname($path);
        }
    }
}
