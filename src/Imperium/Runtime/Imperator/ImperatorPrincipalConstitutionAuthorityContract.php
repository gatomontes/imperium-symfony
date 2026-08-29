<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorPrincipalConstitutionAuthorityContract
{
    public const string SCHEMA = 'imperium.operator-root.imperator-principal-constitution-authority/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'operator-root.imperator-principal-constitution-authority-issuer';
    public const array CONSUMER_POSTURES = ['mastermason.future-instance-imperator-principal-constitution', 'mastermason.existing-instance-imperator-principal-remediation'];
    public const array ROUTES = ['FUTURE_INSTANCE_ROOT_ESTABLISHMENT', 'EXISTING_INSTANCE_REMEDIATION'];
    public const array TRANSITIONS = ['CONSTITUTE_INITIAL_IMPERATOR_PRINCIPAL', 'REMEDIATE_MISSING_IMPERATOR_PRINCIPAL'];
    public const array REQUIRED_FIELDS = ['schema', 'authority_id', 'instance_id', 'route', 'operator_root', 'operationalization', 'imperator_identity', 'permitted_transition', 'target_principal', 'scope', 'authority_single_use', 'authority_exercisable', 'issued_at', 'expires_at', 'consumed', 'continuing_authority', 'sealed', 'record_digest'];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_OPERATOR_ROOT_FIELDS = ['operator_id', 'source_identity_digest', 'decision_id', 'decision_digest'];
    public const array REQUIRED_TARGET_FIELDS = ['principal_id', 'binding_id', 'generation'];
    public const array REQUIRED_SCOPE_FIELDS = ['provider_binding_activation_authority', 'outbound_email_authority', 'credential_authority', 'provider_execution_authority', 'corridor_disposition_authority'];
    public const array NON_AUTHORITIES = ['selects_imperator_identity' => false, 'acts_as_runtime_principal' => false, 'installs_personnel' => false, 'reopens_operator_root_window' => false, 'issues_caller_authority' => false, 'decides_provider_binding' => false, 'activates_provider_binding' => false, 'handles_credentials' => false, 'starts_external_io' => false];

    private function __construct() {}
}
