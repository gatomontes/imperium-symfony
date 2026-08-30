<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract
{
    public const string SCHEMA = 'imperium.mastermason.imperator-provider-executor-principal-activation-decision-scope-successor/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-mastermason.imperator-provider-executor-principal-activation-decision-scope-successor-committer';
    public const array CONSUMER_POSTURES = [
        'operator-root.imperator-principal-lifecycle-authority',
        'future-imperator.provider-executor-principal-activation-decision-issuance-authorizer',
        'future-imperator.provider-executor-principal-activation-decision-provenance-reconstructor',
    ];
    public const string PERMITTED_TRANSITION =
        'COMMIT_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_SCOPE_SUCCESSOR';
    public const string INITIAL_STATUS = 'PENDING_ACTIVATION';
    public const array REQUIRED_FIELDS = [
        'schema',
        'successor_transition_id',
        'instance_id',
        'scope_grant',
        'source_principal',
        'successor_principal',
        'source_generation',
        'successor_generation',
        'identity_preserved',
        'binding_preserved',
        'scope_delta',
        'preserved_scope',
        'initial_status',
        'activation_required',
        'separate_activation_authority_required',
        'transition_winner_required',
        'committed_at',
        'grant_consumed',
        'source_principal_mutated',
        'source_principal_superseded',
        'decision_issuance_authorization_created',
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
    public const array REQUIRED_SCOPE_DELTA_FIELDS = [
        'provider_executor_principal_activation_decision_authority',
    ];
    public const array REQUIRED_PRESERVED_SCOPE_FIELDS = [
        'provider_binding_activation_authority',
        'outbound_email_authority',
        'credential_authority',
        'provider_execution_authority',
        'corridor_disposition_authority',
    ];
    public const array NON_AUTHORITIES = [
        'chooses_scope' => false,
        'issues_scope_grant' => false,
        'activates_successor' => false,
        'rewrites_source_principal' => false,
        'reinterprets_lifecycle_supersession' => false,
        'issues_caller_authority' => false,
        'authorizes_decision_issuance' => false,
        'produces_activation_decision' => false,
        'issues_activation_authority' => false,
        'activates_provider_executor_principal' => false,
        'activates_provider_binding' => false,
        'handles_credential' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
