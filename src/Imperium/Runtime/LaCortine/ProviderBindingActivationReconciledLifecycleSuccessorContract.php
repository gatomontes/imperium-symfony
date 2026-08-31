<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingActivationReconciledLifecycleSuccessorContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-activation-reconciled-lifecycle-successor/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'future-authorized-atomic-operation-scoped-successor-transition';
    public const array CONSUMER_POSTURES = [
        'la-cortine.provider-binding-successor-reconstruction',
        'la-cortine.future-pre-effect-admission',
        'imperium.audit.provider-binding-lifecycle',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'successor_id',
        'instance_id',
        'source_decision',
        'successor_target',
        'active_principal_activation',
        'provider_binding_descriptor',
        'provider_assurance_admission',
        'execution_boundary',
        'operation_scope',
        'replay_contention_root',
        'consumed_activation_authority',
        'status',
        'validity',
        'reconstruction',
        'operation_scoped_binding_sufficiency_established',
        'original_binding_mutated',
        'global_bound_active_asserted',
        'credential_or_capability_handled',
        'provider_invoked',
        'external_io_started',
        'provider_effect_started',
        'retry_authority_created',
        'continuing_authority',
        'activated_at',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_CONSUMED_AUTHORITY_FIELDS = [
        'id',
        'digest',
        'schema',
        'consumed_at',
        'consumed',
        'continuing_authority',
    ];
    public const array REQUIRED_OPERATION_SCOPE_FIELDS =
        ProviderBindingActivationReconciledTargetContract::REQUIRED_OPERATION_SCOPE_FIELDS;
    public const array REQUIRED_ROOT_FIELDS =
        ProviderBindingActivationReconciledTargetContract::REQUIRED_ROOT_FIELDS;
    public const array REQUIRED_VALIDITY_FIELDS =
        ProviderBindingActivationReconciledTargetContract::REQUIRED_VALIDITY_FIELDS;
    public const array REQUIRED_RECONSTRUCTION_FIELDS = [
        'read_only',
        'exact_replay_only',
        'legacy_activation_promotable',
        'capability_reconstruction_permitted',
    ];
    public const array STATUSES = [
        'OPERATION_SCOPED_BINDING_ACTIVE',
        'EXPIRED',
        'REVOKED',
    ];
    public const array REQUIRED_INVARIANTS = [
        'operation_scoped_binding_sufficiency_established' => true,
        'original_binding_mutated' => false,
        'global_bound_active_asserted' => false,
        'credential_or_capability_handled' => false,
        'provider_invoked' => false,
        'external_io_started' => false,
        'provider_effect_started' => false,
        'retry_authority_created' => false,
        'continuing_authority' => false,
    ];
    public const array RECONSTRUCTION_INVARIANTS = [
        'read_only' => true,
        'exact_replay_only' => true,
        'legacy_activation_promotable' => false,
        'capability_reconstruction_permitted' => false,
    ];
    public const array NON_AUTHORITIES = [
        'mutates_original_binding' => false,
        'asserts_global_bound_active' => false,
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
