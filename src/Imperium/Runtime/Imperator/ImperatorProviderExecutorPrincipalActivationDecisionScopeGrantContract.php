<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract
{
    public const string SCHEMA = 'imperium.operator-root.imperator-provider-executor-principal-activation-decision-scope-grant/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'operator-root.imperator-provider-executor-principal-activation-decision-scope-grant-issuer';
    public const array CONSUMER_POSTURES = [
        'future-mastermason.imperator-provider-executor-principal-activation-decision-scope-successor-committer',
    ];
    public const string PERMITTED_TRANSITION =
        'AUTHORIZE_EXACT_PROVIDER_EXECUTOR_PRINCIPAL_ACTIVATION_DECISION_SCOPE_SUCCESSOR';
    public const array REQUIRED_FIELDS = [
        'schema',
        'grant_id',
        'instance_id',
        'operator_root',
        'source_principal',
        'successor_principal',
        'scope_delta',
        'preserved_scope',
        'permitted_transition',
        'rationale',
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
    public const array REQUIRED_OPERATOR_ROOT_FIELDS = [
        'operator_id',
        'source_identity_digest',
        'decision_id',
        'decision_digest',
    ];
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
        'identifies_operator_root' => false,
        'issues_grant' => false,
        'consumes_grant' => false,
        'widens_source_principal' => false,
        'creates_successor_principal' => false,
        'activates_successor_principal' => false,
        'issues_caller_authority' => false,
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
