<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\LaCortine\{DeterministicExecutionClaimContract, ProviderImplementationBindingContract};
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceContract as Issuance;
use App\Imperium\Runtime\LaCortine\DeterministicOutboundEmailAuthorizationContract as EmailAuthority;

/** Authoritative operation interpretation. An admission-shaped array alone changes nothing. */
final class NativeBindingReader
{
    private static array $legacyScopes = [];
    public function __construct(private readonly NativeState $state) {}

    /** Read-only classification, never an execution or retry capability. */
    public function interpret(string $instance, string $binding, string $operation, int $at): array
    {
        $root = self::root($instance, $binding, $operation);
        $result = ['root' => $root, 'classification' => 'CORRUPT', 'descriptor' => null,
            'receipt' => null, 'read_only' => true, 'provider_effect_permitted' => false,
            'retry_authorized' => false, 'recovery' => 'UNKNOWN_REPLAY_PROHIBITED'];
        try {
            $descriptor = $this->state->json(NativeState::SOURCES['binding'].'/'.$binding.'.json');
            if (ProviderImplementationBindingContract::REQUIRED_FIELDS !== array_keys($descriptor)
                || ProviderImplementationBindingContract::SCHEMA !== $descriptor['schema']
                || $descriptor['binding_id'] !== $binding || $descriptor['instance_id'] !== $instance
                || true !== $descriptor['sealed'] || 'BOUND_INACTIVE' !== $descriptor['status']) {
                return $result;
            }
            $this->state->source('binding', NativeState::ref($descriptor, 'binding_id'));
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
        $nativeBefore = $this->hasNativeState();
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
        if ([] === $matches && !$this->hasNativeState() && [] === $before) {
            // The old unbound claim protocol has no descriptor/root interpretation to upgrade.
            // It is unavailable as soon as binding or native state exists in this storage.
            $result = ['root' => null, 'classification' => 'LEGACY_UNBOUND', 'descriptor' => null,
                'receipt' => null, 'read_only' => true, 'provider_effect_permitted' => false,
                'retry_authorized' => false, 'recovery' => 'UNKNOWN_REPLAY_PROHIBITED'];
        } else {
            if (1 !== count($matches)) { throw new \RuntimeException('CCI_BINDING_ABSENT_OR_AMBIGUOUS'); }
            $this->assertBoundClaim($claim);
            $result = $this->interpret($claim['instance_id'], $matches[0], $claim['request']['operation'], $at);
        }
        if ($nativeBefore !== $this->hasNativeState() || $before !== $this->bindingSnapshot() || $claim !== $this->state->json($path)) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
        // Transition root and one-message replay root remain separate, joined by this stored claim.
        $result['execution_claim'] = NativeState::ref($claim, 'claim_id');
        $result['execution_id'] = $claim['execution_identity']['execution_id'];
        $result['replay_fingerprint'] = $claim['replay_fingerprint'];
        return $result;
    }

    /** Same outer scope as NativeState::locked; legacy scopes are acquired only inside it. */
    public function legacy(callable $action): mixed
    {
        $scope = $this->state->identity();
        if (isset(self::$legacyScopes[$scope])) { return $action(); }
        return (new AtomicTransition($this->state->root))->run('native-provider-transition', function () use ($scope, $action): mixed {
            self::$legacyScopes[$scope] = true;
            try { return $action(); } finally { unset(self::$legacyScopes[$scope]); }
        });
    }

    public function forJournal(array $journal, int $at): array
    {
        $id = $journal['journal_id'] ?? null;
        if (!is_string($id) || !preg_match('/^deterministic-effect-start-journal-[a-f0-9]{20}$/D', $id)
            || NativeState::seal($journal) !== $journal
            || $journal !== $this->state->json('var/imperium/la-cortine/deterministic-effect-start-journals/'.$id.'.json')) {
            throw new \RuntimeException('CCI_JOURNAL_INVALID');
        }
        $result = $this->forClaim($journal['execution_claim']['id'], $at);
        if ($result['execution_claim']['digest'] !== $journal['execution_claim']['digest']) {
            throw new \RuntimeException('CCI_JOURNAL_CLAIM_MISMATCH');
        }
        return $result;
    }

    public function hasNativeState(): bool
    {
        return file_exists($this->state->root.'/'.NativeState::DIRECTORY)
            || is_link($this->state->root.'/'.NativeState::DIRECTORY)
            || file_exists($this->state->root.'/var/imperium/runtime/legacy-provider-transitions');
    }

    /** Check input/cached records before any legacy consumption, including nested authorities. */
    public function assertLegacyRecord(array $record): void
    {
        if (!$this->hasNativeState()) { return; }
        if (ProviderImplementationBindingContract::SCHEMA === ($record['schema'] ?? null)) {
            $this->assertLegacy($record);
            return;
        }
        foreach ($record as $key => $value) {
            if (!is_array($value)) { continue; }
            if ('provider_binding' === $key) {
                $descriptor = $this->state->source('binding', $value);
                $this->assertLegacy($descriptor);
            } else {
                $this->assertLegacyRecord($value);
            }
        }
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
        $directory = $this->state->root.'/'.NativeState::SOURCES['binding'];
        if (is_link($directory) || (file_exists($directory) && !is_dir($directory))) {
            throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED');
        }
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

    /** Backward source joins and producer-derived replay identity, never configured provenance. */
    private function assertBoundClaim(array $claim): void
    {
        $matches = [];
        $directory = 'var/imperium/imperator/outbound-email-authorization-issuances';
        foreach (glob($this->state->root.'/'.$directory.'/*.json') ?: [] as $path) {
            $record = $this->state->json($directory.'/'.basename($path));
            $a = $record['issued_authorization'] ?? [];
            if (($a['authorization_id'] ?? null) === ($claim['source_authorization']['id'] ?? null)) { $matches[] = $record; }
        }
        if (1 !== count($matches)) { throw new \RuntimeException('CCI_CLAIM_ISSUANCE_ABSENT_OR_AMBIGUOUS'); }
        $r = $matches[0]; $a = $r['issued_authorization'];
        if (Issuance::REQUIRED_ISSUANCE_FIELDS !== array_keys($r) || Issuance::ISSUANCE_SCHEMA !== $r['schema']
            || NativeState::seal($r) !== $r || EmailAuthority::REQUIRED_FIELDS !== array_keys($a)
            || EmailAuthority::SCHEMA !== $a['schema'] || NativeState::seal($a) !== $a
            || $a['record_digest'] !== $claim['source_authorization']['digest']
            || $a['instance_id'] !== $claim['instance_id'] || $r['instance_id'] !== $claim['instance_id']
            || true !== $r['authority_issued'] || false !== $r['external_action_performed']
            || true !== $a['single_use'] || false !== $a['continuing_authority']
            || EmailAuthority::REQUIRED_SCOPE_FIELDS !== array_keys($a['scope'])
            || EmailAuthority::REQUIRED_PROVIDER_SAFETY_FIELDS !== array_keys($a['provider_safety'])) {
            throw new \RuntimeException('CCI_CLAIM_ISSUANCE_INVALID');
        }
        $scope = $a['scope']; $provider = $a['provider_safety'];
        $request = ['id' => $r['source_request']['id'], 'commission_id' => $scope['commission_id'],
            'authorization_id' => $a['authorization_id'], 'authorization_digest' => $a['record_digest'],
            'mode' => 'DETERMINISTIC', 'operation' => $scope['operation'], 'destination' => $scope['destination'],
            'payload_digest' => $scope['payload_digest'], 'expected_return_contract' => $scope['expected_return_contract']];
        $fingerprint = TransitionContract::digest([$a['record_digest'], $request, $claim['credential_capability'], $provider['request_fingerprint'], $provider['idempotency_key_digest']]);
        $id = 'deterministic-execution-claim-'.substr(TransitionContract::digest([$a['authorization_id'], $fingerprint]), 0, 20);
        if ($request !== $claim['request'] || $fingerprint !== $claim['replay_fingerprint'] || $id !== $claim['claim_id']
            || 'deterministic-execution-'.substr(hash('sha256', $id), 0, 20) !== $claim['execution_identity']['execution_id']
            || 'authorization:'.$a['authorization_id'] !== $claim['execution_identity']['winner_scope']
            || $scope['credential_reference_digest'] !== $claim['credential_capability']['credential_reference_digest']
            || $provider['idempotency_key'] !== $claim['provider_safety']['provider_idempotency_key']) {
            throw new \RuntimeException('CCI_CLAIM_REPLAY_JOIN_INVALID');
        }
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
