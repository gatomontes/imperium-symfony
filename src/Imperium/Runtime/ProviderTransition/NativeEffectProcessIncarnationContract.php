<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative process-incarnation requirements. It creates no runtime identity. */
final class NativeEffectProcessIncarnationContract
{
    public const string SCHEMA = 'imperium.la-cortine.native-effect-process-incarnation/v1';
    public const array REQUIRED_COMPONENTS = [
        'runtime_process_id',
        'issuer_owned_random_nonce',
        'issuer_object_identity',
    ];
    public const array OPTIONAL_CORROBORATION = [
        'linux_boot_id',
        'linux_proc_start_ticks',
        'windows_process_creation_time',
    ];
    public const array PROHIBITED_IDENTITY_SUBSTITUTES = [
        'authority_execution_boundary_id',
        'container_service_id',
        'hostname',
        'caller_supplied_process_label',
        'pid_without_nonce',
    ];
    public const array REQUIRED_INVARIANTS = [
        'current_pid_must_equal_initial_pid' => true,
        'pid_reuse_requires_fresh_nonce' => true,
        'missing_pid_fails_closed' => true,
        'serialization_permitted' => false,
        'unserialization_permitted' => false,
        'clone_permitted' => false,
        'fork_inheritance_permitted' => false,
        'nonce_persistence_permitted' => false,
        'nonce_metadata_exposure_permitted' => false,
    ];
}
