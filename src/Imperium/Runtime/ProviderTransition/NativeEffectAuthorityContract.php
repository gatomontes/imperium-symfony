<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative only: this schema neither issues nor consumes authority. */
final class NativeEffectAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.canonical-native-effect-authority/v1';
    public const string OPERATION = 'email.send';
    public const string CONSUMER = 'la-cortine.canonical-native-effect-consumer/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'native_root', 'native_transition',
        'native_receipt', 'successor', 'v3_admission', 'executor_principal',
        'execution_boundary', 'provider_binding', 'operation', 'destination',
        'payload_digest', 'request_fingerprint', 'provider', 'credential_scope',
        'expected_return_contract', 'provider_idempotency_key_digest', 'holder',
        'issuer', 'effective_at', 'expires_at', 'revocation_reference',
        'cancellation_reference', 'single_use', 'consumed',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_PROVIDER_FIELDS = [
        'provider_id', 'adapter_id', 'adapter_version', 'assurance_admission',
    ];
    public const array REQUIRED_CREDENTIAL_SCOPE_FIELDS = [
        'credential_family', 'stationary_same_process',
        'cross_process_transfer_permitted', 'secret_persistence_permitted',
    ];
}
