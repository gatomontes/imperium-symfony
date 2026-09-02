<?php

declare(strict_types=1);

namespace App\ReproofV2;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;

/** Finite synthetic inputs. Never accepts caller conclusions or operational authority. */
final class CaseProfile
{
    public function inputs(string $root): array
    {
        if (!preg_match('/^reproof-v2-[a-z0-9-]{3,80}$/D', $root)) {
            throw new \RuntimeException('REPROOF_ROOT_INVALID');
        }
        $auxiliary = [];
        foreach (['decision', 'changed-decision', 'authority', 'admission', 'join', 'source-binding', 'successor-binding'] as $kind) {
            $auxiliary[$kind] = ['schema' => 'imperium.synthetic-reference/v2', 'kind' => $kind, 'authority_empty' => true];
        }
        $full = $this->complete($root, 'journal.1', $auxiliary, 'decision');
        $changed = $this->complete($root, 'journal.1', $auxiliary, 'changed-decision');
        $contender = $this->complete($root, 'journal.2', $auxiliary, 'decision');
        $snapshots = [[], ['journal' => $full['journal']],
            ['journal' => $full['journal'], 'winner' => $full['winner']], $full,
            $full, $full, $full, ['journal' => $full['journal'], 'receipt' => $full['receipt']]];
        $cuts = ['BEFORE_JOURNAL', 'AFTER_JOURNAL', 'AFTER_WINNER', 'AFTER_RECEIPT', 'NONE', 'NONE', 'NONE', 'MISSING_WINNER'];
        $classifications = ['ABSENT', 'PREPARED', 'COMMITTING', 'COMMITTED', 'COMMITTED', 'COMMITTED', 'COMMITTED', 'INCOMPLETE'];
        $comparisons = ['NOT_APPLICABLE', 'NOT_APPLICABLE', 'NOT_APPLICABLE', 'NOT_APPLICABLE',
            'EXACT_REPLAY', 'CHANGED_EVIDENCE_REFUSED', 'SAME_ROOT_CONTENTION_REFUSED', 'NOT_APPLICABLE'];
        $result = [];
        foreach (Contract::CASES as $i => $id) {
            $comparison = match ($i) { 4 => $full, 5 => $changed, 6 => $contender, default => null };
            $mutation = match ($i) {
                5 => ['kind' => 'SUBSTITUTE_REFERENCE_AND_RESEAL', 'target_path' => 'comparison.journal.source_decision', 'replacement' => $changed['journal']['source_decision']],
                6 => ['kind' => 'DISTINCT_JOURNAL', 'target_path' => 'comparison.journal.journal_id', 'replacement' => 'journal.2'],
                7 => ['kind' => 'REMOVE_WINNER', 'target_path' => 'primary.winner', 'replacement' => null],
                default => ['kind' => 'NONE', 'target_path' => null, 'replacement' => null],
            };
            $input = Records::seal(['schema' => Contract::SCHEMAS['input'], 'case_id' => $id,
                'root' => $root, 'cut' => $cuts[$i], 'primary' => $snapshots[$i], 'comparison' => $comparison,
                'mutation' => $mutation, 'plan' => ['directives' => Plan::DIRECTIVES, 'automatic_repair' => false, 'runtime_write' => false],
                'auxiliary' => $auxiliary]);
            $expected = Records::seal(['schema' => Contract::SCHEMAS['expected'], 'case_id' => $id,
                'classification' => $classifications[$i], 'directive' => Plan::DIRECTIVES[$classifications[$i]],
                'comparison' => $comparisons[$i], 'validator_error' => null,
                'findings' => ['NOT_APPLICABLE' === $comparisons[$i] ? $classifications[$i].'_READ_ONLY' : $comparisons[$i]]]);
            $result[] = ['input' => $input, 'expected' => $expected];
        }
        return $result;
    }

    private function complete(string $root, string $id, array $aux, string $decision): array
    {
        $ref = static fn (string $kind, string $identity, string $schema): array => [
            'id' => $identity, 'digest' => Records::hash($aux[$kind]), 'schema' => $schema];
        $target = static fn (string $identity, string $schema): array => ['id' => $identity, 'schema' => $schema];
        $journal = Records::seal([
            'schema' => Journal::SCHEMA, 'journal_id' => $id, 'instance_id' => 'instance.1',
            'source_decision' => $ref($decision, 'decision.1', 'decision/v1'),
            'transition_authority' => $ref('authority', 'authority.1', 'authority/v1'),
            'replay_contention_root' => $root, 'canonical_lock_order' => Journal::LOCK_ORDER,
            'write_set' => [
                'authority_consumption' => $target('authority.1', 'authority-consumption/v1'),
                'v3_admission' => $target('admission.1', 'admission/v3'),
                'adoption_join' => $target('join.1', 'adoption-join/v1'),
                'source_binding_transition' => $target('source-binding.1', 'binding-transition/v1'),
                'successor_binding_activation' => $target('successor-binding.1', 'binding-activation/v1'),
                'winner_target' => $target('winner.'.$id, Winner::SCHEMA),
                'receipt_target' => $target('receipt.'.$id, Receipt::SCHEMA),
            ], 'recovery_states' => Journal::RECOVERY_STATES, 'status' => Journal::STATUS,
            'journal_opened' => false, 'combined_commit_performed' => false, 'continuing_authority' => false, 'sealed' => true,
        ]);
        $winner = Records::seal([
            'schema' => Winner::SCHEMA, 'winner_id' => 'winner.'.$id, 'instance_id' => 'instance.1',
            'transaction_journal' => $this->reference($journal, 'journal_id'),
            'source_decision' => $journal['source_decision'], 'transition_authority' => $journal['transition_authority'],
            'v3_admission' => $ref('admission', 'admission.1', 'admission/v3'),
            'adoption_join' => $ref('join', 'join.1', 'adoption-join/v1'),
            'source_binding_transition' => $ref('source-binding', 'source-binding.1', 'binding-transition/v1'),
            'successor_binding_activation' => $ref('successor-binding', 'successor-binding.1', 'binding-activation/v1'),
            'replay_contention_root' => $root, 'authority_consumed' => false, 'execution_admitted' => false,
            'successor_adopted' => false, 'source_binding_deactivated' => false, 'successor_binding_activated' => false,
            'combined_commit_performed' => false, 'continuing_authority' => false, 'status' => Winner::STATUS, 'sealed' => true,
        ]);
        $receipt = Records::seal([
            'schema' => Receipt::SCHEMA, 'receipt_id' => 'receipt.'.$id, 'instance_id' => 'instance.1',
            'combined_winner' => $this->reference($winner, 'winner_id'), 'transaction_journal' => $this->reference($journal, 'journal_id'),
            'replay_contention_root' => $root, 'combined_commit_observed' => false, 'provider_effect_started' => false,
            'continuing_authority' => false, 'status' => Receipt::STATUS, 'sealed' => true,
        ]);
        return ['journal' => $journal, 'winner' => $winner, 'receipt' => $receipt];
    }

    private function reference(array $record, string $field): array
    {
        return ['id' => $record[$field], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }
}
