<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-atomic-live-transition-transaction-journal/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_OPENED';
    public const array LOCK_ORDER = [
        'replay_contention_root',
        'transition_authority',
        'v3_admission',
        'adoption_join',
        'source_binding',
        'successor_binding',
    ];
    public const array RECOVERY_STATES = [
        'ABSENT', 'PREPARED', 'COMMITTING', 'COMMITTED', 'REFUSED',
    ];
    public const array REQUIRED_FIELDS = [
        'schema', 'journal_id', 'instance_id', 'source_decision',
        'transition_authority', 'replay_contention_root', 'canonical_lock_order',
        'write_set', 'recovery_states', 'status', 'journal_opened',
        'combined_commit_performed', 'continuing_authority', 'sealed',
        'record_digest',
    ];
    public const array REQUIRED_WRITE_SET_FIELDS = [
        'authority_consumption', 'v3_admission', 'adoption_join',
        'source_binding_transition', 'successor_binding_activation',
        'winner_target', 'receipt_target',
    ];
    public const array REQUIRED_TARGET_FIELDS = ['id', 'schema'];
    public const array NON_AUTHORITIES = [
        'opens_journal' => false,
        'acquires_lock' => false,
        'writes_state' => false,
        'recovers_state' => false,
        'consumes_authority' => false,
        'admits_execution' => false,
        'adopts_successor' => false,
        'changes_binding_state' => false,
        'creates_winner' => false,
        'creates_receipt' => false,
        'handles_credential_capability' => false,
        'invokes_provider' => false,
        'starts_external_io' => false,
        'starts_provider_effect' => false,
        'authorizes_retry' => false,
    ];

    private function __construct()
    {
    }
}
