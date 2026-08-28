<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

final class AuthorityConsumptionRecoveryContract
{
    public const string SCHEMA = 'imperium.runtime-authority-consumption-recovery/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'recovery_id',
        'source_transaction',
        'replay_fingerprint',
        'checkpoint',
        'outcome',
        'retry',
        'rollback',
        'external_effect',
        'resolved_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_TRANSACTION_REFERENCE_FIELDS = [
        'id',
        'digest',
    ];

    public const array CHECKPOINTS = [
        'NOT_STARTED',
        'PREPARED',
        'CONSUMPTION_COMMITTED',
        'RESULT_COMMITTED',
        'COMPLETE',
        'UNKNOWN',
    ];

    public const array OUTCOMES = [
        'NOT_ATTEMPTED',
        'FORWARD_RECOVERY_REQUIRED',
        'COMPLETE',
        'FAILED_STOPPED',
        'UNKNOWN',
    ];

    public const array REQUIRED_RETRY_FIELDS = [
        'automatic_retry_permitted',
        'same_replay_fingerprint_required',
        'provider_reinvocation_permitted',
    ];

    public const array REQUIRED_ROLLBACK_FIELDS = [
        'automatic_rollback_permitted',
        'authority_unconsume_permitted',
    ];

    public const array REQUIRED_EXTERNAL_EFFECT_FIELDS = [
        'started',
        'outcome_known',
        'response_identity',
    ];

    public const array UNKNOWN_OUTCOME_RULES = [
        'automatic_retry_permitted' => false,
        'provider_reinvocation_permitted' => false,
        'authority_unconsume_permitted' => false,
        'governed_resolution_required' => true,
        'sealed_response_forward_recovery_only' => true,
    ];

    public const array RECOVERY_BOUNDARY = [
        'issues_recovery_authority' => false,
        'infers_recovery_authority' => false,
        'unconsumes_authority' => false,
        'rewrites_immutable_result' => false,
        'automatically_rolls_back' => false,
        'automatically_reinvokes_provider' => false,
        'external_action_authority' => false,
        'execution_authority' => false,
        'continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
