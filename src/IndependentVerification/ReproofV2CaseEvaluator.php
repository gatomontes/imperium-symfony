<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\ReproofV2\Records;

/** Independent interpretation of the retained finite profile. No producer evaluator imports. */
final class ReproofV2CaseEvaluator
{
    private const string BASE = 'imperium.la-cortine.provider-binding-successor-atomic-live-transition-';
    private const array IDS = ['interruption_before_journal', 'interruption_after_journal', 'interruption_after_winner',
        'interruption_after_receipt', 'exact_replay', 'changed_evidence', 'same_root_contention', 'partial_write'];
    private const array DIRECTIVES = ['ABSENT' => 'NO_ACTION', 'PREPARED' => 'REFUSE_AUTOMATIC_REPAIR',
        'COMMITTING' => 'REFUSE_PARTIAL_STATE', 'COMMITTED' => 'ACCEPT_EXACT_READ_ONLY', 'INCOMPLETE' => 'REFUSE_INCOMPLETE_EVIDENCE'];

    public function evaluate(array $matrix, string $root, string $executorDigest): array
    {
        $this->sealed($matrix, ['schema', 'profile', 'cases', 'input_root', 'expected_root', 'observed_root', 'record_digest'],
            'imperium.atomic-transition-reproof.ordered-matrix/v2');
        $this->require('eight-retained-disposable-cases/v2' === $matrix['profile'] && is_array($matrix['cases'])
            && array_is_list($matrix['cases']) && 8 === count($matrix['cases']));
        $base = $matrix['cases'][3]['input']['primary'] ?? null;
        $aux = $matrix['cases'][3]['input']['auxiliary'] ?? null;
        $this->require(is_array($base) && is_array($aux));
        $this->auxiliary($aux);
        $this->require('COMMITTED' === $this->classify($base, $root, $aux));
        $expectedCuts = ['BEFORE_JOURNAL', 'AFTER_JOURNAL', 'AFTER_WINNER', 'AFTER_RECEIPT', 'NONE', 'NONE', 'NONE', 'MISSING_WINNER'];
        $expectedClasses = ['ABSENT', 'PREPARED', 'COMMITTING', 'COMMITTED', 'COMMITTED', 'COMMITTED', 'COMMITTED', 'INCOMPLETE'];
        $expectedComparisons = ['NOT_APPLICABLE', 'NOT_APPLICABLE', 'NOT_APPLICABLE', 'NOT_APPLICABLE',
            'EXACT_REPLAY', 'CHANGED_EVIDENCE_REFUSED', 'SAME_ROOT_CONTENTION_REFUSED', 'NOT_APPLICABLE'];
        $observations = [];
        foreach ($matrix['cases'] as $index => $case) {
            $this->keys($case, ['input', 'expected', 'observed']);
            $input = $case['input'];
            $this->sealed($input, ['schema', 'case_id', 'root', 'cut', 'primary', 'comparison', 'mutation', 'plan', 'auxiliary', 'record_digest'],
                'imperium.atomic-transition-reproof.case-input/v2');
            $this->require($input['case_id'] === self::IDS[$index] && $input['root'] === $root && $input['cut'] === $expectedCuts[$index]);
            $this->require(Records::same($input['plan'], ['directives' => self::DIRECTIVES, 'automatic_repair' => false, 'runtime_write' => false]));
            $this->auxiliary($input['auxiliary']);
            $classification = $this->classify($input['primary'], $root, $input['auxiliary']);
            $comparison = 'NOT_APPLICABLE';
            if (null !== $input['comparison']) {
                $this->require('COMMITTED' === $classification
                    && 'COMMITTED' === $this->classify($input['comparison'], $root, $input['auxiliary']));
                $comparison = Records::same($input['primary'], $input['comparison']) ? 'EXACT_REPLAY'
                    : ($input['primary']['journal']['journal_id'] === $input['comparison']['journal']['journal_id']
                        ? 'CHANGED_EVIDENCE_REFUSED' : 'SAME_ROOT_CONTENTION_REFUSED');
            }
            $this->require($classification === $expectedClasses[$index] && $comparison === $expectedComparisons[$index]);
            $this->relations($index, $input, $base);
            $values = ['classification' => $classification, 'directive' => self::DIRECTIVES[$classification],
                'comparison' => $comparison, 'validator_error' => null,
                'findings' => ['NOT_APPLICABLE' === $comparison ? $classification.'_READ_ONLY' : $comparison]];
            $expected = Records::seal(['schema' => 'imperium.atomic-transition-reproof.expected-result/v2', 'case_id' => self::IDS[$index]] + $values);
            $observed = Records::seal(['schema' => 'imperium.atomic-transition-reproof.observation/v2', 'case_id' => self::IDS[$index],
                'input_digest' => $input['record_digest'], 'expected_digest' => $expected['record_digest'],
                'executor_digest' => $executorDigest] + $values);
            $this->require(Records::same($expected, $case['expected']) && Records::same($observed, $case['observed']));
            $observations[] = $observed;
        }
        foreach (['input', 'expected', 'observed'] as $kind) {
            $this->require($matrix[$kind.'_root'] === Records::hash(array_column(array_column($matrix['cases'], $kind), 'record_digest')));
        }
        return $observations;
    }

