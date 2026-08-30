<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-executor-principal-activation-decision-production-envelope/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-imperator.provider-executor-principal-activation-decision-production-envelope-committer';
    public const array CONSUMER_POSTURES = [
        'future-imperator.provider-executor-principal-activation-decision-producer',
        'future-imperator.provider-executor-principal-activation-decision-provenance-reconstructor',
    ];
    public const string PERMITTED_TRANSITION =
        ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::PERMITTED_TRANSITION;
    public const array DISPOSITIONS = ProviderExecutorPrincipalActivationDecisionContract::DISPOSITIONS;
    public const array REQUIRED_FIELDS = [
        'schema',
        'production_envelope_id',
        'instance_id',
        'issuance_authorization',
        'issuer_principal',
        'source_authority',
        'actor',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'scope',
        'disposition',
        'rationale',
        'limitations',
        'activation_authority',
        'validity',
        'decision_id',
        'permitted_transition',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS =
        ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_REFERENCE_FIELDS;
    public const array REQUIRED_PRINCIPAL_FIELDS =
        ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::REQUIRED_PRINCIPAL_FIELDS;
    public const array REQUIRED_ACTOR_FIELDS =
        ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTOR_FIELDS;
    public const array REQUIRED_SCOPE_FIELDS =
        ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS;
    public const array REQUIRED_ACTIVATION_AUTHORITY_FIELDS =
        ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS;
    public const array REQUIRED_VALIDITY_FIELDS =
        ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_VALIDITY_FIELDS;
    public const array AUTHORIZATION_BOUND_FIELDS = [
        'instance_id',
        'issuer_principal',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'decision_id',
        'activation_authority.authority_id',
        'activation_authority.authority_single_use',
        'activation_authority.authority_exercisable',
        'activation_authority.expires_at',
        'activation_authority.consumed',
        'activation_authority.continuing_authority',
        'validity.expires_at',
        'validity.revocation_reference',
        'permitted_transition',
    ];
    public const array NON_AUTHORITIES = [
        'creates_principal' => false,
        'widens_principal_scope' => false,
        'changes_lifecycle_disposition' => false,
        'issues_itself' => false,
        'consumes_issuance_authorization' => false,
        'produces_activation_decision' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'activates_principal' => false,
        'activates_provider_binding' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
    ];

    private function __construct()
    {
    }
}
