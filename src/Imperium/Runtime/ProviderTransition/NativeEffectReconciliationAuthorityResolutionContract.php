<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Declarative trusted-ingress checks required before typed custody delivery. */
final class NativeEffectReconciliationAuthorityResolutionContract
{
    public const array REQUIRED_RECORDS = [
        'canonical_authority', 'canonical_issuance', 'effect_admission',
        'native_transition_commit', 'resolved_native_authority',
        'resolved_native_principal', 'callback_start', 'sealed_response',
    ];
    public const array REQUIRED_CHECKS = [
        'authority_and_issuance_digests_match_storage',
        'issuance_references_exact_authority',
        'admission_references_exact_committed_native_root',
        'transition_commit_references_exact_native_authority',
        'native_authority_resolves_current_active_principal',
        'operator_root_act_is_verified',
        'callback_and_response_lineage_is_exact',
        'authority_is_effective_unexpired_and_unrevoked',
        'authority_has_no_conflicting_consumption',
    ];
    public const array PROHIBITED_PROVENANCE_SUBSTITUTES = [
        'constant_issuer_label', 'caller_computed_digest',
        'trusted_directory_without_trusted_ingress', 'fixture_only_source_record',
    ];
}
