<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final class ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier
{
    public const array INTERRUPTION_CLASSIFICATIONS = [
        'BEFORE_JOURNAL' => 'ABSENT',
        'AFTER_JOURNAL' => 'PREPARED',
        'AFTER_WINNER' => 'COMMITTING',
        'AFTER_RECEIPT' => 'COMMITTED',
    ];

    public function __construct(
        private readonly ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator $validator,
    ) {
    }

    public function classify(array $evidence): string
    {
        if ([] === $evidence) {
            return 'ABSENT';
        }

        $journal = $evidence['journal'] ?? null;
        $winner = $evidence['winner'] ?? null;
        $receipt = $evidence['receipt'] ?? null;
        if (!is_array($journal)) {
            return 'INCOMPLETE';
        }

        $this->validator->assertJournal($journal);
        if (null === $winner && null === $receipt) {
            return 'PREPARED';
        }
        if (!is_array($winner)) {
            return 'INCOMPLETE';
        }

        $this->validator->assertWinner($winner, $journal);
        if (null === $receipt) {
            return 'COMMITTING';
        }
        if (!is_array($receipt)) {
            return 'INCOMPLETE';
        }

        $this->validator->assertReceipt($receipt, $winner, $journal);

        return 'COMMITTED';
    }

    public function assertInterruptionCut(string $cut, array $evidence): void
    {
        $expected = self::INTERRUPTION_CLASSIFICATIONS[$cut] ?? null;
        if (null === $expected || $expected !== $this->classify($evidence)) {
            throw new \RuntimeException(
                'PBL930_ATOMIC_TRANSITION_INTERRUPTION_CLASSIFICATION_INVALID',
            );
        }
    }

    public function compare(array $left, array $right): string
    {
        if ('COMMITTED' !== $this->classify($left)
            || 'COMMITTED' !== $this->classify($right)) {
            return 'INCOMPLETE_COMPARISON_REFUSED';
        }

        $leftJournal = $left['journal'];
        $rightJournal = $right['journal'];
        if ($leftJournal['replay_contention_root']
            !== $rightJournal['replay_contention_root']) {
            return 'DISTINCT_ROOTS';
        }
        if (CanonicalJson::encode($left) === CanonicalJson::encode($right)) {
            return 'EXACT_REPLAY';
        }
        if ($leftJournal['journal_id'] === $rightJournal['journal_id']) {
            return 'CHANGED_EVIDENCE_REFUSED';
        }

        return 'SAME_ROOT_CONTENTION_REFUSED';
    }
}
