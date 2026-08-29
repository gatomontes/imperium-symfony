<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ActivationTransitionInterruptionEvidenceContract
{
    public const string SCHEMA = 'imperium.imperator.activation-transition-interruption-evidence/v1';
    public const int VERSION = 1;
    public const string PRODUCER_POSTURE = 'imperator.offline-activation-transition-interruption-demonstration';
    public const array CONSUMER_POSTURES = ['imperator.activation-corridor-disposition', 'la-cortine.read-only-reconstruction'];
    public const array REQUIRED_FIELDS = ['schema', 'evidence_id', 'instance_id', 'transition', 'source_authority', 'target_identity', 'cut', 'pre_cut_state', 'post_restart_state', 'retry', 'expiry', 'contention', 'classification', 'observed_at', 'target_created', 'external_action_performed', 'sealed', 'record_digest'];
    public const array TRANSITIONS = ['DECIDE_EXACT_PROVIDER_BINDING_ACTIVATION', 'ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_AUTHORITY'];
    public const array CUTS = ['BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_CONSUMPTION_BEFORE_TARGET_COMMIT', 'AFTER_TARGET_COMMIT'];
    public const array CLASSIFICATIONS = ['CONVERGENT_RECOVERABLE', 'TERMINAL_REFUSAL', 'CONFLICTING', 'UNPROVED'];
    public const array NON_AUTHORITIES = ['consumes_live_authority' => false, 'creates_live_decision' => false, 'issues_activation_authority' => false, 'activates_binding' => false, 'authorizes_retry' => false, 'starts_external_io' => false];

    private function __construct() {}
}
