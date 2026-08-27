<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\DecisionIntegrity;

final class InstitutionalDecisionSurfaceContract
{
    public const string SCHEMA = 'imperium.institutional-decision-surface/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'surface_id',
        'instance_id',
        'proceeding_id',
        'source_option_universe',
        'source_presentation_directive',
        'decision_owner',
        'decision_question',
        'options_presented',
        'unavailable_options',
        'prohibited_options',
        'rejected_options',
        'unexamined_options',
        'material_consequences',
        'risks',
        'reversibility',
        'recommendation',
        'evidence',
        'requested_authority',
        'authority_not_requested',
        'limitations',
        'expires_at',
        'material_facts_fingerprint',
        'allowed_dispositions',
        'authorization_state',
        'presented_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_DECISION_OWNER_FIELDS = [
        'actor_id',
        'office_or_seat',
        'authority_basis',
        'accountability_boundary',
    ];

    public const array REQUIRED_OPTION_FIELDS = [
        'option_id',
        'plain_language_explanation',
        'material_consequences',
        'risks',
        'costs',
        'external_effects',
        'reversibility',
        'authority_effect',
    ];

    public const array REQUIRED_EVIDENCE_FIELDS = [
        'artifact_id',
        'record_digest',
        'provenance',
        'version',
        'relevance',
        'sealed',
        'observed_at',
        'expires_at',
    ];

    public const array REQUIRED_CLASSIFIED_OPTION_FIELDS = [
        'option_id',
        'plain_language_explanation',
        'classification_reason',
        'material_consequences',
        'risks',
        'reversibility',
        'authority_effect',
        'evidence',
    ];

    public const array REQUIRED_AUTHORIZATION_STATE_FIELDS = [
        'decision_pending',
        'authority_granted',
        'decision_inferred',
        'non_authorizing_signals',
    ];

    public const array ALLOWED_DISPOSITIONS = [
        'AUTHORIZED',
        'REFUSED',
        'RETURNED_FOR_REVISION',
        'ALTERNATIVE_PROPOSED',
        'SELECTED',
        'REJECTED',
        'OPPOSED',
        'MODIFICATION_REQUESTED',
        'CLARIFICATION_REQUIRED',
        'DEFERRED',
    ];

    public const array NON_AUTHORIZING_SIGNALS = [
        'SILENCE',
        'INACTIVITY',
        'FAMILIARITY',
        'PRIOR_CONSENT',
    ];

    public const array CONSTITUTIONAL_BOUNDARY = [
        'presentation_is_recommendation' => false,
        'recommendation_is_selection' => false,
        'selection_is_approval' => false,
        'approval_is_authority' => false,
        'consent_is_unbounded_authority' => false,
        'capability_possession_is_permission' => false,
        'prior_authorization_survives_material_change' => false,
        'decision_authority' => false,
        'lifecycle_transition_authority' => false,
        'execution_authority' => false,
        'continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
