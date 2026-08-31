<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-to-v3-adoption-join-boundary/v1';
    public const int VERSION = 1;
    public const string STATUS = 'CONTRACT_ONLY_NOT_JOINED';
    public const array REQUIRED_FIELDS = [
        'schema', 'join_boundary_id', 'instance_id', 'adoption_decision',
        'completed_successor', 'adoption_target', 'v3_admission',
        'operation_scope', 'replay_contention_root', 'exact_join_required',
        'adoption_decision_authorized', 'join_performed', 'execution_admitted',
        'live_adoption_performed', 'effect_started', 'continuing_authority',
        'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'exact_join_required' => true,
        'adoption_decision_authorized' => false,
        'join_performed' => false,
        'execution_admitted' => false,
        'live_adoption_performed' => false,
        'effect_started' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'decides_adoption' => false,
        'performs_join' => false,
        'adopts_successor' => false,
        'implements_v3_admission' => false,
        'admits_execution' => false,
        'issues_or_consumes_authority' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
