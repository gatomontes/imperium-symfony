<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class SortieLifecycle
{
    /** @var array<string, string> */
    private array $states = [];

    public function register(SortieManifest $manifest): void
    {
        if (isset($this->states[$manifest->sortieId])) {
            throw new \RuntimeException('SORTIE_REUSE_FORBIDDEN: sortie identity already exists.');
        }

        $this->states[$manifest->sortieId] = 'created';
    }

    public function deploy(SortieManifest $manifest, \DateTimeImmutable $now): void
    {
        $this->requireState($manifest->sortieId, 'created');
        if ($now >= $manifest->expiresAt) {
            $this->states[$manifest->sortieId] = 'retired';
            throw new \RuntimeException('SORTIE_EXPIRED: expired sortie cannot deploy.');
        }

        $this->states[$manifest->sortieId] = 'deployed';
    }

    public function markReturned(SortieManifest $manifest): void
    {
        $this->requireState($manifest->sortieId, 'deployed');
        $this->states[$manifest->sortieId] = 'returned';
    }

    public function retire(SortieManifest $manifest): void
    {
        $state = $this->states[$manifest->sortieId] ?? null;
        if (!in_array($state, ['created', 'deployed', 'returned'], true)) {
            throw new \RuntimeException('SORTIE_RETIREMENT_INVALID: sortie is absent or already terminal.');
        }

        $this->states[$manifest->sortieId] = 'retired';
    }

    public function state(SortieManifest $manifest): string
    {
        return $this->states[$manifest->sortieId] ?? 'unknown';
    }

    private function requireState(string $sortieId, string $expected): void
    {
        $actual = $this->states[$sortieId] ?? null;
        if ($expected !== $actual) {
            throw new \RuntimeException(sprintf('SORTIE_STATE_INVALID: expected %s, got %s.', $expected, $actual ?? 'absent'));
        }
    }
}
