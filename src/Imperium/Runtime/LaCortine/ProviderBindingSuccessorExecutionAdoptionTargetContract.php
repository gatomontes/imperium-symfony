<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorExecutionAdoptionTargetContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-execution-adoption-target/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE =
        'caller-supplied-authority-empty-explicit-adoption-target';
    public const array CONSUMER_POSTURES = [
        'la-cortine.future-successor-adoption-decision',
        'la-cortine.future-pre-effect-admission-v3',
        'imperium.audit.provider-binding-successor-adoption',
    ];
    public const string TARGET_KIND =
        'explicit_execution_adoption_of_completed_binding_successor';
    public const array REQUIRED_FIELDS = [
        'schema',
        'adoption_target_id',
        'instance_id',
        'completed_successor',
        'active_principal_activation',
        'provider_binding_descriptor',
        'provider_assurance_admission',
        'execution_boundary',
        'operation_scope',
        'replay_contention_root',
        'required_admission_contract',
        'legacy_activation_substitution_permitted',
        'successor_synthesis_permitted',
        'original_binding_mutation_permitted',
        'global_bound_active_permitted',
        'credential_resolution_permitted',
        'provider_invocation_permitted',
        'external_io_permitted',
        'effect_start_permitted',
        'live_adoption_performed',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_OPERATION_SCOPE_FIELDS =
        ProviderBindingActivationReconciledTargetContract::REQUIRED_OPERATION_SCOPE_FIELDS;
    public const array REQUIRED_ROOT_FIELDS =
        ProviderBindingActivationReconciledTargetContract::REQUIRED_ROOT_FIELDS;
    public const array REQUIRED_INVARIANTS = [
        'legacy_activation_substitution_permitted' => false,
        'successor_synthesis_permitted' => false,
        'original_binding_mutation_permitted' => false,
        'global_bound_active_permitted' => false,
        'credential_resolution_permitted' => false,
        'provider_invocation_permitted' => false,
        'external_io_permitted' => false,
        'effect_start_permitted' => false,
        'live_adoption_performed' => false,
    ];
    public const array NON_AUTHORITIES = [
        'has_producer' => false,
        'validates_target' => false,
        'persists_target' => false,
        'decides_adoption' => false,
        'adopts_successor' => false,
        'changes_execution_admission' => false,
        'activates_original_binding' => false,
        'issues_or_consumes_authority' => false,
        'handles_credential_capability' => false,
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
