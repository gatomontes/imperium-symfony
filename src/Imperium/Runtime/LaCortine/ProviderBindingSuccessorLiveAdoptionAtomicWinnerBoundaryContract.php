<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorLiveAdoptionAuthorityContract;

final class ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-live-adoption-atomic-winner-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'INERT_NOT_EXECUTED';
    public const string LOCK_KIND = 'exact_replay_contention_root';
    public const array REQUIRED_FIELDS = [
        'schema', 'winner_boundary_id', 'instance_id', 'adoption_decision',
        'authority_schema', 'authority_source', 'custody_source',
        'completed_successor', 'atomic_creation_winner', 'adoption_target',
        'v3_admission', 'adoption_join', 'original_binding',
        'successor_binding_target', 'replay_contention_root', 'lock_kind',
        'consumer_service', 'permitted_transition',
        'admission_consumption_adoption_and_binding_atomic',
        'authority_consumed', 'execution_admitted', 'successor_adopted',
        'binding_transitioned', 'partial_record_created', 'effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'authority_schema' =>
            ProviderBindingSuccessorLiveAdoptionAuthorityContract::SCHEMA,
        'lock_kind' => self::LOCK_KIND,
        'permitted_transition' =>
            ProviderBindingSuccessorLiveAdoptionAuthorityContract::PERMITTED_TRANSITION,
        'admission_consumption_adoption_and_binding_atomic' => true,
        'authority_consumed' => false,
        'execution_admitted' => false,
        'successor_adopted' => false,
        'binding_transitioned' => false,
        'partial_record_created' => false,
        'effect_started' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'produces_decision' => false,
        'issues_authority' => false,
        'consumes_live_authority' => false,
        'admits_live_execution' => false,
        'adopts_live_successor' => false,
        'changes_live_binding_state' => false,
        'repairs_partial_state' => false,
        'reissues_authority' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_provider_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
    ];

    private function __construct()
    {
    }
}
