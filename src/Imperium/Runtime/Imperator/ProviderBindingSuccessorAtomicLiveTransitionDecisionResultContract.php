<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-atomic-live-transition-decision-result/v1';
    public const int VERSION = 1;
    public const array REQUIRED_FIELDS = [
        'schema', 'decision_id', 'instance_id', 'producer',
        'principal_input', 'exact_principal', 'source_binding',
        'successor_binding_target', 'adoption_decision', 'v3_admission',
        'adoption_join', 'authority_issuance_target', 'operation_scope',
        'replay_contention_root', 'decision_scope', 'disposition',
        'decision_performed', 'authority_empty', 'live_transition_performed',
        'continuing_authority', 'sealed', 'record_digest',
    ];
    public const array REQUIRED_REFERENCE_FIELDS = ['id', 'digest', 'schema'];
    public const array REQUIRED_ISSUANCE_TARGET_FIELDS = [
        'authority_id', 'authority_schema', 'consumer_service',
        'permitted_transition', 'replay_contention_root', 'single_use',
    ];
    public const array PERMITTED_DISPOSITIONS =
        ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract::PERMITTED_DISPOSITIONS;
    public const array NON_AUTHORITIES = [
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
