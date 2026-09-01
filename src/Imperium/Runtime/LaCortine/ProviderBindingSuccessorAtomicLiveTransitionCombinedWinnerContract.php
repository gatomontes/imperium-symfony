<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-atomic-live-transition-combined-winner/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_CREATED';
    public const array REQUIRED_FIELDS = [
        'schema', 'winner_id', 'instance_id', 'transaction_journal',
        'source_decision', 'transition_authority', 'v3_admission',
        'adoption_join', 'source_binding_transition',
        'successor_binding_activation', 'replay_contention_root',
        'authority_consumed', 'execution_admitted', 'successor_adopted',
        'source_binding_deactivated', 'successor_binding_activated',
        'combined_commit_performed', 'continuing_authority', 'status',
        'sealed', 'record_digest',
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
