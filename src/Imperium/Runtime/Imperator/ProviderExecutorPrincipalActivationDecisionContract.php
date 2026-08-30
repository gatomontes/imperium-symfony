<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderExecutorPrincipalActivationDecisionContract
{
    public const string SCHEMA = 'imperium.imperator.provider-executor-principal-activation-decision/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-imperator-provider-executor-principal-activation-decision';
    public const array CONSUMER_POSTURES = [
        'future-la-cortine-provider-executor-principal-activation-transition',
    ];
    public const string PERMITTED_TRANSITION = 'ACTIVATE_EXACT_ATTESTED_PROVIDER_EXECUTOR_PRINCIPAL_GENERATION';
    public const array DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array REQUIRED_FIELDS = [
        'schema',
        'decision_id',
        'instance_id',
        'source_authority',
        'actor',
        'principal_attestation',
        'provider_assurance_admission',
        'scope',
        'disposition',
        'rationale',
        'limitations',
        'activation_authority',
        'validity',
        'decided_at',
        'external_action_performed',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ACTOR_FIELDS = [
        'principal_id',
        'office',
        'seat',
        'binding_id',
        'generation',
    ];
    public const array REQUIRED_SCOPE_FIELDS = [
        'provider_id',
        'operation',
        'execution_boundary_id',
        'principal_id',
        'principal_generation',
        'process_boundary_id',
        'same_process_execution_required',
    ];
    public const array REQUIRED_ACTIVATION_AUTHORITY_FIELDS = [
        'authority_id',
        'authority_single_use',
        'authority_exercisable',
        'issuer_service',
        'permitted_transition',
        'target_attestation_digest',
        'expires_at',
        'consumed',
        'continuing_authority',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'expires_at',
        'revocation_reference',
    ];
    public const array NON_AUTHORITIES = [
        'produces_activation_decision' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'activates_principal' => false,
        'mutates_principal_attestation' => false,
        'activates_provider_binding' => false,
        'defines_live_call_runtime' => false,
        'issues_execution_authority' => false,
        'consumes_execution_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
