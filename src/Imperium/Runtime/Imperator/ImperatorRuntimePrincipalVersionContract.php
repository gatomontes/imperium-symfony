<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ImperatorRuntimePrincipalVersionContract
{
    public const string SCHEMA = 'imperium.imperator-runtime-principal/v2';
    public const int VERSION = 2;
    public const string PRODUCER_POSTURE = 'mastermason.authorized-imperator-principal-version-committer';
    public const array CONSUMER_POSTURES = ['la-cortine.deterministic-transition-caller-authority-issuer', 'imperator.principal-lifecycle-disposition', 'imperator.principal-read-only-reconstruction'];
    public const array STATUSES = ['PENDING_ACTIVATION', 'ACTIVE', 'SUSPENDED', 'SUPERSEDED', 'REVOKED', 'EXPIRED', 'RETIRED'];
    public const array REQUIRED_FIELDS = ['schema', 'principal_version_id', 'principal_id', 'instance_id', 'binding_id', 'principal_generation', 'constitution_route', 'source_constitution_authority', 'source_operator_root', 'identity', 'authority_scope', 'lifecycle', 'status', 'credential_reference_persisted', 'credential_secret_persisted', 'serialized_capability_persisted', 'sealed', 'record_digest'];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_IDENTITY_FIELDS = ['operator_id', 'operator_identity_digest', 'imperator_subject_id', 'imperator_subject_digest'];
    public const array REQUIRED_AUTHORITY_SCOPE_FIELDS = ['provider_binding_activation_authority', 'outbound_email_authority', 'credential_authority', 'provider_execution_authority', 'corridor_disposition_authority'];
    public const array REQUIRED_LIFECYCLE_FIELDS = ['constituted_at', 'effective_at', 'expires_at', 'prior_version', 'superseding_version', 'current_disposition'];
    public const array SECRET_EXCLUSION = ['credential_reference_permitted' => false, 'credential_secret_permitted' => false, 'serialized_capability_permitted' => false, 'provider_authentication_permitted' => false];
    public const array NON_AUTHORITIES = ['self_constitutes' => false, 'self_renews' => false, 'self_widens_scope' => false, 'issues_own_caller_authority' => false, 'reopens_operator_root_window' => false, 'acts_as_credential' => false, 'activates_provider_binding' => false, 'starts_external_io' => false];

    private function __construct() {}
}
