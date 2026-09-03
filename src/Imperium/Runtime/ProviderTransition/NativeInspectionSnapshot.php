<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Read-only optimistic observation boundary. Its manifest is never authority. */
final class NativeInspectionSnapshot
{
    private const int MAX_ATTEMPTS = 2;
    private const array BASES = [
        'var/imperium/la-cortine/deterministic-execution-claims',
        'var/imperium/imperator/outbound-email-authorization-issuances',
        'var/imperium/la-cortine/deterministic-effect-start-journals',
        NativeState::DIRECTORY,
        NativeState::TRUST,
        'var/imperium/runtime/legacy-provider-transitions',
    ];

    /** @var array<string, true> */
    private static array $active = [];

    public function __construct(private readonly NativeState $state, private readonly ?\Closure $checkpoint = null) {}

    public function observe(callable $action): mixed
    {
        $scope = $this->state->identity();
        if (isset(self::$active[$scope])) {
            return $action();
        }

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; ++$attempt) {
            try {
                $before = $this->manifest();
                $this->checkpoint('manifest_a', $attempt);
            } catch (\Throwable) {
                continue;
            }

            self::$active[$scope] = true;
            $result = null;
            $error = null;
            try {
                $result = $action();
            } catch (\Throwable $caught) {
                $error = $caught;
            } finally {
                unset(self::$active[$scope]);
            }

            try {
                $this->checkpoint('before_manifest_b', $attempt);
                $after = $this->manifest();
            } catch (\Throwable) {
                continue;
            }

            if ($before !== $after) {
                $this->checkpoint('unstable', $attempt);
                continue;
            }
            if (null !== $error) {
                throw $error;
            }
            return $result;
        }

        throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
    }

    /** @return array<string, string> */
    private function manifest(): array
    {
        $manifest = [];
        $bases = array_values(array_unique([...self::BASES, ...array_values(NativeState::SOURCES)]));
        sort($bases);
        foreach ($bases as $relative) {
            $this->scan($this->state->root.'/'.$relative, str_replace('\\', '/', $relative), $manifest);
        }
        ksort($manifest);
        return $manifest;
    }

    /** @param array<string, string> $manifest */
    private function scan(string $path, string $relative, array &$manifest): void
    {
        clearstatcache(true, $path);
        if (is_link($path)) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        if (!file_exists($path)) {
            $manifest[$relative] = 'absent';
            return;
        }
        if (is_dir($path)) {
            $manifest[$relative] = 'directory';
            $entries = @scandir($path);
            if (false === $entries) {
                throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
            }
            $entries = array_values(array_diff($entries, ['.', '..']));
            sort($entries);
            foreach ($entries as $entry) {
                $this->scan($path.'/'.$entry, $relative.'/'.$entry, $manifest);
            }
            return;
        }
        if (!is_file($path)) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        if (str_ends_with($path, '.lock')) {
            return;
        }
        $digest = @hash_file('sha256', $path);
        if (false === $digest) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        clearstatcache(true, $path);
        if (!is_file($path) || is_link($path)) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        $manifest[$relative] = 'file:'.$digest;
    }

    private function checkpoint(string $cut, int $attempt): void
    {
        if (null !== $this->checkpoint) {
            ($this->checkpoint)('inspection.'.$cut, $attempt);
        }
    }
}
