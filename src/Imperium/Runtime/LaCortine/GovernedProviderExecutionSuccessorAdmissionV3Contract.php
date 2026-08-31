<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class GovernedProviderExecutionSuccessorAdmissionV3Contract
{
    public const string SCHEMA =
        'imperium.la-cortine.governed-provider-execution-admission/v3';
    public const int VERSION = 3;
    public const string STATUS = 'NOT_IMPLEMENTED';
    public const string PRODUCER_POSTURE =
        'future-explicit-successor-adoption-pre-effect-admission';
    public const array REQUIRED_FIELDS = [
        'schema', 'admission_boundary_id', 'instance_id', 'completed_successor',
        'atomic_creation_winner', 'adoption_target', 'executor_principal',
        'execution_boundary', 'operation_scope', 'replay_contention_root',
        'legacy_activation_substitution_permitted',
        'successor_synthesis_permitted', 'original_binding_mutation_permitted',
        'credential_resolution_permitted', 'provider_invocation_permitted',
        'external_io_permitted', 'effect_start_permitted',
        'execution_admitted', 'live_adoption_performed',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array INVARIANTS = [
        'legacy_activation_substitution_permitted' => false,
        'successor_synthesis_permitted' => false,
        'original_binding_mutation_permitted' => false,
        'credential_resolution_permitted' => false,
        'provider_invocation_permitted' => false,
        'external_io_permitted' => false,
        'effect_start_permitted' => false,
        'execution_admitted' => false,
        'live_adoption_performed' => false,
        'continuing_authority' => false,
        'status' => self::STATUS,
    ];
    public const array NON_AUTHORITIES = [
        'implements_admission' => false,
        'admits_execution' => false,
        'issues_or_consumes_authority' => false,
        'creates_successor' => false,
        'decides_adoption' => false,
        'adopts_successor' => false,
        'activates_provider_binding' => false,
        'handles_credential_capability' => false,
        'resolves_credentials' => false,
        'invokes_provider' => false,
        'starts_effect' => false,
        'starts_external_io' => false,
        'authorizes_retry' => false,
        'opens_iron_gate' => false,
        'opens_lazaretto' => false,
    ];

    private function __construct()
    {
    }
}
