<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingActivationRevocationAuthorityIssuanceContract
{
    public const string DECISION_SCHEMA =
        'imperium.imperator.provider-binding-activation-revocation-authority-decision/v1';
    public const string ISSUANCE_SCHEMA =
        'imperium.imperator.provider-binding-activation-revocation-authority-issuance/v1';
    public const int VERSION = 1;
    public const string TARGET_KIND =
        'provider_binding_activation_revocation_authority';
    public const string PERMITTED_TRANSITION =
        'ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_REVOCATION_AUTHORITY';
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_DECISION_FIELDS = [
        'schema', 'decision_id', 'instance_id', 'source_authority', 'actor',
        'target', 'basis', 'disposition', 'rationale', 'limitations',
        'issuance_authority', 'decided_at', 'expires_at',
        'external_action_performed', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_ISSUANCE_AUTHORITY_FIELDS = [
        'authority_id', 'authority_single_use', 'authority_exercisable',
        'issuer_service', 'permitted_transition', 'target_digest',
        'expires_at', 'consumed', 'continuing_authority',
    ];
    public const array REQUIRED_ISSUANCE_FIELDS = [
        'schema', 'issuance_id', 'instance_id', 'source_decision',
        'consumed_issuance_authority', 'issued_artifact', 'issuer', 'issued_at',
        'revocation_authority_issued', 'principal_installed',
        'provider_binding_activated', 'credential_capability_issued',
        'credential_resolved', 'external_action_performed',
        'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_CONSUMED_AUTHORITY_FIELDS = [
        'id', 'digest', 'schema', 'consumed_at', 'consumed',
        'continuing_authority',
    ];
    public const array REQUIRED_ACTOR_FIELDS = [
        'principal_id', 'office', 'seat', 'binding_id', 'generation',
    ];
    public const array REQUIRED_TARGET_FIELDS = [
        'kind', 'id', 'digest', 'schema',
    ];
    public const array REQUIRED_BASIS_FIELDS = [
        'provider_binding_activation', 'execution_boundary',
        'executor_principal', 'provider_binding',
    ];
    public const array NON_AUTHORITIES = [
        'revokes_activation' => false,
        'issues_activation' => false,
        'issues_execution_authority' => false,
        'activates_provider_binding' => false,
        'consumes_execution_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
