<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Pure future identity derivation. No current admission service consumes it. */
final class NativeEffectSemanticIdentity
{
    public const string ADMISSION_PREFIX = 'canonical-native-effect-admission-';

    public static function tuple(array $authority): array
    {
        self::assertAuthorityShape($authority);

        return [
            'native_root' => $authority['native_root'],
            'native_transition' => $authority['native_transition'],
            'native_receipt' => $authority['native_receipt'],
            'successor' => $authority['successor'],
            'v3_admission' => $authority['v3_admission'],
            'executor_principal' => $authority['executor_principal'],
            'execution_boundary' => $authority['execution_boundary'],
            'provider_binding' => $authority['provider_binding'],
            'operation' => $authority['operation'],
            'destination' => $authority['destination'],
            'payload_digest' => $authority['payload_digest'],
            'request_fingerprint' => $authority['request_fingerprint'],
            'provider' => [
                'provider_id' => $authority['provider']['provider_id'],
                'adapter_id' => $authority['provider']['adapter_id'],
                'adapter_version' => $authority['provider']['adapter_version'],
                'assurance_admission' => $authority['provider']['assurance_admission'],
            ],
            'credential_family' => $authority['credential_scope']['credential_family'],
            'expected_return_contract' => $authority['expected_return_contract'],
            'provider_idempotency_key_digest' => $authority['provider_idempotency_key_digest'],
        ];
    }

    public static function tupleId(array $authority): string
    {
        return TransitionContract::digest(self::tuple($authority));
    }

    public static function authorityConsumptionId(array $authority): string
    {
        self::assertAuthorityShape($authority);

        return TransitionContract::digest([
            'semantic_effect_tuple_id' => self::tupleId($authority),
            'authority_id' => $authority['authority_id'],
            'authority_digest' => $authority['record_digest'],
        ]);
    }

    public static function admissionId(string $semanticEffectTupleId): string
    {
        self::digest($semanticEffectTupleId);

        return self::ADMISSION_PREFIX.$semanticEffectTupleId;
    }

    private static function assertAuthorityShape(array $authority): void
    {
        $plain = $authority;
        unset($plain['record_digest']);
        if (NativeEffectAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeState::seal($plain) !== $authority
            || NativeEffectAuthorityContract::REQUIRED_PROVIDER_FIELDS !== array_keys($authority['provider'] ?? [])
            || NativeEffectAuthorityContract::REQUIRED_CREDENTIAL_SCOPE_FIELDS !== array_keys($authority['credential_scope'] ?? [])
            || !is_string($authority['authority_id'] ?? null)
            || !is_string($authority['native_root'] ?? null)
            || !is_string($authority['operation'] ?? null)
            || !is_string($authority['destination'] ?? null)
            || !is_string($authority['request_fingerprint'] ?? null)
            || !is_string($authority['expected_return_contract'] ?? null)
            || !is_string($authority['provider']['provider_id'] ?? null)
            || !is_string($authority['provider']['adapter_id'] ?? null)
            || !is_string($authority['provider']['adapter_version'] ?? null)
            || !is_string($authority['credential_scope']['credential_family'] ?? null)) {
            throw new \RuntimeException('CNE110_SEMANTIC_IDENTITY_AUTHORITY_INVALID');
        }
        foreach (['native_transition', 'native_receipt', 'successor', 'v3_admission', 'executor_principal', 'execution_boundary', 'provider_binding'] as $field) {
            NativeState::reference($authority[$field]);
        }
        NativeState::reference($authority['provider']['assurance_admission']);
        foreach (['payload_digest', 'request_fingerprint', 'provider_idempotency_key_digest', 'record_digest'] as $field) {
            self::digest($authority[$field] ?? null);
        }
    }

    private static function digest(mixed $digest): void
    {
        if (!is_string($digest) || !preg_match('/^[a-f0-9]{64}$/D', $digest)) {
            throw new \RuntimeException('CNE111_SEMANTIC_IDENTITY_DIGEST_INVALID');
        }
    }
}
