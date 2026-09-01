<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as CaseContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivationContractValidator as CaseValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDeterministicCaseExecutor as CaseExecutor;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceExpectedResultContract as Expected;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceFixtureContract as Fixture;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceMutationContract as Mutation;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceOriginContract as Origin;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContract as Provenance;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContractValidator as ProvenanceValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionProvenanceBoundCaseResultContract as Result;
use App\Imperium\Runtime\Imperator\AtomicTransitionTrustedCaseExecutionCorridor as Corridor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch2Test extends TestCase
{
    public function testInternalExecutionProducesExactProvenanceBoundResult(): void
    {
        [$origin, $provenance, $case, $fixture, $mutation, $expected, $plan] = $this->chain();
        $result = $this->corridor()->executeCase(
            $origin,
            $provenance,
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $plan,
        );

        self::assertSame(Result::REQUIRED_FIELDS, array_keys($result));
        self::assertSame($this->reference($origin, 'evidence_origin_id'), $result['evidence_origin_reference']);
        self::assertSame($this->reference($provenance, 'execution_provenance_id'), $result['execution_provenance_reference']);
        self::assertSame($origin['source_commit'], $result['source_commit']);
        self::assertSame($origin['build_artifact_digest'], $result['build_artifact_digest']);
        self::assertSame($origin['executor_implementation_digest'], $result['executor_implementation_digest']);
        self::assertSame($origin['case_set_root'], $result['case_set_root']);
        self::assertSame('ABSENT', $result['observed_classification']);
        self::assertSame('NO_ACTION', $result['observed_directive']);
        self::assertSame(['ABSENT_NO_ACTION_ONLY'], $result['derived_finding_codes']);
        self::assertTrue($result['expectation_matched']);
        self::assertTrue($result['case_executed']);
        self::assertTrue($result['finding_derived']);
        self::assertFalse($result['caller_result_accepted']);
    }

    public function testCorridorAcceptsNoCallerResultOrProofBooleanParameter(): void
    {
        $names = array_map(
            static fn (\ReflectionParameter $parameter): string => $parameter->getName(),
            (new \ReflectionMethod(Corridor::class, 'executeCase'))->getParameters(),
        );

        self::assertSame([
            'origin', 'provenance', 'case', 'primaryFixture',
            'comparisonFixture', 'mutation', 'expected', 'plan', 'replacement',
        ], $names);
        foreach ([
            'result', 'caseExecuted', 'findingDerived', 'expectationMatched',
            'proof', 'proofs',
        ] as $forbidden) {
            self::assertNotContains($forbidden, $names);
        }
    }

    public function testOriginPlanOrRootSubstitutionRefusesBeforeExecution(): void
    {
        [$origin, $provenance, $case, $fixture, $mutation, $expected, $plan] = $this->chain();
        $case['replay_contention_root'] = 'substituted-root.1';
        $case = $this->seal($case);

        $this->expectExceptionMessage('PBL996_TRUSTED_EXECUTION_INPUT_ORIGIN_MISMATCH');
        $this->corridor()->executeCase(
            $origin,
            $provenance,
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $plan,
        );
    }

    public function testObservedExpectationMismatchCannotProduceAcceptedResult(): void
    {
        [$origin, $provenance, $case, $fixture, $mutation, $expected, $plan] = $this->chain(
            ['UNSUPPORTED_EXPECTATION'],
        );

        $this->expectExceptionMessage('PBL998_TRUSTED_EXECUTION_EXPECTATION_MISMATCH');
        $this->corridor()->executeCase(
            $origin,
            $provenance,
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $plan,
        );
    }

    public function testCorridorRemainsPureAndBatchBoundaryIsExplicit(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
            .'AtomicTransitionTrustedCaseExecutionCorridor.php',
        );
        foreach ([
            'ImmutableRecordStore', 'MutableStateStore',
            'AuthorityConsumptionStore', 'ProviderInvocation',
            'public function persist', 'public function write',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-2-trusted-execution.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-2-complete.md',
        );
        foreach ([
            'BATCH_2_TRUSTED_INTERNAL_CASE_EXECUTION_AND_PROVENANCE_BOUND_RESULTS_COMPLETE',
            'accepts no caller-supplied case result',
            'no result, `case_executed`, `finding_derived` or `expectation_matched` input parameter',
            '`caller_result_accepted=false`',
            'not an operational receipt, aggregate disposition, complete case-set proof or campaign-closure input',
            'does not yet derive the executor\'s actual recursive dependency-capability graph',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 3 actual recursive dependency-capability graph derivation may next be considered.',
            'refuse unknown, substituted, mutable or effect-capable dependencies',
            'may not run the disposable real mission, handle a live credential or capability',
            'invoke a provider, perform external I/O, mutate runtime state',
            'remove the closure qualification',
            '`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`',
            'Estimated campaign countdown after Batch 2: five batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function corridor(): Corridor
    {
        $transaction = new TransactionValidator();
        $classifier = new Classifier($transaction);

        return new Corridor(
            new ProvenanceValidator(),
            new CaseExecutor(
                new CaseValidator($transaction),
                new Reconstructor(new PlanValidator(), $classifier),
                $classifier,
            ),
        );
    }

    private function chain(array $findings = ['ABSENT_NO_ACTION_ONLY']): array
    {
        $plan = $this->plan();
        $origin = $this->origin($plan);
        $provenance = $this->provenance($origin);
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected($findings);
        $case = $this->case($fixture, $mutation, $expected);

        return [$origin, $provenance, $case, $fixture, $mutation, $expected, $plan];
    }

    private function origin(array $plan): array
    {
        return $this->seal([
            'schema' => Origin::SCHEMA, 'evidence_origin_id' => 'atomic-evidence-origin.1',
            'experiment_id' => 'atomic-experiment.1', 'disposable_mission_id' => 'disposable-mission.1',
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'disposable_mission_authorization' => $this->stub('authorization.1', 'authorization/v1', '1'),
            'authorized_case_profile' => 'atomic-transition-required-cases.v1',
            'source_repository' => 'gatomontes/imperium-symfony', 'source_commit' => str_repeat('a', 40),
            'source_tree_digest' => str_repeat('b', 64), 'dirty_tree_refused' => true,
            'build_id' => 'atomic-build.1', 'build_artifact_digest' => str_repeat('c', 64),
            'dependency_lock_digest' => str_repeat('d', 64), 'runtime_version' => 'php-8.4.14',
            'build_command_identity' => 'composer-install-no-dev.v1',
            'executor_principal' => $this->stub('executor-principal.1', 'executor-principal/v1', '2'),
            'executor_implementation_digest' => str_repeat('e', 64),
            'executor_entry_point' => 'atomic-transition-trusted-executor.v1',
            'execution_environment_class' => 'disposable-local-one-root.v1',
            'mission_dossier' => $this->stub('mission-dossier.1', 'mission-dossier/v1', '3'),
            'fixture_set_root' => str_repeat('f', 64), 'recovery_plan' => $this->reference($plan, 'recovery_plan_id'),
            'mutation_set_root' => str_repeat('5', 64), 'expected_result_set_root' => str_repeat('6', 64),
            'case_set_root' => str_repeat('7', 64), 'authoritative_evidence_root' => 'disposable-root.1',
            'fixture_custodian' => 'trusted-fixture-custodian.v1', 'origin_producer' => 'future-origin-producer.v1',
            'issued_at' => '2026-09-01T12:00:00+00:00', 'not_before' => '2026-09-01T12:00:00+00:00',
            'expires_at' => '2026-09-01T12:15:00+00:00', 'prior_origin_disposition' => 'ABSENT',
            'limitations' => Origin::LIMITATIONS, 'sanitized_evidence_package_id' => 'sanitized-evidence-package.1',
            'sanitized_evidence_package_digest' => str_repeat('8', 64), 'raw_private_evidence_excluded' => true,
            'single_use' => true, 'authority_empty' => true, 'execution_performed' => false,
            'operational_receipt_created' => false, 'continuing_authority' => false,
            'status' => Origin::STATUS, 'sealed' => true,
        ]);
    }

    private function provenance(array $origin): array
    {
        $record = [
            'schema' => Provenance::SCHEMA,
            'execution_provenance_id' => 'atomic-execution-provenance.1',
            'evidence_origin' => $this->reference($origin, 'evidence_origin_id'),
        ];
        foreach ([
            'experiment_id', 'disposable_mission_id', 'replay_contention_root', 'source_commit',
            'source_tree_digest', 'build_id', 'build_artifact_digest', 'dependency_lock_digest',
            'runtime_version', 'executor_principal', 'executor_implementation_digest',
            'executor_entry_point', 'execution_environment_class', 'mission_dossier',
            'fixture_set_root', 'recovery_plan', 'mutation_set_root', 'expected_result_set_root',
            'case_set_root', 'authoritative_evidence_root', 'fixture_custodian', 'origin_producer',
        ] as $field) {
            $record[$field] = $origin[$field];
        }
        $record['authorized_not_before'] = $origin['not_before'];
        $record['authorized_expires_at'] = $origin['expires_at'];
        $record['limitations'] = $origin['limitations'];
        $record['sanitized_evidence_package_id'] = $origin['sanitized_evidence_package_id'];
        $record['sanitized_evidence_package_digest'] = $origin['sanitized_evidence_package_digest'];

        return $this->seal($record + [
            'trusted_executor_implemented' => false, 'execution_performed' => false,
            'caller_result_accepted' => false, 'result_produced' => false,
            'dependency_graph_derived' => false, 'complete_chain_exclusion_proved' => false,
            'operational_receipt_created' => false, 'authority_empty' => true,
            'continuing_authority' => false, 'status' => Provenance::STATUS, 'sealed' => true,
        ]);
    }

    private function fixture(): array
    {
        return $this->seal(['schema' => Fixture::SCHEMA, 'fixture_id' => 'atomic-transition-fixture.empty.1', 'instance_id' => 'instance.1', 'replay_contention_root' => 'binding-reconciliation-root.1', 'fixture_kind' => 'EMPTY', 'evidence' => [], 'source_contracts' => [Journal::SCHEMA, Winner::SCHEMA, Receipt::SCHEMA], 'immutable' => true, 'status' => Fixture::STATUS, 'sealed' => true]);
    }

    private function mutation(): array
    {
        return $this->seal(['schema' => Mutation::SCHEMA, 'mutation_id' => 'atomic-transition-mutation.none.1', 'mutation_kind' => 'NONE', 'target_path' => null, 'replacement_digest' => null, 'expected_validator_error' => null, 'mutation_applied' => false, 'status' => Mutation::STATUS, 'sealed' => true]);
    }

    private function expected(array $findings): array
    {
        return $this->seal(['schema' => Expected::SCHEMA, 'expected_result_id' => 'atomic-transition-expected.absent.1', 'expected_classification' => 'ABSENT', 'expected_directive' => 'NO_ACTION', 'expected_comparison' => 'NOT_APPLICABLE', 'expected_validator_error' => null, 'expected_finding_codes' => $findings, 'result_derived' => false, 'status' => Expected::STATUS, 'sealed' => true]);
    }

    private function case(array $fixture, array $mutation, array $expected): array
    {
        return $this->seal(['schema' => CaseContract::SCHEMA, 'case_id' => 'atomic-transition-case.absent.1', 'case_kind' => 'INTERRUPTION', 'replay_contention_root' => 'binding-reconciliation-root.1', 'primary_fixture' => $this->reference($fixture, 'fixture_id'), 'comparison_fixture' => null, 'mutation' => $this->reference($mutation, 'mutation_id'), 'expected_result' => $this->reference($expected, 'expected_result_id'), 'case_executed' => false, 'finding_derived' => false, 'status' => CaseContract::STATUS, 'sealed' => true]);
    }

    private function plan(): array
    {
        return $this->seal(['schema' => Plan::SCHEMA, 'recovery_plan_id' => 'atomic-transition-recovery-plan.1', 'instance_id' => 'instance.1', 'replay_contention_root' => 'binding-reconciliation-root.1', 'classification_directives' => Plan::DIRECTIVES, 'automatic_repair_permitted' => false, 'state_write_permitted' => false, 'authority_action_permitted' => false, 'plan_applied' => false, 'continuing_authority' => false, 'status' => Plan::STATUS, 'sealed' => true]);
    }

    private function stub(string $id, string $schema, string $digit): array
    {
        return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema];
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
