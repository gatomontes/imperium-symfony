<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Pure internal case execution that accepts no caller-supplied result. */
final readonly class AtomicTransitionTrustedCaseExecutionCorridor
{
    public function __construct(
        private AtomicTransitionExecutionProvenanceContractValidator $provenanceValidator,
        private AtomicTransitionEvidenceDeterministicCaseExecutor $caseExecutor,
    ) {
    }

    public function executeCase(
        array $origin,
        array $provenance,
        array $case,
        array $primaryFixture,
        ?array $comparisonFixture,
        array $mutation,
        array $expected,
        array $plan,
        mixed $replacement = null,
    ): array {
        $this->provenanceValidator->assertExecutionProvenance($provenance, $origin);
        if (($case['replay_contention_root'] ?? null) !== $origin['replay_contention_root']
            || ($plan['replay_contention_root'] ?? null) !== $origin['replay_contention_root']
            || $this->reference($plan, 'recovery_plan_id') !== $origin['recovery_plan']) {
            throw new \RuntimeException('PBL996_TRUSTED_EXECUTION_INPUT_ORIGIN_MISMATCH');
        }

        $derived = $this->caseExecutor->execute(
            $case,
            $primaryFixture,
            $comparisonFixture,
            $mutation,
            $expected,
            $plan,
            $replacement,
        );
        $this->assertInternalResult($derived);
        if (true !== $derived['expectation_matched']) {
            throw new \RuntimeException('PBL998_TRUSTED_EXECUTION_EXPECTATION_MISMATCH');
        }

        $resultId = 'atomic-transition-provenance-result.'.substr(hash(
            'sha256',
            CanonicalJson::encode([
                $provenance['record_digest'],
                $case['record_digest'],
                $derived['record_digest'],
            ]),
        ), 0, 32);

        return $this->seal([
            'schema' => AtomicTransitionProvenanceBoundCaseResultContract::SCHEMA,
            'result_id' => $resultId,
            'execution_provenance_reference' => $this->reference(
                $provenance,
                'execution_provenance_id',
            ),
            'evidence_origin_reference' => $this->reference(
                $origin,
                'evidence_origin_id',
            ),
            'experiment_id' => $origin['experiment_id'],
            'disposable_mission_id' => $origin['disposable_mission_id'],
            'replay_contention_root' => $origin['replay_contention_root'],
            'source_commit' => $origin['source_commit'],
            'source_tree_digest' => $origin['source_tree_digest'],
            'build_id' => $origin['build_id'],
            'build_artifact_digest' => $origin['build_artifact_digest'],
            'dependency_lock_digest' => $origin['dependency_lock_digest'],
            'executor_principal' => $origin['executor_principal'],
            'executor_implementation_digest' => $origin['executor_implementation_digest'],
            'executor_entry_point' => $origin['executor_entry_point'],
            'case_set_root' => $origin['case_set_root'],
            'case_reference' => $derived['case_reference'],
            'plan_reference' => $derived['plan_reference'],
            'primary_fixture_reference' => $derived['primary_fixture_reference'],
            'comparison_fixture_reference' => $derived['comparison_fixture_reference'],
            'mutation_reference' => $derived['mutation_reference'],
            'expected_result_reference' => $derived['expected_result_reference'],
            'derived_result_digest' => $derived['record_digest'],
            'replacement_digest_observed' => $derived['replacement_digest_observed'],
            'observed_classification' => $derived['observed_classification'],
            'observed_directive' => $derived['observed_directive'],
            'observed_comparison' => $derived['observed_comparison'],
            'observed_validator_error' => $derived['observed_validator_error'],
            'derived_finding_codes' => $derived['derived_finding_codes'],
            'expectation_matched' => true,
            'case_executed' => true,
            'finding_derived' => true,
            'caller_result_accepted' => false,
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
            'status' => AtomicTransitionProvenanceBoundCaseResultContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function assertInternalResult(array $result): void
    {
        $plain = $result;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if (AtomicTransitionEvidenceDerivedCaseResultContract::REQUIRED_FIELDS
                !== array_keys($result)
            || AtomicTransitionEvidenceDerivedCaseResultContract::SCHEMA
                !== ($result['schema'] ?? null)
            || true !== ($result['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || true !== ($result['case_executed'] ?? null)
            || true !== ($result['finding_derived'] ?? null)
            || true !== ($result['read_only'] ?? null)
            || AtomicTransitionEvidenceDerivedCaseResultContract::STATUS
                !== ($result['status'] ?? null)) {
            throw new \RuntimeException('PBL997_TRUSTED_EXECUTION_RESULT_INVALID');
        }
        foreach ([
            'journal_persisted', 'live_lock_acquired',
            'state_written_or_repaired', 'authority_issued_or_consumed',
            'execution_admitted', 'successor_adopted',
            'binding_state_changed', 'durable_winner_or_receipt_created',
            'provider_effect_started', 'continuing_authority',
        ] as $action) {
            if (false !== ($result[$action] ?? null)) {
                throw new \RuntimeException('PBL997_TRUSTED_EXECUTION_RESULT_INVALID');
            }
        }
    }

    private function reference(array $record, string $id): array
    {
        return [
            'id' => $record[$id],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
