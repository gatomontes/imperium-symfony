<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final class ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator
{
    public function assertJournal(array $journal): void
    {
        $this->sealed(
            $journal,
            ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::SCHEMA,
            'PBL900_ATOMIC_TRANSITION_JOURNAL_INVALID',
        );

        $writeSet = $journal['write_set'] ?? null;
        if (!$this->identifier($journal['journal_id'] ?? null)
            || !$this->identifier($journal['instance_id'] ?? null)
            || !$this->reference($journal['source_decision'] ?? null)
            || !$this->reference($journal['transition_authority'] ?? null)
            || !$this->identifier($journal['replay_contention_root'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::LOCK_ORDER
                !== ($journal['canonical_lock_order'] ?? null)
            || !$this->writeSet($writeSet)
            || ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::RECOVERY_STATES
                !== ($journal['recovery_states'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::STATUS
                !== ($journal['status'] ?? null)
            || false !== ($journal['journal_opened'] ?? null)
            || false !== ($journal['combined_commit_performed'] ?? null)
            || false !== ($journal['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PBL900_ATOMIC_TRANSITION_JOURNAL_INVALID');
        }
    }

    public function assertWinner(array $winner, array $journal): void
    {
        $this->assertJournal($journal);
        $this->sealed(
            $winner,
            ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract::SCHEMA,
            'PBL910_ATOMIC_TRANSITION_WINNER_INVALID',
        );

        if (!$this->identifier($winner['winner_id'] ?? null)
            || ($winner['instance_id'] ?? null) !== $journal['instance_id']
            || ($winner['transaction_journal'] ?? null)
                !== $this->referenceFor($journal, 'journal_id')
            || ($winner['source_decision'] ?? null) !== $journal['source_decision']
            || ($winner['transition_authority'] ?? null)
                !== $journal['transition_authority']
            || ($winner['replay_contention_root'] ?? null)
                !== $journal['replay_contention_root']
            || !$this->referencesMatchWriteSet($winner, $journal['write_set'])
            || false !== ($winner['authority_consumed'] ?? null)
            || false !== ($winner['execution_admitted'] ?? null)
            || false !== ($winner['successor_adopted'] ?? null)
            || false !== ($winner['source_binding_deactivated'] ?? null)
            || false !== ($winner['successor_binding_activated'] ?? null)
            || false !== ($winner['combined_commit_performed'] ?? null)
            || false !== ($winner['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract::STATUS
                !== ($winner['status'] ?? null)) {
            throw new \RuntimeException('PBL910_ATOMIC_TRANSITION_WINNER_INVALID');
        }
    }

    public function assertReceipt(
        array $receipt,
        array $winner,
        array $journal,
    ): void {
        $this->assertWinner($winner, $journal);
        $this->sealed(
            $receipt,
            ProviderBindingSuccessorAtomicLiveTransitionReceiptContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionReceiptContract::SCHEMA,
            'PBL920_ATOMIC_TRANSITION_RECEIPT_INVALID',
        );

        if (!$this->identifier($receipt['receipt_id'] ?? null)
            || ($receipt['instance_id'] ?? null) !== $journal['instance_id']
            || ($receipt['combined_winner'] ?? null)
                !== $this->referenceFor($winner, 'winner_id')
            || ($receipt['transaction_journal'] ?? null)
                !== $this->referenceFor($journal, 'journal_id')
            || ($receipt['replay_contention_root'] ?? null)
                !== $journal['replay_contention_root']
            || false !== ($receipt['combined_commit_observed'] ?? null)
            || false !== ($receipt['provider_effect_started'] ?? null)
            || false !== ($receipt['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionReceiptContract::STATUS
                !== ($receipt['status'] ?? null)) {
            throw new \RuntimeException('PBL920_ATOMIC_TRANSITION_RECEIPT_INVALID');
        }
    }

    private function referencesMatchWriteSet(array $winner, array $writeSet): bool
    {
        foreach ([
            'v3_admission', 'adoption_join', 'source_binding_transition',
            'successor_binding_activation',
        ] as $field) {
            $target = $writeSet[$field] ?? null;
            $reference = $winner[$field] ?? null;
            if (!is_array($target) || !is_array($reference)
                || ($target['id'] ?? null) !== ($reference['id'] ?? null)
                || ($target['schema'] ?? null) !== ($reference['schema'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function writeSet(mixed $value): bool
    {
        if (!is_array($value)
            || ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::REQUIRED_WRITE_SET_FIELDS
                !== array_keys($value)) {
            return false;
        }
        foreach ($value as $target) {
            if (!is_array($target)
                || ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract::REQUIRED_TARGET_FIELDS
                    !== array_keys($target)
                || !$this->identifier($target['id'] ?? null)
                || !$this->identifier($target['schema'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function sealed(
        array $record,
        array $fields,
        string $schema,
        string $error,
    ): void {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function reference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema'] === array_keys($value)
            && $this->identifier($value['id'] ?? null)
            && is_string($value['digest'] ?? null)
            && (bool) preg_match('/^[a-f0-9]{64}$/', $value['digest'])
            && $this->identifier($value['schema'] ?? null);
    }

    private function referenceFor(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }
}
