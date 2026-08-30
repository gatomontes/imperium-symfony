<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class SingleOperationProviderBindingActivationContract
{
    public const string SCHEMA = 'imperium.la-cortine.single-operation-provider-binding-activation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.single-operation-provider-binding-activation-transition';
    public const array CONSUMER_POSTURES = [
        'imperator.durable-provider-execution-authority',
        'la-cortine.provider-execution-admission',
        'la-cortine.provider-execution-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'activation_id', 'instance_id', 'source_activation_authority',
        'execution_boundary', 'executor_principal', 'provider_binding', 'tool_authority',
        'effect_authorization', 'request', 'assurance_profile', 'destination_policy',
        'scope', 'activation_authority_consumption', 'status', 'activated_at',
        'expires_at', 'single_operation', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_REQUEST_FIELDS = [
        'request_id', 'operation', 'exact_destination', 'payload_digest',
        'request_fingerprint',
    ];
    public const array REQUIRED_SCOPE_FIELDS = [
        'execution_id', 'provider_id', 'adapter_id',
        'provider_substitution_permitted', 'request_substitution_permitted',
    ];
    public const array REQUIRED_CONSUMPTION_FIELDS = [
        'authority_id', 'authority_digest', 'consumed_at', 'consumed',
        'continuing_authority',
    ];
    public const array STATUSES = [
        'ACTIVATED_UNCONSUMED', 'CONSUMED_PRE_RESOLUTION_PRE_IO', 'EXPIRED', 'REVOKED',
    ];
    public const array NON_AUTHORITIES = [
        'changes_provider_selection' => false,
        'mutates_source_binding' => false,
        'issues_activation_authority' => false,
        'issues_execution_authority' => false,
        'installs_executor_principal' => false,
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
