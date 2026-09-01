<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class AtomicTransitionEvidenceActionCapabilityManifestContract
{
    public const string SCHEMA = 'imperium.imperator.atomic-transition-evidence-action-capability-manifest/v1';
    public const string STATUS = 'PURE_EVALUATOR_DEPENDENCY_CLOSURE_ONLY';
    public const array CAPABILITIES = [
        'journal_persistence', 'live_lock_acquisition', 'state_write_or_repair',
        'authority_issue_or_consumption', 'execution_admission',
        'successor_adoption', 'binding_state_change',
        'durable_winner_or_receipt_creation', 'live_credential_resolution',
        'provider_invocation', 'external_io', 'provider_effect',
        'retry_authorization', 'live_command_migration',
        'iron_gate_or_lazaretto_opening',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'manifest_id', 'evaluator_classes', 'capabilities',
        'dependency_closure_digest', 'read_only', 'status', 'sealed',
        'record_digest',
    ];

    private function __construct()
    {
    }
}
