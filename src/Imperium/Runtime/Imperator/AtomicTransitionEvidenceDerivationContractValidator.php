<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;

final readonly class AtomicTransitionEvidenceDerivationContractValidator
{
    public function __construct(private TransactionValidator $transactionValidator)
    {
    }

    public function assertFixture(array $fixture): void
    {
        $this->sealed($fixture, AtomicTransitionEvidenceFixtureContract::REQUIRED_FIELDS, AtomicTransitionEvidenceFixtureContract::SCHEMA, 'PBL960_EVIDENCE_FIXTURE_INVALID');
        $evidence = $fixture['evidence'] ?? null;
        if (!$this->identifier($fixture['fixture_id'] ?? null)
            || !$this->identifier($fixture['instance_id'] ?? null)
            || !$this->identifier($fixture['replay_contention_root'] ?? null)
            || !in_array($fixture['fixture_kind'] ?? null, AtomicTransitionEvidenceFixtureContract::KINDS, true)
            || !is_array($evidence)
            || [Journal::SCHEMA, Winner::SCHEMA, Receipt::SCHEMA] !== ($fixture['source_contracts'] ?? null)
            || true !== ($fixture['immutable'] ?? null)
            || AtomicTransitionEvidenceFixtureContract::STATUS !== ($fixture['status'] ?? null)
            || !$this->fixtureEvidence($fixture['fixture_kind'], $evidence, $fixture['instance_id'], $fixture['replay_contention_root'])) {
            throw new \RuntimeException('PBL960_EVIDENCE_FIXTURE_INVALID');
        }
    }

    public function assertMutation(array $mutation): void
    {
        $this->sealed($mutation, AtomicTransitionEvidenceMutationContract::REQUIRED_FIELDS, AtomicTransitionEvidenceMutationContract::SCHEMA, 'PBL961_EVIDENCE_MUTATION_INVALID');
        $kind = $mutation['mutation_kind'] ?? null;
        $none = 'NONE' === $kind;
        if (!$this->identifier($mutation['mutation_id'] ?? null)
            || !in_array($kind, AtomicTransitionEvidenceMutationContract::KINDS, true)
            || ($none && (null !== ($mutation['target_path'] ?? null) || null !== ($mutation['replacement_digest'] ?? null)))
            || (!$none && (!$this->path($mutation['target_path'] ?? null) || !$this->digest($mutation['replacement_digest'] ?? null)))
            || !$this->nullableError($mutation['expected_validator_error'] ?? null)
            || false !== ($mutation['mutation_applied'] ?? null)
            || AtomicTransitionEvidenceMutationContract::STATUS !== ($mutation['status'] ?? null)) {
            throw new \RuntimeException('PBL961_EVIDENCE_MUTATION_INVALID');
        }
    }

    public function assertExpectedResult(array $expected): void
    {
        $this->sealed($expected, AtomicTransitionEvidenceExpectedResultContract::REQUIRED_FIELDS, AtomicTransitionEvidenceExpectedResultContract::SCHEMA, 'PBL962_EXPECTED_RESULT_INVALID');
        $findings = $expected['expected_finding_codes'] ?? null;
        if (!$this->identifier($expected['expected_result_id'] ?? null)
            || !in_array($expected['expected_classification'] ?? null, AtomicTransitionEvidenceExpectedResultContract::CLASSIFICATIONS, true)
            || !in_array($expected['expected_directive'] ?? null, AtomicTransitionEvidenceExpectedResultContract::DIRECTIVES, true)
            || !in_array($expected['expected_comparison'] ?? null, AtomicTransitionEvidenceExpectedResultContract::COMPARISONS, true)
            || $this->directiveFor($expected['expected_classification']) !== $expected['expected_directive']
            || !$this->nullableError($expected['expected_validator_error'] ?? null)
            || !is_array($findings) || [] === $findings
            || count($findings) !== count(array_unique($findings))
            || array_filter($findings, fn (mixed $finding): bool => !$this->code($finding))
            || false !== ($expected['result_derived'] ?? null)
            || AtomicTransitionEvidenceExpectedResultContract::STATUS !== ($expected['status'] ?? null)) {
            throw new \RuntimeException('PBL962_EXPECTED_RESULT_INVALID');
        }
    }

    public function assertCase(array $case, array $primaryFixture, ?array $comparisonFixture, array $mutation, array $expected): void
    {
        $this->assertFixture($primaryFixture);
        if (null !== $comparisonFixture) {
            $this->assertFixture($comparisonFixture);
        }
        $this->assertMutation($mutation);
        $this->assertExpectedResult($expected);
        $this->sealed($case, AtomicTransitionEvidenceAdversarialCaseContract::REQUIRED_FIELDS, AtomicTransitionEvidenceAdversarialCaseContract::SCHEMA, 'PBL963_ADVERSARIAL_CASE_INVALID');
        $comparisonReference = null === $comparisonFixture ? null : $this->reference($comparisonFixture, 'fixture_id');
        $comparisonRequired = in_array($case['case_kind'] ?? null, ['EXACT_REPLAY', 'CHANGED_EVIDENCE', 'SAME_ROOT_CONTENTION'], true);
        if (!$this->identifier($case['case_id'] ?? null)
            || !in_array($case['case_kind'] ?? null, AtomicTransitionEvidenceAdversarialCaseContract::KINDS, true)
            || ($case['replay_contention_root'] ?? null) !== $primaryFixture['replay_contention_root']
            || ($case['primary_fixture'] ?? null) !== $this->reference($primaryFixture, 'fixture_id')
            || ($case['comparison_fixture'] ?? null) !== $comparisonReference
            || $comparisonRequired !== (null !== $comparisonFixture)
            || !$this->caseExpectationMatches($case['case_kind'], $expected)
            || ($case['mutation'] ?? null) !== $this->reference($mutation, 'mutation_id')
            || ($case['expected_result'] ?? null) !== $this->reference($expected, 'expected_result_id')
            || false !== ($case['case_executed'] ?? null)
            || false !== ($case['finding_derived'] ?? null)
            || AtomicTransitionEvidenceAdversarialCaseContract::STATUS !== ($case['status'] ?? null)) {
            throw new \RuntimeException('PBL963_ADVERSARIAL_CASE_INVALID');
        }
    }

    private function fixtureEvidence(string $kind, array $evidence, string $instance, string $root): bool
    {
        try {
            if ('EMPTY' === $kind) {
                return [] === $evidence;
            }
            $journal = $evidence['journal'] ?? null;
            if (!is_array($journal) || ($journal['instance_id'] ?? null) !== $instance || ($journal['replay_contention_root'] ?? null) !== $root) {
                return false;
            }
            $this->transactionValidator->assertJournal($journal);
            if ('JOURNAL_ONLY' === $kind) {
                return ['journal'] === array_keys($evidence);
            }
            $winner = $evidence['winner'] ?? null;
            if (!is_array($winner)) {
                return false;
            }
            $this->transactionValidator->assertWinner($winner, $journal);
            if ('JOURNAL_AND_WINNER' === $kind) {
                return ['journal', 'winner'] === array_keys($evidence);
            }
            $receipt = $evidence['receipt'] ?? null;
            if (!is_array($receipt) || ['journal', 'winner', 'receipt'] !== array_keys($evidence)) {
                return false;
            }
            $this->transactionValidator->assertReceipt($receipt, $winner, $journal);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sealed(array $record, array $fields, string $schema, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record) || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null) || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function directiveFor(string $classification): ?string
    {
        return [
            'ABSENT' => 'NO_ACTION',
            'PREPARED' => 'REFUSE_AUTOMATIC_REPAIR',
            'COMMITTING' => 'REFUSE_PARTIAL_STATE',
            'COMMITTED' => 'ACCEPT_EXACT_READ_ONLY',
            'INCOMPLETE' => 'REFUSE_INCOMPLETE_EVIDENCE',
        ][$classification] ?? null;
    }

    private function caseExpectationMatches(string $kind, array $expected): bool
    {
        $requiredComparison = [
            'EXACT_REPLAY' => 'EXACT_REPLAY',
            'CHANGED_EVIDENCE' => 'CHANGED_EVIDENCE_REFUSED',
            'SAME_ROOT_CONTENTION' => 'SAME_ROOT_CONTENTION_REFUSED',
        ][$kind] ?? 'NOT_APPLICABLE';

        return $requiredComparison === $expected['expected_comparison'];
    }

    private function reference(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }

    private function path(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z][a-z0-9_.]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function nullableError(mixed $value): bool
    {
        return null === $value || $this->code($value);
    }

    private function code(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[A-Z][A-Z0-9_]{2,120}$/', $value);
    }
}
