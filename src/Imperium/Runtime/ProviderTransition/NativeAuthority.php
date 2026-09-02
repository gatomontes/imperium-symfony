<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Deterministic native principal attribution and backward-only authority seals. */
final readonly class NativeAuthority
{
    public function __construct(private NativeState $state, private ?\Closure $clock = null) {}

    public function issue(string $principalId, ?string $successorId = null): array
    {
        return $this->state->locked(function () use ($principalId, $successorId): array {
            $at = null === $this->clock ? time() : ($this->clock)();
            $p = (new NativePrincipal($this->state))->load($principalId, $at);
            $successor = null === $successorId ? null : (new NativeSuccessor($this->state))->load($successorId, $at);
            if (null !== $successor && $successor['principal'] !== NativeState::ref($p, 'principal_version_id')) { throw new \RuntimeException('NIR_AUTHORITY_SUCCESSOR_PRINCIPAL'); }
            $chain = $this->chain($p, $at, $successor);
            // Decision, issuance target, custody and single-use authority are one publication.
            return $this->state->put('authorities', $chain['authority']['authority_id'], $chain);
        });
    }

    public function load(string $id, int $at): array
    {
        $r = $this->state->get('authorities', $id) ?? throw new \RuntimeException('NIR_AUTHORITY_ABSENT');
        if (!is_int($r['at'] ?? null) || $r['at'] > $at) { throw new \RuntimeException('NIR_AUTHORITY_TIME'); }
        $p = (new NativePrincipal($this->state))->load($r['principal']['id'] ?? '', $at);
        (new NativePrincipal($this->state))->load($r['principal']['id'] ?? '', $r['at']);
        $successor = null === ($r['decision']['successor'] ?? null) ? null : (new NativeSuccessor($this->state))->load($r['decision']['successor']['id'], $at);
        if ($r !== $this->chain($p, $r['at'], $successor) || $id !== $r['authority']['authority_id']) { throw new \RuntimeException('NIR_AUTHORITY_LINEAGE'); }
        return $r;
    }

    private function chain(array $p, int $at, ?array $successor): array
    {
        $id = 'native-authority-'.TransitionContract::digest([$p['instance_id'], $p['principal_version_id'], $p['operation'], $p['provider_binding'], $successor['successor']['record_digest'] ?? null]);
        $root = TransitionContract::digest(['instance' => $p['instance_id'], 'binding' => $p['provider_binding']['id'], 'operation' => $p['operation']]);
        $target = ['authority_id' => $id, 'consumer' => TransitionContract::CONSUMER, 'transition' => TransitionContract::SCOPE,
            'root' => $root, 'authority_single_use' => true];
        $decision = NativeState::seal(['schema' => 'imperium.imperator.native-transition-decision/v1', 'decision_id' => 'decision-'.$root,
            'principal' => NativeState::ref($p, 'principal_version_id'), 'binding' => $p['provider_binding'],
            'operation' => $p['operation'], 'issuance_target' => $target,
            'successor' => null === $successor ? null : NativeState::ref($successor['successor'], 'successor_id'),
            'creation_winner' => null === $successor ? null : NativeState::ref($successor['creation_winner'], 'winner_boundary_id'),
            'at' => $at, 'expires_at' => $p['expires_at'],
            'disposition' => 'AUTHORIZED_EXACT_TRANSITION', 'continuing_authority' => false]);
        $custody = NativeState::seal(['schema' => 'imperium.imperator.native-transition-custody/v1', 'custody_id' => 'custody-'.$root,
            'source_decision' => NativeState::ref($decision, 'decision_id'), 'target' => $target,
            'delivery' => 'EXACT_CONSUMER_LOAD_ONLY', 'serialized_capability_persisted' => false]);
        $authority = NativeState::seal(['schema' => 'imperium.imperator.native-transition-authority/v1', 'authority_id' => $id,
            'source_decision' => NativeState::ref($decision, 'decision_id'), 'custody' => NativeState::ref($custody, 'custody_id'),
            'issuance_target' => $target, 'authority_single_use' => true, 'continuing_authority' => false]);
        return ['schema' => 'imperium.imperator.native-transition-issuance/v1', 'principal' => NativeState::ref($p, 'principal_version_id'),
            'decision' => $decision, 'custody' => $custody, 'authority' => $authority, 'at' => $at];
    }
}
