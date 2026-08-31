<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-execution-adoption-decision-boundary/v1';
    public const int VERSION = 1;
    public const string DECISION_SCOPE =
        'DECIDE_EXACT_SUCCESSOR_TO_V3_EXECUTION_ADOPTION';
    public const string STATUS = 'CONTRACT_ONLY_NOT_DECIDED';
    public const array REQUIRED_FIELDS = [
        'schema', 'decision_boundary_id', 'instance_id', 'exact_principal',
        'completed_successor', 'adoption_target', 'v3_admission',
        'operation_scope', 'replay_contention_root', 'decision_scope',
        'permitted_dispositions', 'authority_empty', 'decision_performed',
        'disposition', 'live_adoption_performed', 'continuing_authority',
        'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array PERMITTED_DISPOSITIONS = ['AUTHORIZED', 'REFUSED'];
    public const array INVARIANTS = [
        'decision_scope' => self::DECISION_SCOPE,
        'permitted_dispositions' => self::PERMITTED_DISPOSITIONS,
        'authority_empty' => true,
        'decision_performed' => false,
        'disposition' => 'NOT_DECIDED',
        'live_adoption_performed' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'activates_principal' => false,
        'decides_adoption' => false,
        'adopts_successor' => false,
        'implements_v3_admission' => false,
        'admits_execution' => false,
        'issues_or_consumes_authority' => false,
        'creates_successor' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'starts_provider_effect' => false,
        'starts_external_io' => false,
    ];

    private function __construct()
    {
    }
}
