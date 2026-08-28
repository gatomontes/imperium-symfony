<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

final class TransactionalAuthorityConsumptionContract
{
    public const string SCHEMA = 'imperium.runtime-transactional-authority-consumption/v1';
    public const int VERSION = 1;

    public const array REQUIRED_FIELDS = [
        'schema',
        'transaction_id',
        'instance_id',
        'authority_set',
        'authoritative_inputs',
        'replay_fingerprint',
        'consumer',
        'lock_plan',
        'consumption_result',
        'recovery',
        'created_at',
        'sealed',
        'record_digest',
    ];

    public const array REQUIRED_AUTHORITY_FIELDS = [
        'authority_id',
        'authority_schema',
        'source',
        'issuer',
        'holder',
        'scope',
        'expires_at',
        'single_use',
        'expected_unconsumed',
    ];

    public const array REQUIRED_SOURCE_FIELDS = [
        'id',
        'digest',
    ];

    public const array REQUIRED_CONSUMER_FIELDS = [
        'actor',
        'competent_service',
        'bounded_act',
    ];

    public const array REQUIRED_LOCK_FIELDS = [
        'order',
        'scope',
        'authority_id',
    ];

    public const array REQUIRED_RESULT_FIELDS = [
        'state',
        'authority_consumptions',
        'immutable_result',
        'continuing_authority',
    ];

    public const array RESULT_STATES = [
        'PENDING',
        'COMMITTED',
        'CONFLICT',
        'FAILED_STOPPED',
        'UNKNOWN',
    ];

    public const array REPLAY_REQUIREMENTS = [
        'complete_authoritative_inputs' => true,
        'authority_order_preserved' => true,
        'source_id_and_digest_exact' => true,
        'consumer_identity_exact' => true,
        'bounded_act_exact' => true,
        'lock_plan_exact' => true,
        'exact_replay_returns_same_result' => true,
        'conflicting_replay_fails_stopped' => true,
    ];

    public const array CONSTITUTIONAL_BOUNDARY = [
        'replaces_lifecycle_authority_schema' => false,
        'changes_issuer' => false,
        'changes_holder' => false,
        'changes_competent_consumer' => false,
        'widens_scope' => false,
        'changes_expiry' => false,
        'changes_lock_scope' => false,
        'changes_lock_order' => false,
        'grants_authority' => false,
        'revokes_authority' => false,
        'propagates_authority' => false,
        'execution_authority' => false,
        'continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
