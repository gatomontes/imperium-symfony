<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Canonical cooperative single-host lock order for native-currentness use cuts. */
final class NativeStateSharedExclusionContract
{
    public const string OUTER_SCOPE = 'native-provider-transition';
    public const string TARGET_ORDER = 'native_shared_exclusion_before_semantic_target';
    public const array PROTECTED_CURRENTNESS = [
        'operator_root_identity_and_revocation',
        'native_principal_generation_activation_and_revocation',
        'source_principal_generation_and_lifecycle',
        'exact_admission_callback_response_lineage',
    ];
    public const array PERMITTED_ORDER = [
        'native_shared_exclusion',
        'semantic_target_lock',
        'immutable_publication_lock',
    ];
    public const array PROHIBITED_NESTING = [
        'semantic_target_to_native_shared_exclusion',
        'native_shared_exclusion_reentry',
        'semantic_target_reentry',
        'external_io_under_governed_lock',
    ];
    public const array INTERRUPTION_CUTS = [
        'before_currentness_no_output',
        'after_currentness_before_consumption_no_output',
        'after_consumption_exact_retry_only',
        'after_target_publication_exact_retry_only',
        'after_evidence_publication_established_result',
    ];
    public const array REQUIRED_INVARIANTS = [
        'shared_exclusion_is_not_target_serialization' => true,
        'currentness_and_use_share_one_exclusion' => true,
        'shared_lock_precedes_target_lock' => true,
        'reverse_acquisition_prohibited' => true,
        'locks_are_non_reentrant' => true,
        'exact_retry_may_finish_only_same_publication' => true,
        'changed_input_conflicts' => true,
        'distributed_or_hostile_writer_exclusion_proved' => false,
    ];

    private function __construct() {}
}
