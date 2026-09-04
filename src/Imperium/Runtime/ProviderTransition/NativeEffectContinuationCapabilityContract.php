<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative future contract. It neither issues nor recognizes a capability. */
final class NativeEffectContinuationCapabilityContract
{
    public const string SCHEMA = 'imperium.la-cortine.canonical-native-effect-continuation-capability/v1';
    public const int MAX_USES = 1;
    public const array REQUIRED_FIELDS = [
        'schema', 'capability_id', 'admission_id', 'admission_digest',
        'semantic_effect_tuple_id', 'authority_consumption_id',
        'process_boundary_id', 'runtime_process_id',
        'process_incarnation_binding', 'expires_at', 'max_uses',
        'cross_process_transfer_permitted', 'durable_persistence_permitted',
        'reconstruction_permitted',
    ];
    public const array REQUIRED_INVARIANTS = [
        'issuer_registry_object_identity_required' => true,
        'newly_published_winner_only' => true,
        'exact_replay_may_mint' => false,
        'cross_process_transfer_permitted' => false,
        'durable_persistence_permitted' => false,
        'reconstruction_permitted' => false,
        'single_use' => true,
        'actual_runtime_pid_required' => true,
        'issuer_owned_incarnation_nonce_required' => true,
        'authority_process_boundary_is_identity' => false,
        'serialization_permitted' => false,
        'unserialization_permitted' => false,
        'clone_permitted' => false,
        'fork_inheritance_permitted' => false,
    ];
}
