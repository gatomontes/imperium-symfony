<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CodexImperiiStore
{
    private const string PATH = 'var/imperium/codex-imperii.json';

    private MutableStateStore $state;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        private AtomicTransition $atomic,
        ?MutableStateStore $state = null,
    ) {
        $this->state = $state ?? new MutableStateStore($root, $atomic);
    }

    public function initialize(string $instanceId, string $checkpoint, array $folia): array
    {
        $instanceId = trim($instanceId);
        $checkpoint = trim($checkpoint);
        $folia = $this->validateFolia($folia, 1);
        if ('' === $instanceId || '' === $checkpoint || [] === $folia) {
            throw new \InvalidArgumentException('CDI100_CODEX_INITIALIZATION_INVALID');
        }

        return $this->atomic->run('codex-imperii', function () use ($instanceId, $checkpoint, $folia): array {
            $candidate = $this->record($instanceId, $checkpoint, $folia, 1, null);
            try {
                $current = $this->state->read(self::PATH);
                if (CanonicalJson::encode($current) !== CanonicalJson::encode($this->withDigest($candidate))) {
                    throw new \RuntimeException('CDI109_CODEX_CONFLICT');
                }

                return $current;
            } catch (\RuntimeException $error) {
                if ('PST122_MUTABLE_STATE_ABSENT' !== $error->getMessage()) {
                    throw $error;
                }
            }

            return $this->state->compareAndSwap(self::PATH, null, $candidate);
        });
    }

    public function advance(string $instanceId, string $expectedCheckpoint, string $nextCheckpoint, array $newFolia): array
    {
        $instanceId = trim($instanceId);
        $expectedCheckpoint = trim($expectedCheckpoint);
        $nextCheckpoint = trim($nextCheckpoint);
        if ('' === $instanceId || '' === $expectedCheckpoint || '' === $nextCheckpoint || $expectedCheckpoint === $nextCheckpoint) {
            throw new \InvalidArgumentException('CDI101_CODEX_ADVANCE_INVALID');
        }

        return $this->atomic->run('codex-imperii', function () use ($instanceId, $expectedCheckpoint, $nextCheckpoint, $newFolia): array {
            $current = $this->state->read(self::PATH);
            $this->validateCodex($current);
            if ($instanceId !== $current['instance_id']) {
                throw new \RuntimeException('CDI102_CODEX_INSTANCE_MISMATCH');
            }
            if ($nextCheckpoint === $current['current_checkpoint']) {
                $fingerprint = $this->transitionFingerprint($instanceId, $expectedCheckpoint, $nextCheckpoint, $newFolia);
                if (hash_equals($current['last_advance_fingerprint'] ?? '', $fingerprint)) {
                    return $current;
                }
                throw new \RuntimeException('CDI109_CODEX_CONFLICT');
            }
            if ($expectedCheckpoint !== $current['current_checkpoint']) {
                throw new \RuntimeException('CDI103_CODEX_CHECKPOINT_MISMATCH');
            }

            $newFolia = $this->validateFolia($newFolia, count($current['folia']) + 1);
            if ([] === $newFolia) {
                throw new \InvalidArgumentException('CDI101_CODEX_ADVANCE_INVALID');
            }
            $all = [...$current['folia'], ...$newFolia];
            $this->assertUnique($all);
            $next = $this->record(
                $instanceId,
                $nextCheckpoint,
                $all,
                $current['generation'] + 1,
                $this->transitionFingerprint($instanceId, $expectedCheckpoint, $nextCheckpoint, $newFolia),
            );

            return $this->state->compareAndSwap(self::PATH, $current['record_digest'], $next);
        });
    }

    public function read(): array
    {
        $codex = $this->state->read(self::PATH);
        $this->validateCodex($codex);

        return $codex;
    }

    private function record(
        string $instanceId,
        string $checkpoint,
        array $folia,
        int $generation,
        ?string $lastAdvanceFingerprint,
    ): array
    {
        return [
            'schema' => 'imperium.codex-imperii/v1',
            'codex_id' => 'codex-imperii-'.substr(hash('sha256', $instanceId), 0, 20),
            'instance_id' => $instanceId,
            'generation' => $generation,
            'current_checkpoint' => $checkpoint,
            'last_advance_fingerprint' => $lastAdvanceFingerprint,
            'folia' => $folia,
        ];
    }

    private function validateFolia(array $folia, int $firstSequence): array
    {
        if (!array_is_list($folia)) {
            throw new \InvalidArgumentException('CDI104_FOLIA_INVALID');
        }
        $keys = ['digest', 'folium_id', 'folium_schema', 'office', 'relation', 'sequence', 'storage_reference'];
        foreach ($folia as $offset => $folium) {
            if (!is_array($folium)) {
                throw new \InvalidArgumentException('CDI104_FOLIA_INVALID');
            }
            $actual = array_keys($folium);
            sort($actual, SORT_STRING);
            if ($keys !== $actual
                || $firstSequence + $offset !== $folium['sequence']
                || !$this->text($folium['folium_id'])
                || !$this->text($folium['folium_schema'])
                || !$this->text($folium['office'])
                || !$this->text($folium['relation'])
                || !$this->text($folium['storage_reference'])
                || str_contains($folium['storage_reference'], '..')
                || !preg_match('/^[a-f0-9]{64}$/', $folium['digest'])) {
                throw new \InvalidArgumentException('CDI104_FOLIA_INVALID');
            }
        }
        $this->assertUnique($folia);

        return $folia;
    }

    private function assertUnique(array $folia): void
    {
        $ids = array_column($folia, 'folium_id');
        $references = array_column($folia, 'storage_reference');
        if (count($ids) !== count(array_unique($ids)) || count($references) !== count(array_unique($references))) {
            throw new \RuntimeException('CDI105_FOLIUM_DUPLICATE');
        }
    }

    private function validateCodex(array $codex): void
    {
        if ('imperium.codex-imperii/v1' !== ($codex['schema'] ?? null)
            || !preg_match('/^codex-imperii-[a-f0-9]{20}$/', $codex['codex_id'] ?? '')
            || !$this->text($codex['instance_id'] ?? null)
            || !$this->text($codex['current_checkpoint'] ?? null)
            || !is_int($codex['generation'] ?? null)
            || $codex['generation'] < 1
            || !array_key_exists('last_advance_fingerprint', $codex)
            || !(null === ($codex['last_advance_fingerprint'] ?? null)
                || (is_string($codex['last_advance_fingerprint'])
                    && preg_match('/^[a-f0-9]{64}$/', $codex['last_advance_fingerprint'])))
            || !is_array($codex['folia'] ?? null)) {
            throw new \RuntimeException('CDI106_CODEX_INVALID');
        }
        $this->validateFolia($codex['folia'], 1);
    }

    private function withDigest(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function transitionFingerprint(
        string $instanceId,
        string $expectedCheckpoint,
        string $nextCheckpoint,
        array $newFolia,
    ): string {
        return hash('sha256', CanonicalJson::encode([
            'instance_id' => $instanceId,
            'expected_checkpoint' => $expectedCheckpoint,
            'next_checkpoint' => $nextCheckpoint,
            'new_folia' => $newFolia,
        ]));
    }

    private function text(mixed $value): bool
    {
        return is_string($value) && '' !== trim($value);
    }
}
