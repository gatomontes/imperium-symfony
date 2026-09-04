<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative boundary between durable evidence and process-local custody. */
final class NativeEffectReconciliationAuthorityCustodyContract
{
    public const array DELIVERY_INPUTS = ['authority_id', 'current_time'];
    public const array REQUIRED_INVARIANTS = [
        'caller_supplies_record' => false,
        'caller_supplies_digest' => false,
        'durable_record_is_capability' => false,
        'resolver_loads_canonical_issuance' => true,
        'resolver_revalidates_source_competence' => true,
        'typed_capability_is_process_local' => true,
        'typed_capability_is_non_serializable' => true,
        'typed_capability_is_non_cloneable' => true,
        'fresh_process_may_resolve_only_unconsumed_authority' => true,
    ];
}
