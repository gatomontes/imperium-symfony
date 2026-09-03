<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\LaCortine\{DeterministicExecutionClaimContract, ProviderImplementationBindingContract};

/** Authoritative operation interpretation. An admission-shaped array alone changes nothing. */
final readonly class NativeBindingReader
{
    public function __construct(private NativeState $state) {}

    /** Read-only classification, never an execution or retry capability. */
    public function interpret(string $instance, string $binding, string $operation, int $at): array
    {
        $root = self::root($instance, $binding, $operation);
        $result = ['root' => $root, 'classification' => 'CORRUPT', 'descriptor' => null,
            'receipt' => null, 'read_only' => true, 'provider_effect_permitted' => false,
            'retry_authorized' => false, 'recovery' => 'UNKNOWN_REPLAY_PROHIBITED'];
        try {
            $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/'.$binding.'.json');
            $this->state->source('binding', NativeState::ref($descriptor, 'binding_id'));
            if (ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($descriptor)
                || ProviderImplementationBindingContract::SCHEMA !== $descriptor['schema']
                || $descriptor['binding_id'] !== $binding || $descriptor['instance_id'] !== $instance
                || true !== $descriptor['sealed'] || 'BOUND_INACTIVE' !== $descriptor['status']) {
                return $result;
            }
            $result['descriptor'] = NativeState::ref($descriptor, 'binding_id');
            if (($descriptor['scope']['operation'] ?? null) !== $operation) {
                $result['classification'] = 'UNRELATED_OPERATION';
                return $result;
            }
            $commit = $this->state->get('transitions', $root);
            $journal = $this->state->get('journals', $root);
            $proof = (new NativeReconstructor($this->state))->reconstruct($instance, $binding, $operation, $at);
            if (null === $commit) {
                $result['classification'] = null !== $journal || 'ABSENT' !== $proof['classification']
                    ? 'INCOMPLETE' : 'BOUND_INACTIVE';
            } elseif ('COMMITTED_NOT_CURRENT' === $proof['classification']) {
                $result['classification'] = 'COMMITTED_NOT_CURRENT';
                $result['receipt'] = $proof['receipt'];
            } elseif ('COMMITTED' === $proof['classification']) {
                $read = $this->read($instance, $binding, $operation, $at);
                $result['classification'] = 'COMMITTED_CURRENT';
                $result['receipt'] = $read['receipt'];
            }
            return $result;
        } catch (\Throwable $error) {
            if ('UNKNOWN_REPLAY_PROHIBITED' === $error->getMessage()) {
                $result['classification'] = 'INCOMPLETE';
            }
            return $result;
        }
    }

    /** Resolve from the stored claim, never a caller-selected binding or projection. */
    public function forClaim(string $claimId, int $at): array
    {
        if (!preg_match('/^deterministic-execution-claim-[a-f0-9]{20}$/D', $claimId)) {
            throw new \RuntimeException('CCI_CLAIM_INVALID');
        }
        $path = 'var/imperium/la-cortine/deterministic-execution-claims/'.$claimId.'.json';
        $claim = $this->state->json($path);
        if (DeterministicExecutionClaimContract::REQUIRED_FIELDS !== array_keys($claim)
            || DeterministicExecutionClaimContract::SCHEMA !== ($claim['schema'] ?? null)
            || $claimId !== ($claim['claim_id'] ?? null) || NativeState::seal($claim) !== $claim
            || true !== ($claim['sealed'] ?? null) || 'email.send' !== ($claim['request']['operation'] ?? null)
            || 'CLAIMED_PRE_IO' !== ($claim['effect']['checkpoint'] ?? null)
            || false !== ($claim['effect']['external_io_started'] ?? null)) {
            throw new \RuntimeException('CCI_CLAIM_INVALID');
        }
        $before = $this->bindingSnapshot();
        $matches = [];
        foreach (array_keys($before) as $id) {
            $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/'.$id.'.json');
            if (($descriptor['instance_id'] ?? null) === $claim['instance_id']
                && ($descriptor['scope']['operation'] ?? null) === $claim['request']['operation']
                && ($descriptor['scope']['authorization_target_id'] ?? null) === $claim['source_authorization']['id']
                && ($descriptor['scope']['authorization_target_digest'] ?? null) === $claim['source_authorization']['digest']) {
                $matches[] = $id;
            }
        }
        if (1 !== count($matches)) { throw new \RuntimeException('CCI_BINDING_ABSENT_OR_AMBIGUOUS'); }
        $result = $this->interpret($claim['instance_id'], $matches[0], $claim['request']['operation'], $at);
        if ($before !== $this->bindingSnapshot() || $claim !== $this->state->json($path)) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        // Transition root and one-message replay root remain separate, joined by this stored claim.
        $result['execution_claim'] = NativeState::ref($claim, 'claim_id');
        $result['execution_id'] = $claim['execution_identity']['execution_id'];
        $result['replay_fingerprint'] = $claim['replay_fingerprint'];
        return $result;
    }

    /** Old descriptor semantics are usable only before any native attempt for this exact root. */
    public function assertLegacy(array $descriptor): void
    {
        $instance = $descriptor['instance_id'] ?? null;
        $binding = $descriptor['binding_id'] ?? null;
        $operation = $descriptor['scope']['operation'] ?? null;
        if (!is_string($instance) || !is_string($binding) || !is_string($operation)) {
            throw new \RuntimeException('CCI_BINDING_INVALID');
        }
        NativeState::id($binding);
        $stored = $this->state->source('binding', NativeState::ref($descriptor, 'binding_id'));
        if ($stored !== $descriptor) { throw new \RuntimeException('CCI_BINDING_INVALID'); }
        // Native absence does not upgrade legacy validation; its caller still owns all old checks.
        $proof = (new NativeReconstructor($this->state))->reconstruct($instance, $binding, $operation, time());
        if ('ABSENT' !== $proof['classification']) {
            throw new \RuntimeException('CCI_NATIVE_STATE_PRECLUDES_LEGACY');
        }
    }

    private function bindingSnapshot(): array
    {
        $snapshot = [];
        foreach (glob($this->state->root.'/'.NativeState::SOURCES['binding'].'/*') ?: [] as $file) {
            if (!is_file($file) || is_link($file) || !str_ends_with($file, '.json')) {
                throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
            }
            $id = basename($file, '.json'); NativeState::id($id);
            $digest = hash_file('sha256', $file);
            if (false === $digest) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            $snapshot[$id] = $digest;
        }
        ksort($snapshot);
        return $snapshot;
    }

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
            $proof = (new NativeReconstructor($this->state))->reconstruct($instance, $binding, $operation, $at);
            if ('ABSENT' !== $proof['classification']) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/'.$binding.'.json');
            $this->state->source('binding', NativeState::ref($descriptor, 'binding_id'));
            if ($descriptor['binding_id'] !== $binding || $descriptor['instance_id'] !== $instance || 'BOUND_INACTIVE' !== $descriptor['status']) {
                throw new \RuntimeException('NIR_BINDING_SOURCE');
            }
            return ['root' => $root, 'effective_status' => 'BOUND_INACTIVE', 'descriptor' => NativeState::ref($descriptor, 'binding_id'), 'receipt' => null];
        }
        TransitionContract::keys($commit, ['schema', 'root', 'authority_id', 'journal', 'records', 'committed_at', 'record_digest']);
        $plain = $commit; unset($plain['record_digest']);
        $journal = $this->state->get('journals', $root) ?? throw new \RuntimeException('NIR_JOURNAL_ABSENT');
        if ($commit['journal'] !== NativeState::ref($journal, 'journal_id') || $journal['root'] !== $root
            || $journal['storage'] !== $this->state->identity() || $journal['prepared_at'] > $commit['committed_at']) { throw new \RuntimeException('NIR_JOURNAL_JOIN'); }
        if ($commit['schema'] !== 'imperium.la-cortine.native-transition-commit/v1' || $commit['root'] !== $root
            || !is_int($commit['committed_at']) || $commit['committed_at'] > $at
            || $commit['record_digest'] !== TransitionContract::digest($plain)) { throw new \RuntimeException('NIR_COMMIT_INVALID'); }
        // Revalidate current native lifecycle as well as the original decision time.
        $authority = (new NativeAuthority($this->state))->load($commit['authority_id'], $at);
        $expected = (new NativeAdmission($this->state))->records($commit['authority_id'], $commit['committed_at']);
        if ($commit['records'] !== $expected || $authority['decision']['issuance_target']['root'] !== $root) { throw new \RuntimeException('NIR_BINDING_JOIN'); }
        $proof = (new NativeReconstructor($this->state))->reconstruct($instance, $binding, $operation, $at);
        if ('COMMITTED' !== $proof['classification'] || $proof['receipt'] !== $expected['receipt_target']) { throw new \RuntimeException('NIR_INDEPENDENT_RECONSTRUCTION_REQUIRED'); }
        return ['root' => $root, 'effective_status' => 'BOUND_ACTIVE_FOR_EXACT_OPERATION',
            'descriptor' => $expected['source_binding_transition']['binding'], 'receipt' => $expected['receipt_target']];
    }
}