    private function relations(int $index, array $input, array $base): void
    {
        $primary = match ($index) { 0 => [], 1 => ['journal' => $base['journal']],
            2 => ['journal' => $base['journal'], 'winner' => $base['winner']],
            7 => ['journal' => $base['journal'], 'receipt' => $base['receipt']], default => $base };
        $this->require(Records::same($primary, $input['primary']));
        $this->require('journal.1' === $base['journal']['journal_id']);
        $this->require($base['journal']['source_decision']['digest'] === Records::hash($input['auxiliary']['decision']));
        $mutation = ['kind' => 'NONE', 'target_path' => null, 'replacement' => null];
        if (5 === $index) {
            $other = $input['comparison'];
            $this->require('journal.1' === $other['journal']['journal_id']);
            $this->require($other['journal']['source_decision']['digest'] === Records::hash($input['auxiliary']['changed-decision']));
            // All other non-link fields must be identical; seals and links are independently checked below.
            foreach (['journal', 'winner', 'receipt'] as $section) {
                $left = $base[$section]; $right = $other[$section];
                foreach (['record_digest', 'source_decision', 'transaction_journal', 'combined_winner'] as $field) {
                    unset($left[$field], $right[$field]);
                }
                $this->require(Records::same($left, $right));
            }
            $mutation = ['kind' => 'SUBSTITUTE_REFERENCE_AND_RESEAL', 'target_path' => 'comparison.journal.source_decision', 'replacement' => $other['journal']['source_decision']];
        } elseif (6 === $index) {
            $other = $input['comparison'];
            $this->require('journal.2' === $other['journal']['journal_id']);
            $this->require($base['journal']['source_decision'] === $other['journal']['source_decision']);
            $mutation = ['kind' => 'DISTINCT_JOURNAL', 'target_path' => 'comparison.journal.journal_id', 'replacement' => 'journal.2'];
        } elseif (7 === $index) {
            $mutation = ['kind' => 'REMOVE_WINNER', 'target_path' => 'primary.winner', 'replacement' => null];
        }
        $this->require(Records::same($mutation, $input['mutation']));
    }

