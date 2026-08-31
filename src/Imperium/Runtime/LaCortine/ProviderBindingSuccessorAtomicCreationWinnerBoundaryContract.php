<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract;

final class ProviderBindingSuccessorAtomicCreationWinnerBoundaryContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-atomic-creation-winner-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'INERT_NOT_EXECUTED';
    public const string LOCK_KIND = 'exact_replay_contention_root';
    public const array REQUIRED_FIELDS = [
        'schema', 'winner_boundary_id', 'instance_id', 'authority_schema',
        'authority_source', 'custody_source', 'successor_target',
        'replay_contention_root', 'lock_kind', 'consumer_service',
        'permitted_transition', 'consumption_and_creation_atomic',
        'authority_consumed', 'successor_created', 'partial_record_created',
        'effect_started', 'continuing_authority', 'status', 'sealed',
        'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'authority_schema' =>
            ProviderBindingSuccessorCreationAuthorityV2Contract::SCHEMA,
        'lock_kind' => self::LOCK_KIND,
        'permitted_transition' =>
            ProviderBindingSuccessorCreationAuthorityV2Contract::PERMITTED_TRANSITION,
        'consumption_and_creation_atomic' => true,
        'authority_consumed' => false,
        'successor_created' => false,
        'partial_record_created' => false,
        'effect_started' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'issues_authority' => false,
        'consumes_live_authority' => false,
        'creates_live_successor' => false,
        'repairs_partial_state' => false,
        'reissues_authority' => false,
        'implements_v3_admission' => false,
        'decides_adoption' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'starts_provider_effect' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
