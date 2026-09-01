<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as AdversarialCase;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivationContractValidator as Validator;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceExpectedResultContract as ExpectedResult;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceFixtureContract as Fixture;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceMutationContract as Mutation;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationBatch1Test extends TestCase
{
    public function testTypedEmptyInterruptionCaseValidatesWithoutExecution(): void
    {
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected();
        $case = $this->case($fixture, $mutation, $expected);
        $validator = new Validator(new TransactionValidator());

        $validator->assertFixture($fixture);
        $validator->assertMutation($mutation);
        $validator->assertExpectedResult($expected);
        $validator->assertCase($case, $fixture, null, $mutation, $expected);

        self::assertFalse($case['case_executed']);
        self::assertFalse($case['finding_derived']);
        self::assertFalse($mutation['mutation_applied']);
        self::assertFalse($expected['result_derived']);
    }

    public function testTamperedFixtureAndReferenceFailClosed(): void
    {
        $fixture = $this->fixture();
        $fixture['replay_contention_root'] = 'binding-reconciliation-root.2';
        $this->expectExceptionMessage('PBL960_EVIDENCE_FIXTURE_INVALID');
        (new Validator(new TransactionValidator()))->assertFixture($fixture);
    }

    public function testMutationCannotCarryReplacementMaterial(): void
    {
        $mutation = $this->mutation();
        $mutation['target_path'] = 'evidence.journal';
        $mutation = $this->seal($mutation);

        $this->expectExceptionMessage('PBL961_EVIDENCE_MUTATION_INVALID');
        (new Validator(new TransactionValidator()))->assertMutation($mutation);
    }

    public function testIncompatibleExpectedDirectiveFailsClosed(): void
    {
        $expected = $this->expected();
        $expected['expected_directive'] = 'ACCEPT_EXACT_READ_ONLY';
        $expected = $this->seal($expected);

        $this->expectExceptionMessage('PBL962_EXPECTED_RESULT_INVALID');
        (new Validator(new TransactionValidator()))->assertExpectedResult($expected);
    }

    public function testTamperedCaseReferenceFailsClosed(): void
    {
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected();
        $case = $this->case($fixture, $mutation, $expected);
        $case['primary_fixture']['digest'] = str_repeat('9', 64);
        $case = $this->seal($case);

        $this->expectExceptionMessage('PBL963_ADVERSARIAL_CASE_INVALID');
        (new Validator(new TransactionValidator()))->assertCase(
            $case,
            $fixture,
            null,
            $mutation,
            $expected,
        );
    }

    public function testContractsArePureAndDocumentationAuthorizesExecutionNextOnly(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/';
        $source = '';
        foreach ([
            'AtomicTransitionEvidenceFixtureContract.php',
            'AtomicTransitionEvidenceMutationContract.php',
            'AtomicTransitionEvidenceExpectedResultContract.php',
            'AtomicTransitionEvidenceAdversarialCaseContract.php',
            'AtomicTransitionEvidenceDerivationContractValidator.php',
        ] as $file) {
            $source .= (string) file_get_contents($root.$file);
        }
        foreach (['Persistence\\AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore', 'AuthorityConsumptionStore', 'public function execute', 'public function derive', 'public function persist', 'public function write'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }

        $doc = $this->document('docs/atomic-transition-evidence-derivation-remediation-batch-1-typed-contracts.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-batch-1-complete.md');
        foreach (['BATCH_1_TYPED_CASE_MUTATION_EXPECTED_RESULT_AND_IMMUTABLE_FIXTURE_CONTRACTS_COMPLETE', 'Four separately versioned, sealed contract families', 'exact sealed references', 'The seal order is fixture, mutation, expected result, then case.', 'execute no case and derive no finding', 'apply no mutation', 'remove no campaign qualification'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['Only Atomic Transition Evidence Derivation Remediation Batch 2 deterministic pure case execution and finding derivation without proof booleans may next be considered.', 'may not accept a caller proof boolean', 'may not seal an aggregate audit receipt', 'may not remove the campaign qualification', 'may not persist a journal', 'may not acquire a live lock', 'may not issue or consume authority', 'may not handle or resolve a credential or capability', 'may not invoke a provider', 'may not perform external I/O', 'may not open Iron Gate or Lazaretto', 'Estimated remediation countdown after Batch 1: four batches'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        return $this->seal([
            'schema' => Fixture::SCHEMA,
            'fixture_id' => 'atomic-transition-fixture.empty.1',
            'instance_id' => 'instance.1',
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'fixture_kind' => 'EMPTY',
            'evidence' => [],
            'source_contracts' => [Journal::SCHEMA, Winner::SCHEMA, Receipt::SCHEMA],
            'immutable' => true,
            'status' => Fixture::STATUS,
            'sealed' => true,
        ]);
    }

    private function mutation(): array
    {
        return $this->seal([
            'schema' => Mutation::SCHEMA,
            'mutation_id' => 'atomic-transition-mutation.none.1',
            'mutation_kind' => 'NONE',
            'target_path' => null,
            'replacement_digest' => null,
            'expected_validator_error' => null,
            'mutation_applied' => false,
            'status' => Mutation::STATUS,
            'sealed' => true,
        ]);
    }

    private function expected(): array
    {
        return $this->seal([
            'schema' => ExpectedResult::SCHEMA,
            'expected_result_id' => 'atomic-transition-expected.absent.1',
            'expected_classification' => 'ABSENT',
            'expected_directive' => 'NO_ACTION',
            'expected_comparison' => 'NOT_APPLICABLE',
            'expected_validator_error' => null,
            'expected_finding_codes' => ['ABSENT_NO_ACTION_ONLY'],
            'result_derived' => false,
            'status' => ExpectedResult::STATUS,
            'sealed' => true,
        ]);
    }

    private function case(array $fixture, array $mutation, array $expected): array
    {
        return $this->seal([
            'schema' => AdversarialCase::SCHEMA,
            'case_id' => 'atomic-transition-case.absent.1',
            'case_kind' => 'INTERRUPTION',
            'replay_contention_root' => 'binding-reconciliation-root.1',
            'primary_fixture' => $this->reference($fixture, 'fixture_id'),
            'comparison_fixture' => null,
            'mutation' => $this->reference($mutation, 'mutation_id'),
            'expected_result' => $this->reference($expected, 'expected_result_id'),
            'case_executed' => false,
            'finding_derived' => false,
            'status' => AdversarialCase::STATUS,
            'sealed' => true,
        ]);
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
