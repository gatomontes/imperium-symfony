<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class DefensibleDecisionRecordContract
{
    public const string SCHEMA = 'imperium.decision-record/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'decision_record_id',
        'instance_id',
        'proceeding_id',
        'source_decision_surface',
        'source_requests',
        'prior_decisions',
        'underlying_proceeding_evidence',
        'decision',
        'decision_owner',
        'options_considered',
        'risks',
        'evidence_relied_on',
        'rationale',
        'decided_at',
        'limitations',
        'expires_at',
        'authority_lineage',
        'supersession',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_DECISION_FIELDS = [
        'disposition',
        'decided_scope',
        'granted_authority',
        'denied_authority',
        'resulting_state',
        'everything_remaining_unauthorized',
    ];

    public const array REQUIRED_DECISION_OWNER_FIELDS = [
        'actor_id',
        'office_or_seat',
        'authority_basis',
        'accountability_boundary',
    ];

    public const array REQUIRED_OPTION_FIELDS = [
        'option_id',
        'examined_disposition',
        'reason',
    ];

    public const array REQUIRED_RISK_FIELDS = [
        'identified_risk',
        'proposed_treatment',
        'applied_treatment',
        'residual_risk',
        'residual_risk_owner',
        'acceptance_disposition',
    ];

    public const array REQUIRED_EVIDENCE_FIELDS = [
        'artifact_id',
        'record_digest',
        'provenance',
        'version',
        'relevance',
    ];

    public const array AUTHORITY_BOUNDARY = [
        'summarizes_underlying_proceeding' => true,
        'replaces_underlying_proceeding' => false,
        'rewrites_historical_record' => false,
        'infers_rationale' => false,
        'selects_option' => false,
        'widens_granted_authority' => false,
        'lifecycle_transition_authority' => false,
        'execution_authority' => false,
        'continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