    private function classify(array $snapshot, string $root, array $aux): string
    {
        if ([] === $snapshot) { return 'ABSENT'; }
        $this->require(in_array(array_keys($snapshot), [['journal'], ['journal', 'winner'], ['journal', 'winner', 'receipt'], ['journal', 'receipt']], true));
        $j = $snapshot['journal'];
        $this->sealed($j, ['schema', 'journal_id', 'instance_id', 'source_decision', 'transition_authority', 'replay_contention_root',
            'canonical_lock_order', 'write_set', 'recovery_states', 'status', 'journal_opened', 'combined_commit_performed', 'continuing_authority', 'sealed', 'record_digest'],
            self::BASE.'transaction-journal/v1');
        $this->require(in_array($j['journal_id'], ['journal.1', 'journal.2'], true) && 'instance.1' === $j['instance_id'] && $root === $j['replay_contention_root']);
        $this->require($j['canonical_lock_order'] === ['replay_contention_root', 'transition_authority', 'v3_admission', 'adoption_join', 'source_binding', 'successor_binding']);
        $this->require($j['recovery_states'] === ['ABSENT', 'PREPARED', 'COMMITTING', 'COMMITTED', 'REFUSED'] && 'CONTRACT_ONLY_NOT_OPENED' === $j['status']);
        $this->falseFields($j, ['journal_opened', 'combined_commit_performed', 'continuing_authority']);
        $decision = $this->ref('decision.1', 'decision/v1', Records::hash($aux['decision']));
        $changed = $this->ref('decision.1', 'decision/v1', Records::hash($aux['changed-decision']));
        $this->require(Records::same($j['source_decision'], $decision) || Records::same($j['source_decision'], $changed));
        $this->require(Records::same($j['transition_authority'], $this->ref('authority.1', 'authority/v1', Records::hash($aux['authority']))));
        $targets = ['authority_consumption' => ['authority.1', 'authority-consumption/v1'],
            'v3_admission' => ['admission.1', 'admission/v3'], 'adoption_join' => ['join.1', 'adoption-join/v1'],
            'source_binding_transition' => ['source-binding.1', 'binding-transition/v1'],
            'successor_binding_activation' => ['successor-binding.1', 'binding-activation/v1'],
            'winner_target' => ['winner.'.$j['journal_id'], self::BASE.'combined-winner/v1'],
            'receipt_target' => ['receipt.'.$j['journal_id'], self::BASE.'receipt/v1']];
        $writeSet = [];
        foreach ($targets as $name => [$id, $schema]) { $writeSet[$name] = ['id' => $id, 'schema' => $schema]; }
        $this->require(Records::same($j['write_set'], $writeSet));
        if (!isset($snapshot['winner'])) { return isset($snapshot['receipt']) ? 'INCOMPLETE' : 'PREPARED'; }
        $w = $snapshot['winner'];
        $this->sealed($w, ['schema', 'winner_id', 'instance_id', 'transaction_journal', 'source_decision', 'transition_authority',
            'v3_admission', 'adoption_join', 'source_binding_transition', 'successor_binding_activation', 'replay_contention_root',
            'authority_consumed', 'execution_admitted', 'successor_adopted', 'source_binding_deactivated', 'successor_binding_activated',
            'combined_commit_performed', 'continuing_authority', 'status', 'sealed', 'record_digest'], self::BASE.'combined-winner/v1');
        $this->require($w['winner_id'] === 'winner.'.$j['journal_id'] && $w['instance_id'] === $j['instance_id']
            && $w['replay_contention_root'] === $root && 'CONTRACT_ONLY_NOT_CREATED' === $w['status']);
        $this->require(Records::same($w['transaction_journal'], $this->ref($j['journal_id'], $j['schema'], $j['record_digest'])));
        foreach (['source_decision', 'transition_authority'] as $key) { $this->require(Records::same($w[$key], $j[$key])); }
        foreach (['v3_admission' => 'admission', 'adoption_join' => 'join', 'source_binding_transition' => 'source-binding', 'successor_binding_activation' => 'successor-binding'] as $key => $kind) {
            $this->require(Records::same($w[$key], $this->ref($targets[$key][0], $targets[$key][1], Records::hash($aux[$kind]))));
        }
        $this->falseFields($w, ['authority_consumed', 'execution_admitted', 'successor_adopted', 'source_binding_deactivated',
            'successor_binding_activated', 'combined_commit_performed', 'continuing_authority']);
        if (!isset($snapshot['receipt'])) { return 'COMMITTING'; }
        $r = $snapshot['receipt'];
        $this->sealed($r, ['schema', 'receipt_id', 'instance_id', 'combined_winner', 'transaction_journal', 'replay_contention_root',
            'combined_commit_observed', 'provider_effect_started', 'continuing_authority', 'status', 'sealed', 'record_digest'], self::BASE.'receipt/v1');
        $this->require($r['receipt_id'] === 'receipt.'.$j['journal_id'] && $r['instance_id'] === $j['instance_id']
            && $r['replay_contention_root'] === $root && 'CONTRACT_ONLY_NOT_CREATED' === $r['status']);
        $this->require(Records::same($r['transaction_journal'], $this->ref($j['journal_id'], $j['schema'], $j['record_digest']))
            && Records::same($r['combined_winner'], $this->ref($w['winner_id'], $w['schema'], $w['record_digest'])));
        $this->falseFields($r, ['combined_commit_observed', 'provider_effect_started', 'continuing_authority']);
        return 'COMMITTED';
    }

    private function auxiliary(array $aux): void
    {
        $expected = [];
        foreach (['decision', 'changed-decision', 'authority', 'admission', 'join', 'source-binding', 'successor-binding'] as $kind) {
            $expected[$kind] = ['schema' => 'imperium.synthetic-reference/v2', 'kind' => $kind, 'authority_empty' => true];
        }
        $this->require(Records::same($aux, $expected));
    }

    private function falseFields(array $record, array $fields): void
    {
        foreach ($fields as $field) { $this->require(false === $record[$field]); }
    }

    private function ref(string $id, string $schema, string $digest): array
    {
        return ['id' => $id, 'digest' => $digest, 'schema' => $schema];
    }

    private function sealed(array $record, array $fields, string $schema): void
    {
        $this->keys($record, $fields);
        $this->require($record['schema'] === $schema && Records::same($record, Records::seal($record)));
        if (array_key_exists('sealed', $record)) { $this->require(true === $record['sealed']); }
    }

    private function keys(array $record, array $fields): void
    {
        $keys = array_keys($record); sort($keys); sort($fields); $this->require($keys === $fields);
    }

    private function require(bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('REPROOF_INDEPENDENT_CASE_REFUSED'); }
    }
}
