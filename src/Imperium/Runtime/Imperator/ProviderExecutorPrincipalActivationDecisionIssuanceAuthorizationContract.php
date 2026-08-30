<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract
{
    public const string SCHEMA = 'imperium.imperator.provider-executor-principal-activation-decision-issuance-authorization/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-imperator.provider-executor-principal-activation-decision-issuance-authorizer';
    public const array CONSUMER_POSTURES = [
        'future-imperator.provider-executor-principal-activation-decision-producer',
    ];
    public const string PERMITTED_TRANSITION =
        'PRODUCE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_AND_AUTHORITY';
    public const array REQUIRED_FIELDS = [
        'schema',
        'issuance_authorization_id',
        'instance_id',
        'issuer_principal',
        'scope_successor',
        'activation_disposition',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'decision_id',
        'activation_authority_id',
        'permitted_transition',
        'authority_single_use',
        'authority_exercisable',
        'issuance_winner_required',
        'consumption_winner_required',
        'issued_at',
        'expires_at',
        'revocation',
        'consumed',
        'continuing_authority',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_PRINCIPAL_FIELDS = [
        'id',
        'digest',
        'schema',
        'generation',
    ];
    public const array NON_AUTHORITIES = [
        'activates_successor_principal' => false,
        'widens_principal_scope' => false,
        'issues_itself' => false,
        'issues_caller_authority' => false,
        'consumes_caller_authority' => false,
        'produces_activation_decision' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'activates_provider_executor_principal' => false,
        'activates_provider_binding' => false,
        'creates_continuing_authority' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
