<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class ProviderBindingSuccessorAtomicLiveTransitionReceiptContract
{
    public const string SCHEMA =
        'imperium.la-cortine.provider-binding-successor-atomic-live-transition-receipt/v1';
    public const string STATUS = 'CONTRACT_ONLY_NOT_CREATED';
    public const array REQUIRED_FIELDS = [
        'schema', 'receipt_id', 'instance_id', 'combined_winner',
        'transaction_journal', 'replay_contention_root',
        'combined_commit_observed', 'provider_effect_started',
        'continuing_authority', 'status', 'sealed', 'record_digest',
    ];
    public const array NON_AUTHORITIES =
        ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::NON_AUTHORITIES;

    private function __construct()
    {
    }
}
