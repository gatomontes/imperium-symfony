<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceAuthenticatedClosureContract
{
    public const string SCHEMA =
        'imperium.atomic-transition-evidence-authenticated-operational-closure/v1';
    public const string STATUS =
        'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_AUTHENTICATED_OPERATIONAL_EVIDENCE_PROOF';
    public const array REQUIRED_FIELDS = [
        'schema', 'closure_id', 'sanitized_evidence_reference',
        'independent_reconstruction_reference', 'terminal_evidence_chain_digest',
        'authenticated_operational_evidence_survived',
        'independent_reconstruction_survived',
        'historical_boolean_audit_disabled',
        'historical_self_recomputed_closure_disabled',
        'producer_disposition_imported', 'material_evidence_defect_corrected',
        'qualification_removed', 'campaign_closed', 'provider_binding_status',
        'required_v3_execution_admission', 'unknown_replay_posture', 'read_only',
        'runtime_state_written', 'authority_issued_or_consumed',
        'execution_admitted', 'provider_binding_changed',
        'credential_or_capability_handled', 'provider_invoked',
        'external_io_started', 'provider_effect_started', 'retry_authorized',
        'live_command_adopted', 'continuing_authority', 'status', 'sealed',
        'record_digest',
    ];

    private function __construct()
    {
    }
}
