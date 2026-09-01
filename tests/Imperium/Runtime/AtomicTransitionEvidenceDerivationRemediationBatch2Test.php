<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as AdversarialCase;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivationContractValidator as ContractValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivedCaseResultContract as DerivedResult;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDeterministicCaseExecutor as Executor;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceExpectedResultContract as ExpectedResult;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceFixtureContract as Fixture;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceMutationContract as Mutation;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationBatch2Test extends TestCase
{
    public function testEmptyEvidenceDerivesOnlyAbsentNoAction(): void
    {
        [$fixture, $mutation, $expected, $case] = $this->chain();
        $result = $this->executor()->execute(
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $this->plan(),
        );

        self::assertSame(DerivedResult::REQUIRED_FIELDS, array_keys($result));
        self::assertSame('ABSENT', $result['observed_classification']);
        self::assertSame('NO_ACTION', $result['observed_directive']);
        self::assertSame('NOT_APPLICABLE', $result['observed_comparison']);
        self::assertSame(['ABSENT_NO_ACTION_ONLY'], $result['derived_finding_codes']);
        self::assertTrue($result['expectation_matched']);
        self::assertTrue($result['case_executed']);
        self::assertTrue($result['finding_derived']);
        self::assertTrue($result['read_only']);
        foreach (array_slice($result, 17, 10) as $action) {
            self::assertFalse($action);
        }
    }

    public function testWrongExpectedFindingIsObservedRatherThanTrusted(): void
    {
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected(['UNSUPPORTED_EXPECTATION']);
        $case = $this->case($fixture, $mutation, $expected);

        $result = $this->executor()->execute(
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $this->plan(),
        );

        self::assertSame(['ABSENT_NO_ACTION_ONLY'], $result['derived_finding_codes']);
        self::assertFalse($result['expectation_matched']);
    }

    public function testMutationMaterialMustMatchTheSealedDigest(): void
    {
        $fixture = $this->fixture();
        $mutation = $this->seal([
            'schema' => Mutation::SCHEMA,
            'mutation_id' => 'atomic-transition-mutation.replace.1',
            'mutation_kind' => 'REPLACE_VALUE',
            'target_path' => 'evidence.journal',
            'replacement_digest' => hash('sha256', CanonicalJson::encode('declared')),
            'expected_validator_error' => null,
            'mutation_applied' => false,
            'status' => Mutation::STATUS,
            'sealed' => true,
        ]);
        $expected = $this->expected();
        $case = $this->case($fixture, $mutation, $expected);

        $this->expectExceptionMessage('PBL972_MUTATION_REPLACEMENT_DIGEST_MISMATCH');
        $this->executor()->execute(
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
            $this->plan(),
            'undeclared',
        );
    }

    public function testExecutorAcceptsNoProofBooleanAndCreatesNoAggregateReceipt(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
            .'AtomicTransitionEvidenceDeterministicCaseExecutor.php',
        );
        foreach (['$proofs', 'REQUIRED_PROOFS', 'AdversarialAuditService', 'ImmutableRecordStore', 'MutableStateStore', 'AuthorityConsumptionStore'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $doc = $this->document('docs/atomic-transition-evidence-derivation-remediation-batch-2-deterministic-execution.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-batch-2-complete.md');
        foreach (['BATCH_2_DETERMINISTIC_CASE_EXECUTION_AND_FINDING_DERIVATION_COMPLETE', 'derives classification, directive, comparison, validator error and finding codes from observed behavior', 'No proof-boolean parameter or proof-boolean input exists.', 'Empty evidence derives only `ABSENT`, `NO_ACTION` and `ABSENT_NO_ACTION_ONLY`.', 'not an aggregate audit receipt', 'explicit read-only declarations'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['Only Atomic Transition Evidence Derivation Remediation Batch 3 evidence-bound read-only aggregate audit receipt, typed action-capability manifest and value-aware secret-exclusion proof may next be considered.', 'may not remove the campaign qualification', 'may not perform terminal evidence-chain recomputation', 'may not persist a journal', 'may not acquire a live lock', 'may not issue or consume authority', 'may not handle or resolve a live credential or capability', 'may not invoke a provider', 'may not perform external I/O', 'may not open Iron Gate or Lazaretto', 'Estimated remediation countdown after Batch 2: three batches'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function executor(): Executor
    {
        $transaction = new TransactionValidator();
        $classifier = new Classifier($transaction);

        return new Executor(
            new ContractValidator($transaction),
            new Reconstructor(new PlanValidator(), $classifier),
            $classifier,
        );
    }

    private function chain(): array
    {
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected();

        return [$fixture, $mutation, $expected, $this->case($fixture, $mutation, $expected)];
    }

    private function fixture(): array
    {
        return $this->seal(['schema' => Fixture::SCHEMA, 'fixture_id' => 'atomic-transition-fixture.empty.1', 'instance_id' => 'instance.1', 'replay_contention_root' => 'binding-reconciliation-root.1', 'fixture_kind' => 'EMPTY', 'evidence' => [], 'source_contracts' => [Journal::SCHEMA, Winner::SCHEMA, Receipt::SCHEMA], 'immutable' => true, 'status' => Fixture::STATUS, 'sealed' => true]);
    }

    private function mutation(): array
    {
        return $this->seal(['schema' => Mutation::SCHEMA, 'mutation_id' => 'atomic-transition-mutation.none.1', 'mutation_kind' => 'NONE', 'target_path' => null, 'replacement_digest' => null, 'expected_validator_error' => null, 'mutation_applied' => false, 'status' => Mutation::STATUS, 'sealed' => true]);
    }

    private function expected(array $findings = ['ABSENT_NO_ACTION_ONLY']): array
    {
        return $this->seal(['schema' => ExpectedResult::SCHEMA, 'expected_result_id' => 'atomic-transition-expected.absent.1', 'expected_classification' => 'ABSENT', 'expected_directive' => 'NO_ACTION', 'expected_comparison' => 'NOT_APPLICABLE', 'expected_validator_error' => null, 'expected_finding_codes' => $findings, 'result_derived' => false, 'status' => ExpectedResult::STATUS, 'sealed' => true]);
    }

    private function case(array $fixture, array $mutation, array $expected): array
    {
        return $this->seal(['schema' => AdversarialCase::SCHEMA, 'case_id' => 'atomic-transition-case.absent.1', 'case_kind' => 'INTERRUPTION', 'replay_contention_root' => 'binding-reconciliation-root.1', 'primary_fixture' => $this->reference($fixture, 'fixture_id'), 'comparison_fixture' => null, 'mutation' => $this->reference($mutation, 'mutation_id'), 'expected_result' => $this->reference($expected, 'expected_result_id'), 'case_executed' => false, 'finding_derived' => false, 'status' => AdversarialCase::STATUS, 'sealed' => true]);
    }

    private function plan(): array
    {
        return $this->seal(['schema' => Plan::SCHEMA, 'recovery_plan_id' => 'atomic-transition-recovery-plan.1', 'instance_id' => 'instance.1', 'replay_contention_root' => 'binding-reconciliation-root.1', 'classification_directives' => Plan::DIRECTIVES, 'automatic_repair_permitted' => false, 'state_write_permitted' => false, 'authority_action_permitted' => false, 'plan_applied' => false, 'continuing_authority' => false, 'status' => Plan::STATUS, 'sealed' => true]);
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
