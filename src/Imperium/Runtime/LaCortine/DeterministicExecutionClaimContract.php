<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicExecutionClaimContract
{
    public const string SCHEMA = 'imperium.la-cortine.deterministic-execution-claim/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'claim_id',
        'instance_id',
        'source_authorization',
        'request',
        'holder',
        'replay_fingerprint',
        'execution_identity',
        'credential_capability',
        'provider_safety',
        'effect',
        'claimed_at',
        'expires_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_SOURCE_AUTHORIZATION_FIELDS = [
        'id',
        'digest',
        'schema',
        'issuer',
        'decision_owner',
    ];

    public const array REQUIRED_REQUEST_FIELDS = [
        'id',
        'commission_id',
        'authorization_id',
        'authorization_digest',
        'mode',
        'operation',
        'destination',
        'payload_digest',
        'expected_return_contract',
    ];

    public const array REQUIRED_HOLDER_FIELDS = [
        'actor_id',
        'office',
        'seat',
        'runtime_principal_id',
        'competent_service',
    ];

    public const array REQUIRED_EXECUTION_IDENTITY_FIELDS = [
        'execution_id',
        'single_use',
        'winner_scope',
        'lock_order',
    ];

    public const array REQUIRED_CREDENTIAL_CAPABILITY_FIELDS = [
        'capability_id',
        'credential_reference_digest',
        'commission_id',
        'operation',
        'expires_at',
        'max_uses',
    ];

    public const array REQUIRED_PROVIDER_SAFETY_FIELDS = [
        'strategy',
        'provider_idempotency_key',
        'provider_contract_reference',
        'automatic_replay_permitted',
        'unknown_outcome_status',
    ];

    public const array PROVIDER_SAFETY_STRATEGIES = [
        'PROVIDER_IDEMPOTENCY_KEY',
        'NON_REPLAYABLE_UNKNOWN_OUTCOME',
    ];

    public const array REQUIRED_EFFECT_FIELDS = [
        'checkpoint',
        'external_io_started',
        'outcome',
        'effect_started_at',
        'resolved_at',
    ];

    public const array EFFECT_CHECKPOINTS = [
        'CLAIMED_PRE_IO',
        'EFFECT_STARTED',
        'PROVIDER_RESOLVED',
        'RAW_RECEIPT_SEALED',
        'RECEIPT_BOUND',
    ];

    public const array EFFECT_OUTCOMES = [
        'NOT_ATTEMPTED',
        'ACCEPTED',
        'REJECTED',
        'UNKNOWN_REPLAY_PROHIBITED',
    ];

    public const array UNKNOWN_OUTCOME_RULES = [
        'automatic_replay_permitted' => false,
        'credential_reconsumption_permitted' => false,
        'provider_reinvocation_permitted' => false,
        'receipt_may_claim_acceptance' => false,
        'governed_resolution_required' => true,
        'sealed_response_forward_recovery_only' => true,
    ];

    public const array CONTRACT_BOUNDARY = [
        'issues_source_authorization' => false,
        'issues_credential_capability' => false,
        'selects_provider_safety_strategy' => false,
        'proves_provider_idempotency' => false,
        'consumes_authority' => false,
        'resolves_credentials' => false,
        'starts_external_io' => false,
        'performs_external_effect' => false,
        'opens_lazaretto' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
