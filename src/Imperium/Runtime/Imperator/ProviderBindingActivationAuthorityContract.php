<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationAuthorityContract
{
    public const string SCHEMA = 'imperium.imperator.provider-binding-activation-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.exact-provider-binding-activation-decision';
    public const array CONSUMER_POSTURES = ['la-cortine.single-execution-provider-binding-activation'];
    public const array REQUIRED_FIELDS = [
        'schema', 'authority_id', 'instance_id', 'source_decision', 'tool_authority',
        'effect_authorization', 'execution_claim', 'provider_binding', 'assurance_profile',
        'destination_policy', 'scope', 'issued_at', 'expires_at', 'authority_single_use',
        'authority_exercisable', 'consumed', 'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_SCOPE_FIELDS = ['execution_id', 'operation', 'exact_destination', 'provider_substitution_permitted'];
    public const array NON_AUTHORITIES = [
        'selects_provider' => false,
        'mutates_provider_binding' => false,
        'issues_credential_capability' => false,
        'takes_capability_custody' => false,
        'delivers_capability' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
