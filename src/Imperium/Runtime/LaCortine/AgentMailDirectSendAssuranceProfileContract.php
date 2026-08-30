<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class AgentMailDirectSendAssuranceProfileContract
{
    public const string SCHEMA = 'imperium.la-cortine.agentmail-direct-send-assurance-profile/v1';
    public const int VERSION = 1;
    public const string PROVIDER_ID = 'agentmail';
    public const string OPERATION = 'email.send';
    public const string ENDPOINT_TEMPLATE = 'POST /v0/inboxes/{inbox_id}/messages/send';
    public const string PRODUCER_POSTURE = 'la-cortine.agentmail-direct-send-assurance-profile-definition';
    public const array CONSUMER_POSTURES = [
        'la-cortine.provider-assurance-evidence-admission-assessment',
        'la-cortine.future-provider-live-call-contract-validation',
    ];
    public const array REQUIRED_FIELDS = [
        'schema',
        'profile_id',
        'provider_id',
        'operation',
        'endpoint',
        'evidence_sources',
        'collision_scope',
        'idempotency_key',
        'request_equivalence',
        'completed_duplicate',
        'changed_request',
        'retention',
        'explicit_unknowns',
        'replay_posture',
        'status',
        'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_COLLISION_SCOPE_FIELDS = [
        'organization_scoped',
        'endpoint_bound',
        'inbox_bound',
        'content_bound',
    ];
    public const array REQUIRED_IDEMPOTENCY_KEY_FIELDS = [
        'header_name',
        'minimum_length',
        'maximum_length',
        'allowed_character_class',
        'empty_permitted',
    ];
    public const array REQUIRED_REQUEST_EQUIVALENCE_FIELDS = [
        'organization',
        'endpoint',
        'inbox_id',
        'message_content',
    ];
    public const array REQUIRED_COMPLETED_DUPLICATE_FIELDS = [
        'second_send_expected',
        'original_message_id_expected',
        'original_thread_id_expected',
    ];
    public const array REQUIRED_CHANGED_REQUEST_FIELDS = [
        'same_key_changed_request_expected_status',
        'local_collision_refusal_required',
    ];
    public const array REQUIRED_RETENTION_FIELDS = [
        'declared_duration_hours',
        'anchor',
        'local_effect_start_may_establish_anchor',
    ];
    public const array REQUIRED_UNKNOWN_FIELDS = [
        'in_progress_duplicate_semantics',
        'query_by_idempotency_key',
        'remote_cryptographic_authorship',
        'completion_time_without_response',
    ];
    public const array STATUSES = ['DEFINED_EVIDENCE_ONLY', 'SUPERSEDED', 'REFUSED'];
    public const string REPLAY_POSTURE = 'UNKNOWN_REPLAY_PROHIBITED';
    public const array NON_AUTHORITIES = [
        'admits_provider_contract' => false,
        'claims_observed_conformance' => false,
        'activates_executor_principal' => false,
        'activates_provider_binding' => false,
        'defines_live_call_runtime' => false,
        'issues_execution_authority' => false,
        'authorizes_retry' => false,
        'handles_credentials' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
    ];

    private function __construct()
    {
    }
}
