<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Read-only exact-root join. It cannot publish, consume, resolve or invoke. */
final readonly class NativeEffectAdmissionValidator
{
    public function __construct(private NativeState $state) {}

    public function inspect(array $authority, int $at): array
    {
        $this->assertAuthority($authority, $at);
        $root = NativeBindingReader::root(
            $authority['instance_id'],
            $authority['provider_binding']['id'],
            $authority['operation'],
        );
        if ($root !== $authority['native_root']) {
            throw new \RuntimeException('CNE201_NATIVE_ROOT_MISMATCH');
        }

        $read = (new NativeBindingReader($this->state))->read(
            $authority['instance_id'],
            $authority['provider_binding']['id'],
            $authority['operation'],
            $at,
        );
        if ('BOUND_ACTIVE_FOR_EXACT_OPERATION' !== $read['effective_status']
            || $read['root'] !== $root
            || $read['receipt'] !== $this->receiptRecord($authority['native_receipt'])) {
            throw new \RuntimeException('CNE202_NATIVE_RECEIPT_NOT_CURRENT');
        }

        $commit = $this->state->get('transitions', $root)
            ?? throw new \RuntimeException('CNE203_NATIVE_TRANSITION_ABSENT');
        if ($authority['native_transition'] !== $this->reference($commit, $root)
            || $authority['native_receipt'] !== NativeState::ref($commit['records']['receipt_target'], 'receipt_id')
            || $authority['successor'] !== $commit['records']['v3_admission']['completed_successor']
            || $authority['v3_admission'] !== NativeState::ref($commit['records']['v3_admission'], 'admission_boundary_id')
            || $authority['executor_principal'] !== $commit['records']['v3_admission']['executor_principal']
            || $authority['execution_boundary'] !== $commit['records']['v3_admission']['execution_boundary']) {
            throw new \RuntimeException('CNE204_NATIVE_LINEAGE_MISMATCH');
        }

        $descriptor = $this->state->source('binding', $authority['provider_binding']);
        if ('BOUND_INACTIVE' !== ($descriptor['status'] ?? null)
            || $authority['provider']['provider_id'] !== ($descriptor['provider_implementation']['provider_id'] ?? null)
            || $authority['provider']['adapter_id'] !== ($descriptor['provider_implementation']['adapter_id'] ?? null)
            || $authority['provider']['adapter_version'] !== ($descriptor['provider_implementation']['adapter_version'] ?? null)
            || $authority['credential_scope']['credential_family'] !== ($descriptor['credential_family']['family_id'] ?? null)) {
            throw new \RuntimeException('CNE205_PROVIDER_BINDING_MISMATCH');
        }

        return [
            'native_root' => $root,
            'native_receipt' => $authority['native_receipt'],
            'effect_authority' => NativeState::ref($authority, 'authority_id'),
            'effect_replay_identity' => self::replayIdentity($authority),
            'eligible_for_future_atomic_admission' => true,
            'effect_grant_used' => false,
            'credential_resolved' => false,
            'capability_consumed' => false,
            'provider_callback_permitted' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'retry_authorized' => false,
            'read_only' => true,
        ];
    }

    public static function replayIdentity(array $authority): string
    {
        return TransitionContract::digest([
            'native_root' => $authority['native_root'],
            'native_transition_digest' => $authority['native_transition']['digest'],
            'native_receipt_digest' => $authority['native_receipt']['digest'],
            'effect_authority_id' => $authority['authority_id'],
            'effect_authority_digest' => $authority['record_digest'],
            'operation' => $authority['operation'],
            'destination' => $authority['destination'],
            'payload_digest' => $authority['payload_digest'],
            'provider_id' => $authority['provider']['provider_id'],
            'adapter_id' => $authority['provider']['adapter_id'],
            'adapter_version' => $authority['provider']['adapter_version'],
            'credential_family' => $authority['credential_scope']['credential_family'],
            'expected_return_contract' => $authority['expected_return_contract'],
            'provider_idempotency_key_digest' => $authority['provider_idempotency_key_digest'],
        ]);
    }

    private function assertAuthority(array $authority, int $at): void
    {
        $plain = $authority;
        unset($plain['record_digest']);
        if (NativeEffectAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeState::seal($plain) !== $authority
            || NativeEffectAuthorityContract::OPERATION !== ($authority['operation'] ?? null)
            || NativeEffectAuthorityContract::REQUIRED_PROVIDER_FIELDS !== array_keys($authority['provider'] ?? [])
            || NativeEffectAuthorityContract::REQUIRED_CREDENTIAL_SCOPE_FIELDS !== array_keys($authority['credential_scope'] ?? [])
            || true !== ($authority['credential_scope']['stationary_same_process'] ?? null)
            || false !== ($authority['credential_scope']['cross_process_transfer_permitted'] ?? null)
            || false !== ($authority['credential_scope']['secret_persistence_permitted'] ?? null)
            || NativeEffectAuthorityContract::CONSUMER !== ($authority['holder'] ?? null)
            || true !== ($authority['single_use'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || null !== ($authority['revocation_reference'] ?? null)
            || null !== ($authority['cancellation_reference'] ?? null)
            || !is_int($authority['effective_at'] ?? null)
            || !is_int($authority['expires_at'] ?? null)
            || $at < $authority['effective_at'] || $at >= $authority['expires_at']) {
            throw new \RuntimeException('CNE200_EFFECT_AUTHORITY_INVALID');
        }
        foreach (['native_transition', 'native_receipt', 'successor', 'v3_admission', 'executor_principal', 'execution_boundary', 'provider_binding'] as $field) {
            NativeState::reference($authority[$field]);
        }
        foreach (['payload_digest', 'request_fingerprint', 'provider_idempotency_key_digest'] as $field) {
            if (!is_string($authority[$field]) || !preg_match('/^[a-f0-9]{64}$/D', $authority[$field])) {
                throw new \RuntimeException('CNE200_EFFECT_AUTHORITY_INVALID');
            }
        }
    }

    private function receiptRecord(array $reference): array
    {
        $root = $reference['id'];
        $prefix = 'receipt-';
        if (!str_starts_with($root, $prefix)) {
            throw new \RuntimeException('CNE202_NATIVE_RECEIPT_NOT_CURRENT');
        }
        $commit = $this->state->get('transitions', substr($root, strlen($prefix)))
            ?? throw new \RuntimeException('CNE203_NATIVE_TRANSITION_ABSENT');
        $receipt = $commit['records']['receipt_target'];
        if ($reference !== NativeState::ref($receipt, 'receipt_id')) {
            throw new \RuntimeException('CNE202_NATIVE_RECEIPT_NOT_CURRENT');
        }
        return $receipt;
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $id, 'schema' => $record['schema'], 'digest' => $record['record_digest']];
    }
}
