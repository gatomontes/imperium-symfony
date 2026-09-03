<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Operator-declared complete local inventory; no inference from absent legacy grants. */
final readonly class NativeMigration
{
    public function __construct(private NativeState $state, private ?\Closure $checkpoint = null) {}

    public function locked(string $instance, callable $action): mixed
    {
        $m = $this->state->json(NativeState::TRUST.'/migration.json');
        TransitionContract::keys($m, ['schema', 'storage', 'instance', 'inventory_complete', 'legacy_directories']);
        if ($m['schema'] !== 'imperium.operator-root.transition-migration-inventory/v1'
            || $m['storage'] !== $this->state->identity() || $m['instance'] !== $instance
            || true !== $m['inventory_complete'] || !is_array($m['legacy_directories']) || !array_is_list($m['legacy_directories'])) {
            throw new \RuntimeException('NIR_MIGRATION_INVENTORY');
        }
        $stores = [];
        foreach ($m['legacy_directories'] as $relative) {
            if (!is_string($relative) || !preg_match('~^var/imperium/runtime/legacy-provider-transitions/[a-z0-9][a-z0-9._-]{2,100}$~D', $relative)
                || str_contains($relative, '..') || is_link($this->state->root.'/'.$relative)) { throw new \RuntimeException('NIR_LEGACY_PATH'); }
            $path = realpath($this->state->root.'/'.$relative);
            $base = realpath($this->state->root.'/var/imperium/runtime/legacy-provider-transitions');
            if (false === $path || false === $base || dirname($path) !== $base) { throw new \RuntimeException('NIR_LEGACY_PATH'); }
            $store = new TransitionStore($path, null === $this->checkpoint ? null : fn (string $cut) => ($this->checkpoint)('legacy.'.$cut));
            if (isset($stores[$store->identity()])) { throw new \RuntimeException('NIR_LEGACY_ALIAS'); }
            foreach (glob($path.'/*') ?: [] as $file) {
                if (!in_array(basename($file), ['domain.lock'], true)) { throw new \RuntimeException('NIR_LEGACY_STATE_NOT_EMPTY'); }
            }
            $stores[$store->identity()] = $store;
        }
        ksort($stores);
        $list = array_values($stores);
        $enter = function (int $n) use (&$enter, $list, $action, $m, $stores): mixed {
            if (isset($list[$n])) { return $list[$n]->locked(fn () => $enter($n + 1)); }
            foreach ($list as $store) {
                foreach (['grant', 'authority', 'journal', 'commit', 'revocation', 'refusal', 'retirement'] as $name) {
                    if ($store->pending($name) || null !== $store->read($name)) { throw new \RuntimeException('NIR_LEGACY_STATE_NOT_EMPTY'); }
                }
            }
            return $action($m, $stores);
        };
        return $enter(0);
    }
}
