<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorRuntimePrincipalVersionV3Contract
{
    public const string SCHEMA = 'imperium.imperator-runtime-principal/v3';
    public const int VERSION = 3;
    public const string PREDECESSOR_SCHEMA = ImperatorRuntimePrincipalVersionContract::SCHEMA;
    public const string PRODUCER_POSTURE = 'future-mastermason.authorized-imperator-principal-version-v3-committer';
    public const array CONSUMER_POSTURES = [
        'operator-root.imperator-principal-lifecycle-authority',
        'future-imperator.provider-executor-principal-activation-decision-issuance-authorizer',
        'imperator.principal-read-only-reconstruction',
    ];
    public const array STATUSES = ImperatorRuntimePrincipalVersionContract::STATUSES;
    public const array REQUIRED_FIELDS = ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS;
    public const array REQUIRED_REFERENCE_FIELDS = ImperatorRuntimePrincipalVersionContract::REQUIRED_REFERENCE_FIELDS;
    public const array REQUIRED_IDENTITY_FIELDS = ImperatorRuntimePrincipalVersionContract::REQUIRED_IDENTITY_FIELDS;
    public const array REQUIRED_AUTHORITY_SCOPE_FIELDS = [
        'provider_binding_activation_authority',
        'outbound_email_authority',
        'credential_authority',
        'provider_execution_authority',
        'corridor_disposition_authority',
        'provider_executor_principal_activation_decision_authority',
    ];
    public const array ADDED_AUTHORITY_SCOPE_FIELDS = [
        'provider_executor_principal_activation_decision_authority',
    ];
    public const array REQUIRED_LIFECYCLE_FIELDS = ImperatorRuntimePrincipalVersionContract::REQUIRED_LIFECYCLE_FIELDS;
    public const array SECRET_EXCLUSION = ImperatorRuntimePrincipalVersionContract::SECRET_EXCLUSION;
    public const array NON_AUTHORITIES = [
        'self_constitutes' => false,
        'self_renews' => false,
        'self_widens_scope' => false,
        'issues_own_caller_authority' => false,
        'issues_own_decision_authority' => false,
        'produces_activation_decision' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'activates_principal' => false,
        'reopens_operator_root_window' => false,
        'acts_as_credential' => false,
        'handles_credential' => false,
        'activates_provider_binding' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
