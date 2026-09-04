<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Present-tense revalidation law for the issuer and claim-use cuts. */
final class NativeEffectReconciliationAtUseCurrentnessContract
{
    public const string SCHEMA = 'imperium.imperator.native-effect-reconciliation-at-use-currentness/v1';
    public const int VERSION = 1;
    public const array GOVERNED_CUTS = ['ISSUER_CONSUME_AND_PUBLISH', 'CLAIM_USE_CONSUME_AND_PUBLISH'];
    public const array PRESENT_TENSE_CHECKS = [
        'operator_root_identity_and_current_untimestamped_revocation',
        'native_principal_generation_activation_and_revocation',
        'source_principal_generation',
        'source_principal_lifecycle',
        'exact_admission_callback_response_lineage',
        'authority_and_issuance_record_digests',
    ];
    public const array INDEPENDENTLY_MUTABLE_CHECKS = [
        'operator_root_revocation', 'native_principal_revocation',
        'source_generation_advance', 'source_lifecycle_change',
    ];
    public const array TRANSITIVELY_BOUNDED_EXPIRY_CASES = ['RR02', 'RR05', 'RR11'];
    public const array LIFECYCLE_EVENT_TO_REFUSAL = [
        'SUSPEND' => 'REFUSED_SOURCE_SUSPENDED',
        'SUPERSEDE' => 'REFUSED_SOURCE_SUPERSEDED',
        'REVOKE' => 'REFUSED_SOURCE_REVOKED',
        'EXPIRE' => 'REFUSED_SOURCE_EXPIRED',
        'RETIRE' => 'REFUSED_SOURCE_RETIRED',
        'V3_MIGRATION_REQUIRED' => 'REFUSED_SOURCE_MIGRATION_REQUIRED',
    ];
    public const array REQUIRED_INVARIANTS = [
        'resolution_snapshot_is_permanent_authority' => false,
        'currentness_is_serialized_into_capability' => false,
        'revalidation_occurs_inside_same_governed_cut' => true,
        'issuer_cut_revalidates_before_consumption' => true,
        'claim_use_cut_revalidates_before_consumption' => true,
        'later_forward_refusal_retroactively_authorizes_claim' => false,
    ];

    private function __construct() {}
}
