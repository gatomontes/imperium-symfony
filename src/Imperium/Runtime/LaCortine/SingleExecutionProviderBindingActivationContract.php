<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class SingleExecutionProviderBindingActivationContract
{
    public const string SCHEMA = 'imperium.la-cortine.single-execution-provider-binding-activation/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'la-cortine.provider-binding-activation-transition';
    public const array CONSUMER_POSTURES = [
        'clavium.opaque-capability-custody-intake',
        'la-cortine.atomic-provider-execution-admission',
        'la-cortine.activation-reconstructor',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'activation_id', 'instance_id', 'source_authority', 'tool_authority',
        'effect_authorization', 'execution_claim', 'provider_binding', 'assurance_profile',
        'destination_policy', 'scope', 'activation_authority_consumption', 'status',
        'activated_at', 'expires_at', 'single_execution', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_SCOPE_FIELDS = ['execution_id', 'operation', 'exact_destination', 'provider_substitution_permitted'];
    public const array STATUSES = ['ACTIVATED_UNCONSUMED', 'CONSUMED_PRE_IO', 'EXPIRED', 'REVOKED'];
    public const array NON_AUTHORITIES = [
        'changes_provider_selection' => false,
        'mutates_source_binding' => false,
        'issues_activation_authority' => false,
        'issues_credential_capability' => false,
        'proves_capability_custody' => false,
        'delivers_capability' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
