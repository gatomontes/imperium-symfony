<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivationContractValidator as CaseValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDeterministicCaseExecutor as CaseExecutor;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceOriginContract as Origin;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContract as Provenance;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContractValidator as ProvenanceValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutorDependencyCapabilityGraphContract as Graph;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutorDependencyCapabilityGraphDeriver as Deriver;
use App\Imperium\Runtime\Imperator\AtomicTransitionTrustedCaseExecutionCorridor as Corridor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch3Test extends TestCase
{
    public function testActualResolvedGraphIsDerivedAndBuildBound(): void
    {
        $executor = $this->corridor();
        $origin = $this->origin($this->implementationDigest(Corridor::class));
        $provenance = $this->provenance($origin);
        $graph = (new Deriver(new ProvenanceValidator()))->derive(
            'atomic-executor-graph.1',
            $origin,
            $provenance,
            $executor,
        );

        self::assertSame(Graph::REQUIRED_FIELDS, array_keys($graph));
        self::assertSame(Corridor::class, $graph['root_executor_class']);
        self::assertSame($origin['executor_implementation_digest'], $graph['root_implementation_digest']);
        self::assertSame($origin['source_commit'], $graph['source_commit']);
        self::assertSame($origin['build_artifact_digest'], $graph['build_artifact_digest']);
        self::assertSame(8, $graph['node_count']);
        self::assertTrue($graph['complete_recursive_object_traversal']);
        self::assertTrue($graph['build_bound']);
        foreach ([
            'unknown_dependencies', 'substituted_dependencies',
            'mutable_dependencies', 'effect_capable_dependencies',
        ] as $refusals) {
            self::assertSame([], $graph[$refusals]);
        }

        $classes = array_column($graph['nodes'], 'class');
        $sorted = $classes;
        sort($sorted, SORT_STRING);
        self::assertSame($sorted, $classes);
        foreach ($graph['nodes'] as $node) {
            self::assertSame(Graph::NODE_FIELDS, array_keys($node));
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $node['implementation_digest']);
            self::assertTrue($node['readonly_or_stateless']);
            self::assertSame(Graph::CAPABILITIES, $node['capabilities']);
        }
    }

    public function testGraphDerivationIsDeterministicForExactResolvedInstances(): void
    {
        $origin = $this->origin($this->implementationDigest(Corridor::class));
        $provenance = $this->provenance($origin);
        $deriver = new Deriver(new ProvenanceValidator());

        $left = $deriver->derive('atomic-executor-graph.1', $origin, $provenance, $this->corridor());
        $right = $deriver->derive('atomic-executor-graph.1', $origin, $provenance, $this->corridor());

        self::assertSame($left, $right);
    }

    public function testUnknownAndSubstitutedRootsFailClosed(): void
    {
        $origin = $this->origin($this->implementationDigest(Corridor::class));
        $provenance = $this->provenance($origin);
        try {
            (new Deriver(new ProvenanceValidator()))->derive(
                'atomic-executor-graph.1',
                $origin,
                $provenance,
                new \stdClass(),
            );
            self::fail('Expected unknown dependency refusal');
        } catch (\RuntimeException $error) {
            self::assertStringStartsWith('PBL1002_EXECUTOR_GRAPH_UNKNOWN_DEPENDENCY', $error->getMessage());
        }

        $origin = $this->origin(str_repeat('f', 64));
        $provenance = $this->provenance($origin);
        $this->expectExceptionMessage('PBL1001_EXECUTOR_GRAPH_ROOT_SUBSTITUTED');
        (new Deriver(new ProvenanceValidator()))->derive(
            'atomic-executor-graph.1',
            $origin,
            $provenance,
            $this->corridor(),
        );
    }

    public function testDeriverHasExplicitUnknownMutableAndEffectRefusals(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'
            .'AtomicTransitionExecutorDependencyCapabilityGraphDeriver.php',
        );
        foreach ([
            'PBL1001_EXECUTOR_GRAPH_ROOT_SUBSTITUTED',
            'PBL1002_EXECUTOR_GRAPH_UNKNOWN_DEPENDENCY',
            'PBL1003_EXECUTOR_GRAPH_MUTABLE_DEPENDENCY',
            'PBL1004_EXECUTOR_GRAPH_EFFECT_CAPABLE_DEPENDENCY',
            'network_io', 'filesystem_write', 'process_execution',
            'environment_access', 'credential_resolution',
            'provider_invocation', 'runtime_state_mutation',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $source);
        }
    }

    public function testBatchBoundaryAndQualificationRemainExplicit(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-3-capability-graph.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-3-complete.md',
        );
        foreach ([
            'BATCH_3_ACTUAL_RECURSIVE_EXECUTOR_DEPENDENCY_CAPABILITY_GRAPH_DERIVED',
            'actual resolved Batch 2 executor object graph',
            'The allowlist is an admission policy, not the graph.',
            'Node membership and edges come from reflection over the resolved object instances.',
            'Successful derivation requires all capability flags false',
            'complete-chain content exclusion remains reserved for Batch 4',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 4 complete-chain secret and process-local capability exclusion may next be considered.',
            'provenance, fixtures, plans, mutations, cases, expectations, results',
            'derived dependency graph', 'exceptions and closure material',
            'may not run the disposable real mission, handle a live credential or capability',
            'invoke a provider, perform external I/O, mutate runtime state',
            'remove the closure qualification',
            '`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`',
            'Estimated campaign countdown after Batch 3: four batches',
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

    private function origin(string $executorDigest): array
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
            'executor_implementation_digest' => $executorDigest,
            'executor_entry_point' => 'atomic-transition-trusted-executor.v1',
            'execution_environment_class' => 'disposable-local-one-root.v1',
            'mission_dossier' => $this->stub('mission-dossier.1', 'mission-dossier/v1', '3'),
            'fixture_set_root' => str_repeat('e', 64),
            'recovery_plan' => $this->stub('recovery-plan.1', 'recovery-plan/v1', '4'),
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
        $record = ['schema' => Provenance::SCHEMA, 'execution_provenance_id' => 'atomic-execution-provenance.1', 'evidence_origin' => $this->reference($origin, 'evidence_origin_id')];
        foreach (['experiment_id', 'disposable_mission_id', 'replay_contention_root', 'source_commit', 'source_tree_digest', 'build_id', 'build_artifact_digest', 'dependency_lock_digest', 'runtime_version', 'executor_principal', 'executor_implementation_digest', 'executor_entry_point', 'execution_environment_class', 'mission_dossier', 'fixture_set_root', 'recovery_plan', 'mutation_set_root', 'expected_result_set_root', 'case_set_root', 'authoritative_evidence_root', 'fixture_custodian', 'origin_producer'] as $field) {
            $record[$field] = $origin[$field];
        }
        $record['authorized_not_before'] = $origin['not_before']; $record['authorized_expires_at'] = $origin['expires_at'];
        $record['limitations'] = $origin['limitations']; $record['sanitized_evidence_package_id'] = $origin['sanitized_evidence_package_id']; $record['sanitized_evidence_package_digest'] = $origin['sanitized_evidence_package_digest'];

        return $this->seal($record + ['trusted_executor_implemented' => false, 'execution_performed' => false, 'caller_result_accepted' => false, 'result_produced' => false, 'dependency_graph_derived' => false, 'complete_chain_exclusion_proved' => false, 'operational_receipt_created' => false, 'authority_empty' => true, 'continuing_authority' => false, 'status' => Provenance::STATUS, 'sealed' => true]);
    }

    private function implementationDigest(string $class): string
    {
        $file = (new \ReflectionClass($class))->getFileName();
        self::assertIsString($file);
        $source = file_get_contents($file);
        self::assertIsString($source);

        return hash('sha256', $source);
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
