<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-atomic-live-transition-decision-principal-input/v1';
    public const int VERSION = 1;
    public const string DECISION_SCOPE =
        'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_ATOMIC_LIVE_TRANSITION';
    public const string STATUS = 'CONTRACT_ONLY_AUTHORITY_EMPTY';
    public const array REQUIRED_FIELDS = [
        'schema', 'input_id', 'instance_id', 'exact_principal',
        'source_binding', 'successor_binding_target', 'adoption_decision',
        'v3_admission', 'adoption_join', 'operation_scope',
        'replay_contention_root', 'decision_scope',
        'exact_combined_transition_required', 'authority_empty',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array NON_AUTHORITIES = [
        'produces_decision' => false,
        'issues_authority' => false,
        'consumes_authority' => false,
        'admits_execution' => false,
        'adopts_successor' => false,
        'changes_binding_state' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'starts_provider_effect' => false,
        'authorizes_retry' => false,
        'grants_continuing_authority' => false,
    ];

    private function __construct()
    {
    }
}
