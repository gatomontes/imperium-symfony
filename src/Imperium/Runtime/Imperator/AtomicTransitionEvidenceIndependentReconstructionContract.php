<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceIndependentReconstructionContract
{
    public const string SCHEMA =
        'imperium.atomic-transition-evidence-independent-reconstruction/v1';
    public const string STATUS =
        'AUTHENTICATED_OPERATIONAL_EVIDENCE_INDEPENDENTLY_RECONSTRUCTED_PENDING_TERMINAL_AUDIT';
    public const array REQUIRED_FIELDS = [
        'schema', 'reconstruction_id', 'sanitized_evidence_reference',
        'source_and_build_binding_reconstructed',
        'trusted_execution_binding_reconstructed',
        'acceptance_matrix_reconstructed',
        'complete_chain_exclusion_reconstructed',
        'non_authority_perimeter_reconstructed',
        'producer_disposition_imported', 'historical_boolean_audit_accepted',
        'historical_self_recomputed_closure_accepted', 'read_only',
        'runtime_state_written', 'authority_issued_or_consumed',
        'execution_admitted', 'provider_binding_changed',
        'credential_or_capability_handled', 'provider_invoked',
        'external_io_started', 'provider_effect_started',
        'continuing_authority', 'qualification_removed', 'campaign_closed',
        'status', 'sealed', 'record_digest',
    ];

    private function __construct()
    {
    }
}
