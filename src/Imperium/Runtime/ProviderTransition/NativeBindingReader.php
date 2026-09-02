<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Authoritative operation interpretation. An admission-shaped array alone changes nothing. */
final readonly class NativeBindingReader
{
    public function __construct(private NativeState $state) {}

    public static function root(string $instance, string $binding, string $operation): string
    {
        foreach ([$instance, $binding, $operation] as $id) { NativeState::id($id); }
        return TransitionContract::digest(['instance' => $instance, 'binding' => $binding, 'operation' => $operation]);
    }

    public function read(string $instance, string $binding, string $operation, int $at): array
    {
        $root = self::root($instance, $binding, $operation);
        $commit = $this->state->get('transitions', $root);
        if (null === $commit) {
            if (null !== $this->state->get('journals', $root)) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/'.$binding.'.json');
            $this->state->source('binding', NativeState::ref($descriptor, 'binding_id'));
            if ($descriptor['binding_id'] !== $binding || $descriptor['instance_id'] !== $instance || 'BOUND_INACTIVE' !== $descriptor['status']) {
                throw new \RuntimeException('NIR_BINDING_SOURCE');
            }
            return ['root' => $root, 'effective_status' => 'BOUND_INACTIVE', 'descriptor' => NativeState::ref($descriptor, 'binding_id'), 'receipt' => null];
        }
        TransitionContract::keys($commit, ['schema', 'root', 'authority_id', 'records', 'committed_at', 'record_digest']);
        $plain = $commit; unset($plain['record_digest']);
        if ($commit['schema'] !== 'imperium.la-cortine.native-transition-commit/v1' || $commit['root'] !== $root
            || !is_int($commit['committed_at']) || $commit['committed_at'] > $at
            || $commit['record_digest'] !== TransitionContract::digest($plain)) { throw new \RuntimeException('NIR_COMMIT_INVALID'); }
        // Revalidate current native lifecycle as well as the original decision time.
        $authority = (new NativeAuthority($this->state))->load($commit['authority_id'], $at);
        $expected = (new NativeAdmission($this->state))->records($commit['authority_id'], $commit['committed_at']);
        if ($commit['records'] !== $expected || $authority['decision']['issuance_target']['root'] !== $root) { throw new \RuntimeException('NIR_BINDING_JOIN'); }
        return ['root' => $root, 'effective_status' => 'BOUND_ACTIVE_FOR_EXACT_OPERATION',
            'descriptor' => $expected['source_binding_transition']['binding'], 'receipt' => $expected['receipt_target']];
    }
}
