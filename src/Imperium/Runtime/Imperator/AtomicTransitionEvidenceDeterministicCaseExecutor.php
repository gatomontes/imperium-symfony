<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;

/** Pure deterministic execution of caller-supplied sealed in-memory cases. */
final readonly class AtomicTransitionEvidenceDeterministicCaseExecutor
{
    public function __construct(
        private AtomicTransitionEvidenceDerivationContractValidator $contractValidator,
        private Reconstructor $reconstructor,
        private Classifier $classifier,
    ) {
    }

    public function execute(
        array $case,
        array $primaryFixture,
        ?array $comparisonFixture,
        array $mutation,
        array $expected,
        array $plan,
        mixed $replacement = null,
    ): array {
        $this->contractValidator->assertCase(
            $case,
            $primaryFixture,
            $comparisonFixture,
            $mutation,
            $expected,
        );
        if (($plan['replay_contention_root'] ?? null)
            !== $case['replay_contention_root']) {
            throw new \RuntimeException('PBL970_CASE_PLAN_ROOT_MISMATCH');
        }

        [$evidence, $replacementDigest] = $this->mutate(
            $primaryFixture['evidence'],
            $mutation,
            $replacement,
        );
        $classification = null;
        $directive = null;
        $comparison = 'NOT_APPLICABLE';
        $error = null;
        $findings = [];

        try {
            $aggregate = $this->reconstructor->reconstruct($plan, $evidence);
            $classification = $aggregate['classification'];
            $directive = $aggregate['directive'];
            $findings[] = $this->classificationFinding($classification);
            if (null !== $comparisonFixture) {
                $comparison = $this->classifier->compare(
                    $evidence,
                    $comparisonFixture['evidence'],
                );
                $findings[] = $this->comparisonFinding($comparison);
            }
        } catch (\Throwable $caught) {
            $error = $caught->getMessage();
            $findings = ['VALIDATOR_REFUSAL_DERIVED'];
        }

        $matched = $this->matches(
            $expected,
            $classification,
            $directive,
            $comparison,
            $error,
            $findings,
        );

        return $this->seal([
            'schema' => AtomicTransitionEvidenceDerivedCaseResultContract::SCHEMA,
            'case_reference' => $this->reference($case, 'case_id'),
            'plan_reference' => $this->reference($plan, 'recovery_plan_id'),
            'primary_fixture_reference' => $this->reference($primaryFixture, 'fixture_id'),
            'comparison_fixture_reference' => null === $comparisonFixture
                ? null
                : $this->reference($comparisonFixture, 'fixture_id'),
            'mutation_reference' => $this->reference($mutation, 'mutation_id'),
            'expected_result_reference' => $this->reference($expected, 'expected_result_id'),
            'replacement_digest_observed' => $replacementDigest,
            'observed_classification' => $classification,
            'observed_directive' => $directive,
            'observed_comparison' => $comparison,
            'observed_validator_error' => $error,
            'derived_finding_codes' => $findings,
            'expectation_matched' => $matched,
            'case_executed' => true,
            'finding_derived' => true,
            'read_only' => true,
            'journal_persisted' => false,
            'live_lock_acquired' => false,
            'state_written_or_repaired' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_state_changed' => false,
            'durable_winner_or_receipt_created' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionEvidenceDerivedCaseResultContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function mutate(array $evidence, array $mutation, mixed $replacement): array
    {
        if ('NONE' === $mutation['mutation_kind']) {
            if (null !== $replacement) {
                throw new \RuntimeException('PBL971_UNDECLARED_MUTATION_MATERIAL');
            }

            return [$evidence, null];
        }

        $digest = hash('sha256', CanonicalJson::encode($replacement));
        if (!hash_equals($mutation['replacement_digest'], $digest)) {
            throw new \RuntimeException('PBL972_MUTATION_REPLACEMENT_DIGEST_MISMATCH');
        }
        $segments = explode('.', $mutation['target_path']);
        $cursor =& $evidence;
        $last = array_pop($segments);
        foreach ($segments as $segment) {
            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                throw new \RuntimeException('PBL973_MUTATION_TARGET_MISSING');
            }
            $cursor =& $cursor[$segment];
        }
        if (!array_key_exists($last, $cursor)) {
            throw new \RuntimeException('PBL973_MUTATION_TARGET_MISSING');
        }
        if ('REMOVE_PATH' === $mutation['mutation_kind']) {
            unset($cursor[$last]);
        } else {
            $cursor[$last] = $replacement;
        }
        unset($cursor);

        return [$evidence, $digest];
    }

    private function matches(
        array $expected,
        ?string $classification,
        ?string $directive,
        string $comparison,
        ?string $error,
        array $findings,
    ): bool {
        if (null !== $error) {
            return $expected['expected_validator_error'] === $error
                && $expected['expected_finding_codes'] === $findings;
        }

        return null === $expected['expected_validator_error']
            && $expected['expected_classification'] === $classification
            && $expected['expected_directive'] === $directive
            && $expected['expected_comparison'] === $comparison
            && $expected['expected_finding_codes'] === $findings;
    }

    private function classificationFinding(string $classification): string
    {
        return [
            'ABSENT' => 'ABSENT_NO_ACTION_ONLY',
            'PREPARED' => 'PREPARED_AUTOMATIC_REPAIR_REFUSED',
            'COMMITTING' => 'COMMITTING_PARTIAL_STATE_REFUSED',
            'COMMITTED' => 'COMMITTED_EXACT_READ_ONLY_ACCEPTED',
            'INCOMPLETE' => 'INCOMPLETE_EVIDENCE_REFUSED',
        ][$classification] ?? 'UNKNOWN_CLASSIFICATION_REFUSED';
    }

    private function comparisonFinding(string $comparison): string
    {
        return [
            'EXACT_REPLAY' => 'EXACT_REPLAY_DERIVED',
            'CHANGED_EVIDENCE_REFUSED' => 'CHANGED_EVIDENCE_REFUSAL_DERIVED',
            'SAME_ROOT_CONTENTION_REFUSED' => 'SAME_ROOT_CONTENTION_REFUSAL_DERIVED',
            'DISTINCT_ROOTS' => 'DISTINCT_ROOTS_DERIVED',
            'INCOMPLETE_COMPARISON_REFUSED' => 'INCOMPLETE_COMPARISON_REFUSAL_DERIVED',
        ][$comparison] ?? 'UNKNOWN_COMPARISON_REFUSED';
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash(
            'sha256',
            CanonicalJson::encode($record),
        );

        return $record;
    }
}
