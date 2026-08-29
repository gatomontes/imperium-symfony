<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationIssuanceContract
{
    public const string DECISION_SCHEMA = 'imperium.imperator.provider-binding-activation-decision/v1';
    public const string ISSUANCE_SCHEMA = 'imperium.imperator.provider-binding-activation-authority-issuance/v1';
    public const int VERSION = 1;
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_DECISION_FIELDS = [
        'schema', 'decision_id', 'instance_id', 'source_effect_authorization', 'execution_claim',
        'provider_binding', 'actor', 'disposition', 'rationale', 'limitations',
        'issuance_authority', 'decided_at', 'expires_at', 'external_action_performed', 'sealed',
        'record_digest',
    ];
    public const array REQUIRED_ISSUANCE_AUTHORITY_FIELDS = [
        'authority_id', 'authority_single_use', 'authority_exercisable', 'issuer_service',
        'permitted_transition', 'execution_claim_digest',
        'provider_binding_digest', 'expires_at', 'consumed', 'continuing_authority',
    ];
    public const array REQUIRED_ISSUANCE_FIELDS = [
        'schema', 'issuance_id', 'instance_id', 'source_decision',
        'consumed_issuance_authority', 'issued_activation_authority', 'issuer', 'issued_at',
        'authority_issued', 'provider_binding_activated', 'credential_capability_issued',
        'external_action_performed', 'sealed', 'record_digest',
    ];
    public const array NON_AUTHORITIES = [
        'activates_provider_binding' => false,
        'mutates_provider_binding' => false,
        'issues_credential_capability' => false,
        'takes_capability_custody' => false,
        'delivers_capability' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
