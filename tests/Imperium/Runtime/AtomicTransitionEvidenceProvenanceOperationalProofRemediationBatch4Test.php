<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionCompleteChainExclusionProofContract as Proof;
use App\Imperium\Runtime\Imperator\AtomicTransitionCompleteChainExclusionService as Service;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as CaseContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAggregateAuditReceiptContract as Aggregate;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceCorrectedClosureContract as Closure;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceExpectedResultContract as Expectation;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceFixtureContract as Fixture;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceMutationContract as Mutation;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceOriginContract as Origin;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContract as Provenance;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutorDependencyCapabilityGraphContract as Graph;
use App\Imperium\Runtime\Imperator\AtomicTransitionProvenanceBoundCaseResultContract as Result;
use App\Imperium\Runtime\Imperator\AtomicTransitionSanitizedExceptionEvidenceContract as ExceptionEvidence;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch4Test extends TestCase
{
    public function testCompleteTypedChainProducesDerivedReadOnlyProof(): void
    {
        $proof = (new Service())->prove('complete-chain-exclusion.1', $this->chain());

        self::assertSame(Proof::REQUIRED_FIELDS, array_keys($proof));
        self::assertSame(Proof::SECTIONS, $proof['scanned_sections']);
        self::assertSame(12, $proof['scanned_artifact_count']);
        self::assertCount(12, $proof['scanned_artifact_digests']);
        self::assertSame(Proof::NORMALIZATIONS, $proof['normalizations_applied']);
        self::assertSame(Proof::ATTACK_VECTOR_KINDS, $proof['attack_vector_kinds']);
        self::assertSame(
            array_fill(0, count(Proof::ATTACK_VECTOR_KINDS), 'PBL1013_SECRET_OR_PROCESS_LOCAL_CAPABILITY_REFUSED'),
            $proof['derived_refusal_codes'],
        );
        foreach ([
            'all_sections_complete', 'all_artifacts_structurally_allowed',
            'all_artifacts_clean', 'all_attacks_refused', 'value_aware',
            'encoding_aware', 'split_value_aware', 'exception_aware', 'read_only',
        ] as $derived) {
            self::assertTrue($proof[$derived]);
        }
        foreach ([
            'runtime_state_written', 'authority_issued_or_consumed',
            'execution_admitted', 'provider_effect_started', 'continuing_authority',
        ] as $nonAction) {
            self::assertFalse($proof[$nonAction]);
        }
    }

    public function testAlternativeNestedSplitAndProcessLocalValuesFailClosed(): void
    {
        $service = new Service();
        foreach ([
            ['payload' => 'UW1WaGNtVnlJR1p2Y21KcFpHUmxiaTF6WldOeVpYUT0='],
            ['payload' => '42656172657220666f7262696464656e2d736563726574'],
            ['payload' => 'Bearer%20forbidden-secret'],
            ['payload' => ['Bearer ', 'forbidden-secret']],
            ['payload' => 'process-local-capability://provider/1'],
            ['payload' => 'resource(3) of type (stream)'],
            ['payload' => new \stdClass()],
        ] as $attack) {
            try {
                $service->assertClean($attack);
                self::fail('Expected complete-chain exclusion refusal');
            } catch (\RuntimeException $error) {
                self::assertSame('PBL1013_SECRET_OR_PROCESS_LOCAL_CAPABILITY_REFUSED', $error->getMessage());
            }
        }
    }

    public function testIncompleteUnknownAndResealedSecretMaterialFailClosed(): void
    {
        $chain = $this->chain();
        $chain['fixtures'] = [];
        try {
            (new Service())->prove('complete-chain-exclusion.1', $chain);
            self::fail('Expected incomplete section refusal');
        } catch (\RuntimeException $error) {
            self::assertStringStartsWith('PBL1011_COMPLETE_CHAIN_SECTION_INCOMPLETE', $error->getMessage());
        }

        $chain = $this->chain();
        $chain['fixtures'][0]['schema'] = 'unknown/schema';
        $chain['fixtures'][0] = $this->reseal($chain['fixtures'][0]);
        try {
            (new Service())->prove('complete-chain-exclusion.1', $chain);
            self::fail('Expected unknown schema refusal');
        } catch (\RuntimeException $error) {
            self::assertStringStartsWith('PBL1014_COMPLETE_CHAIN_SCHEMA_REFUSED', $error->getMessage());
        }

        $chain = $this->chain();
        $chain['exceptions'][0]['message'] = 'failure exposed Bearer forbidden-secret';
        $chain['exceptions'][0] = $this->reseal($chain['exceptions'][0]);
        $this->expectExceptionMessage('PBL1013_SECRET_OR_PROCESS_LOCAL_CAPABILITY_REFUSED');
        (new Service())->prove('complete-chain-exclusion.1', $chain);
    }

    public function testBatchBoundaryPreservesQualificationAndAuthorizesOnlyBatch5(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-4-complete-chain-exclusion.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-4-complete.md',
        );
        foreach ([
            'BATCH_4_COMPLETE_CHAIN_SECRET_AND_PROCESS_LOCAL_CAPABILITY_EXCLUSION_PROVED',
            'exactly twelve ordered sections', 'all admitted content is then recursively inspected',
            'up to three normalization layers', 'Sibling string fragments are concatenated and rescanned',
            'does not authenticate the still caller-constructible Batch 1 origin producer',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 5 disposable real-mission execution may next be considered.',
            'requires separate explicit authorization', 'may not invoke a provider, perform external I/O',
            'handle a live credential or capability', 'repair or disposition the historical audit',
            'remove the closure qualification', 'only a sanitized package may enter the repository',
            'Estimated campaign countdown after Batch 4: three batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function chain(): array
    {
        return [
            'evidence_origin' => [$this->record(Origin::SCHEMA, Origin::REQUIRED_FIELDS, 'evidence_origin_id')],
            'execution_provenance' => [$this->record(Provenance::SCHEMA, Provenance::REQUIRED_FIELDS, 'execution_provenance_id')],
            'fixtures' => [$this->record(Fixture::SCHEMA, Fixture::REQUIRED_FIELDS, 'fixture_id')],
            'recovery_plans' => [$this->record(Plan::SCHEMA, Plan::REQUIRED_FIELDS, 'recovery_plan_id')],
            'mutations' => [$this->record(Mutation::SCHEMA, Mutation::REQUIRED_FIELDS, 'mutation_id')],
            'cases' => [$this->record(CaseContract::SCHEMA, CaseContract::REQUIRED_FIELDS, 'case_id')],
            'expectations' => [$this->record(Expectation::SCHEMA, Expectation::REQUIRED_FIELDS, 'expected_result_id')],
            'results' => [$this->record(Result::SCHEMA, Result::REQUIRED_FIELDS, 'result_id')],
            'dependency_graph' => [$this->record(Graph::SCHEMA, Graph::REQUIRED_FIELDS, 'graph_id')],
            'aggregates' => [$this->record(Aggregate::SCHEMA, Aggregate::REQUIRED_FIELDS, 'receipt_id')],
            'exceptions' => [$this->record(ExceptionEvidence::SCHEMA, ExceptionEvidence::REQUIRED_FIELDS, 'exception_id')],
            'closure_material' => [$this->record(Closure::SCHEMA, Closure::REQUIRED_FIELDS, 'closure_id')],
        ];
    }

    private function record(string $schema, array $fields, string $id): array
    {
        $record = [];
        foreach ($fields as $field) {
            if ('record_digest' !== $field) {
                $record[$field] = null;
            }
        }
        $record['schema'] = $schema;
        $record[$id] = $id.'.1';
        $record['sealed'] = true;

        return $this->reseal($record);
    }

    private function reseal(array $record): array
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
