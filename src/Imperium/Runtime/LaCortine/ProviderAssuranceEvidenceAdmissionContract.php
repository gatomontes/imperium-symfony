<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderAssuranceEvidenceAdmissionContract
{
    public const string SCHEMA = 'imperium.la-cortine.provider-assurance-evidence-admission/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'future-la-cortine-provider-assurance-evidence-admission';
    public const array CONSUMER_POSTURES = [
        'la-cortine.future-provider-principal-activation-assessment',
        'la-cortine.future-operation-binding-activation-assessment',
        'la-cortine.future-provider-live-call-contract-validation',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'admission_id',
        'instance_id',
        'provider_id',
        'operation',
        'assurance_profile',
        'evidence_sources',
        'admitted_claims',
        'explicit_unknowns',
        'threat_model',
        'validity',
        'status',
        'admitted_at',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ADMITTED_CLAIM_FIELDS = [
        'organization_collision_scope',
        'idempotency_key_syntax',
        'request_equivalence',
        'completed_exact_duplicate',
        'changed_request_conflict',
        'completion_anchored_retention',
    ];
    public const array REQUIRED_UNKNOWN_FIELDS = [
        'in_progress_duplicate_semantics',
        'query_before_retry',
        'completion_time_without_response',
        'remote_cryptographic_authorship',
    ];
    public const array REQUIRED_THREAT_MODEL_FIELDS = [
        'integrity_posture',
        'deployment_posture',
        'authenticated_channel_trust_only',
        'hostile_writer_non_forgeability_claimed',
        'distributed_execution_claimed',
    ];
    public const array REQUIRED_VALIDITY_FIELDS = [
        'effective_at',
        'review_due_at',
        'supersession_reference',
        'revocation_reference',
    ];
    public const array STATUSES = [
        'EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY',
        'SUPERSEDED',
        'REVOKED',
        'REFUSED',
    ];
    public const string UNKNOWN_OUTCOME_POSTURE = 'UNKNOWN_REPLAY_PROHIBITED';
    public const array NON_AUTHORITIES = [
        'activates_executor_principal' => false,
        'activates_provider_binding' => false,
        'defines_live_call_runtime' => false,
        'issues_execution_authority' => false,
        'consumes_execution_authority' => false,
        'authorizes_retry' => false,
        'issues_credential_capability' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'migrates_live_consumer' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
