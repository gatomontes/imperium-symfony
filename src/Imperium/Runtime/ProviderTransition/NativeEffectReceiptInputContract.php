<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Immutable admitted meaning for future callback and receipt construction. */
final class NativeEffectReceiptInputContract
{
    public const string SCHEMA = 'imperium.la-cortine.canonical-native-effect-receipt-input/v1';
    public const array REQUIRED_FIELDS = [
        'schema', 'semantic_effect_tuple_id', 'authority_consumption_id',
        'effect_authority', 'native_root', 'native_transition', 'native_receipt',
        'successor', 'v3_admission', 'executor_principal',
        'execution_boundary', 'provider_binding', 'provider_request',
        'provider', 'credential_scope', 'expected_return_contract',
        'admitted_at', 'expires_at', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_PROVIDER_REQUEST_FIELDS = [
        'operation', 'destination', 'payload_digest', 'request_fingerprint',
        'provider_idempotency_key_digest',
    ];
    public const array REQUIRED_PROVIDER_FIELDS = [
        'provider_id', 'adapter_id', 'adapter_version', 'assurance_admission',
    ];
    public const array REQUIRED_CREDENTIAL_SCOPE_FIELDS = ['credential_family'];
    public const array PROHIBITED_CALLER_INPUTS = [
        'authority', 'authority_digest', 'expected_return_contract',
        'provider_id', 'adapter_id', 'adapter_version', 'destination',
    ];
}
