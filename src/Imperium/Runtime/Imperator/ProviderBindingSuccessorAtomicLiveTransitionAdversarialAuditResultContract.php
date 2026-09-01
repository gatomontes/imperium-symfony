<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

final class ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditResultContract
{
    public const string SCHEMA =
        'imperium.imperator.provider-binding-successor-atomic-live-transition-adversarial-audit/v1';
    public const array CLASSIFICATIONS = ['PASSED', 'CONFLICTED'];
    public const array REQUIRED_FIELDS = [
        'schema', 'classification', 'findings', 'audited_root', 'read_only',
        'journal_persisted', 'live_lock_acquired', 'state_written_or_repaired',
        'authority_issued_or_consumed', 'execution_admitted', 'successor_adopted',
        'binding_state_changed', 'durable_winner_or_receipt_created',
        'credential_or_capability_handled', 'provider_invoked',
        'external_io_started', 'provider_effect_started', 'retry_authorized',
        'live_command_migrated', 'continuing_authority',
    ];

    private function __construct()
    {
    }
}
