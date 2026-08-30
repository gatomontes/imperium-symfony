<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class DurableProviderExecutionAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.durable-provider-execution-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.exact-provider-execution-authority-issuance';
    public const array CONSUMER_POSTURES = [
        'la-cortine.provider-execution-admission',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'source_decision', 'execution_boundary',
        'executor_principal', 'tool_authority', 'effect_authorization',
        'provider_binding_activation', 'provider_binding', 'request', 'destination_policy',
        'assurance_profile', 'scope', 'validity', 'authority_single_use',
        'authority_exercisable', 'consumed', 'continuing_authority', 'issued_at',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_REQUEST_FIELDS = [
        'request_id', 'commission_id', 'operation', 'exact_destination',
        'payload_digest', 'request_fingerprint',
    ];
    public const array REQUIRED_SCOPE_FIELDS = [
        'execution_id', 'provider_id', 'adapter_id', 'credential_family',
        'provider_substitution_permitted', 'payload_substitution_permitted',
        'destination_substitution_permitted',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at', 'expires_at', 'revocation_reference',
    ];
    public const array NON_AUTHORITIES = [
        'selects_provider' => false,
        'installs_executor_principal' => false,
        'defines_execution_boundary' => false,
        'activates_provider_binding' => false,
        'consumes_itself' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
