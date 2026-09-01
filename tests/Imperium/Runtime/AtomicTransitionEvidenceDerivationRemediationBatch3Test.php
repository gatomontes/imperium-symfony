<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceActionCapabilityManifestContract as Manifest;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as AdversarialCase;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAggregateAuditBuilder as Builder;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAggregateAuditReceiptContract as Receipt;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivedCaseResultContract as Result;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceSecretExclusionProofContract as SecretProof;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceValueAwareSecretExclusionService as SecretService;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationBatch3Test extends TestCase
{
    public function testCompleteOrderedCaseSetBindsAggregateReceipt(): void
    {
        [$cases, $results] = $this->completeSet();
        $builder = new Builder();
        $manifest = $builder->manifest('atomic-transition-capability-manifest.1');
        $proof = (new SecretService())->prove('atomic-transition-secret-proof.1', $results);
        $receipt = $builder->build('atomic-transition-aggregate-audit.1', 'binding-reconciliation-root.1', $cases, $results, $manifest, $proof);

        self::assertSame(Receipt::REQUIRED_FIELDS, array_keys($receipt));
        self::assertSame(AdversarialCase::KINDS, $receipt['ordered_case_kinds']);
        self::assertSame(hash('sha256', CanonicalJson::encode($receipt['ordered_case_result_references'])), $receipt['ordered_result_set_digest']);
        self::assertTrue($receipt['all_cases_matched']);
        self::assertTrue($receipt['read_only']);
        self::assertFalse($receipt['qualification_removed']);
        self::assertFalse($receipt['terminal_recomputation_performed']);
        self::assertFalse($receipt['durable_receipt_created']);
        self::assertFalse($receipt['continuing_authority']);
    }

    public function testManifestIsTypedAndScopedToPureEvaluatorClosure(): void
    {
        $manifest = (new Builder())->manifest('atomic-transition-capability-manifest.1');

        self::assertSame(Manifest::REQUIRED_FIELDS, array_keys($manifest));
        self::assertSame(Builder::EVALUATORS, $manifest['evaluator_classes']);
        self::assertSame(array_fill_keys(Manifest::CAPABILITIES, false), $manifest['capabilities']);
        self::assertSame(Manifest::STATUS, $manifest['status']);
    }

    public function testSecretProofRejectsKeysValuesEncodedValuesAndProcessLocalCapabilities(): void
    {
        [, $results] = $this->completeSet();
        $proof = (new SecretService())->prove('atomic-transition-secret-proof.1', $results);

        self::assertSame(SecretProof::REQUIRED_FIELDS, array_keys($proof));
        self::assertSame(SecretProof::REQUIRED_VECTOR_KINDS, $proof['attack_vector_kinds']);
        self::assertCount(4, $proof['attack_vector_digests']);
        self::assertSame(array_fill(0, 4, 'PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED'), $proof['derived_refusal_codes']);
        self::assertTrue($proof['all_records_clean']);
        self::assertTrue($proof['all_attacks_refused']);
        self::assertTrue($proof['value_aware']);
    }

    public function testAggregateRefusesMissingOrMismatchedEvidence(): void
    {
        [$cases, $results] = $this->completeSet();
        array_pop($cases);
        $builder = new Builder();
        $manifest = $builder->manifest('atomic-transition-capability-manifest.1');
        $proof = (new SecretService())->prove('atomic-transition-secret-proof.1', $results);

        $this->expectExceptionMessage('PBL983_AGGREGATE_CASE_RESULT_COUNT_MISMATCH');
        $builder->build('atomic-transition-aggregate-audit.1', 'binding-reconciliation-root.1', $cases, $results, $manifest, $proof);
    }

    public function testBatchBoundaryPreservesQualificationAndProhibitions(): void
    {
        $doc = $this->document('docs/atomic-transition-evidence-derivation-remediation-batch-3-aggregate-audit.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-batch-3-complete.md');
        foreach (['BATCH_3_EVIDENCE_BOUND_AGGREGATE_AUDIT_COMPLETE', 'pure evaluator dependency closure', 'recursive and value-aware', 'No caller-supplied proof boolean is accepted.', 'CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['Only Atomic Transition Evidence Derivation Remediation Batch 4 terminal evidence-chain recomputation', 'may not persist a journal', 'may not acquire a live lock', 'may not issue or consume authority', 'may not handle or resolve a live credential or capability', 'may not invoke a provider', 'may not perform external I/O', 'may not open Iron Gate or Lazaretto', 'Estimated remediation countdown after Batch 3: two batches'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function completeSet(): array
    {
        $cases = [];
        $results = [];
        foreach (AdversarialCase::KINDS as $index => $kind) {
            $case = $this->seal([
                'schema' => AdversarialCase::SCHEMA,
                'case_id' => 'atomic-transition-case.'.strtolower($kind).'.1',
                'case_kind' => $kind,
                'replay_contention_root' => 'binding-reconciliation-root.1',
                'primary_fixture' => ['id' => 'fixture.'.$index, 'digest' => str_repeat(dechex($index), 64), 'schema' => 'fixture/v1'],
                'comparison_fixture' => null,
                'mutation' => ['id' => 'mutation.'.$index, 'digest' => str_repeat('a', 64), 'schema' => 'mutation/v1'],
                'expected_result' => ['id' => 'expected.'.$index, 'digest' => str_repeat('b', 64), 'schema' => 'expected/v1'],
                'case_executed' => false,
                'finding_derived' => false,
                'status' => AdversarialCase::STATUS,
                'sealed' => true,
            ]);
            $cases[] = $case;
            $results[] = $this->result($case, $index);
        }

        return [$cases, $results];
    }

    private function result(array $case, int $index): array
    {
        return $this->seal([
            'schema' => Result::SCHEMA,
            'case_reference' => $this->reference($case, 'case_id'),
            'plan_reference' => ['id' => 'plan.1', 'digest' => str_repeat('c', 64), 'schema' => 'plan/v1'],
            'primary_fixture_reference' => ['id' => 'fixture.'.$index, 'digest' => str_repeat(dechex($index), 64), 'schema' => 'fixture/v1'],
            'comparison_fixture_reference' => null,
            'mutation_reference' => ['id' => 'mutation.'.$index, 'digest' => str_repeat('a', 64), 'schema' => 'mutation/v1'],
            'expected_result_reference' => ['id' => 'expected.'.$index, 'digest' => str_repeat('b', 64), 'schema' => 'expected/v1'],
            'replacement_digest_observed' => null,
            'observed_classification' => 'ABSENT',
            'observed_directive' => 'NO_ACTION',
            'observed_comparison' => 'NOT_APPLICABLE',
            'observed_validator_error' => null,
            'derived_finding_codes' => ['READ_ONLY_FINDING_DERIVED'],
            'expectation_matched' => true,
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
            'status' => Result::STATUS,
            'sealed' => true,
        ]);
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
