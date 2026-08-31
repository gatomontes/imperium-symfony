<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingActivationReconciledTargetContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-activation-reconciled-target/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'caller-supplied-authority-empty-reconciliation-target';
    public const array CONSUMER_POSTURES = [
        'imperator.provider-binding-successor-decision-input',
        'la-cortine.provider-binding-successor-validation',
        'la-cortine.provider-binding-successor-reconstruction',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'target_id',
        'instance_id',
        'active_principal_activation',
        'provider_binding_descriptor',
        'provider_assurance_admission',
        'execution_boundary',
        'operation_scope',
        'replay_contention_root',
        'validity',
        'original_binding_status',
        'original_binding_mutation_permitted',
        'global_bound_active_permitted',
        'exact_operation_scoped',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_OPERATION_SCOPE_FIELDS = [
        'provider_id',
        'operation',
        'principal_id',
        'principal_generation',
        'process_boundary_id',
        'provider_substitution_permitted',
        'operation_substitution_permitted',
        'principal_generation_substitution_permitted',
        'binding_substitution_permitted',
    ];
    public const array REQUIRED_ROOT_FIELDS = [
        'root_id',
        'instance_id',
        'principal_activation_id',
        'binding_id',
        'provider_id',
        'operation',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'expires_at',
        'revocation_reference',
    ];
    public const array REQUIRED_INVARIANTS = [
        'active_principal_status' => 'ACTIVE',
        'original_binding_status' => 'BOUND_INACTIVE',
        'original_binding_mutation_permitted' => false,
        'global_bound_active_permitted' => false,
        'exact_operation_scoped' => true,
    ];
    public const array NON_AUTHORITIES = [
        'activates_original_binding' => false,
        'issues_activation_authority' => false,
        'consumes_activation_authority' => false,
        'issues_execution_authority' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
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
