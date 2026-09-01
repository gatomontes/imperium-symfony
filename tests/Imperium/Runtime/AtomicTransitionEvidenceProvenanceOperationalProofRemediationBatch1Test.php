<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceOriginContract as Origin;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContract as Provenance;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContractValidator as Validator;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch1Test extends TestCase
{
    public function testExactAuthorityEmptyOriginAndProvenanceValidate(): void
    {
        $origin = $this->origin();
        $provenance = $this->provenance($origin);
        $validator = new Validator();

        $validator->assertOrigin($origin);
        $validator->assertExecutionProvenance($provenance, $origin);

        self::assertSame(Origin::REQUIRED_FIELDS, array_keys($origin));
        self::assertSame(Provenance::REQUIRED_FIELDS, array_keys($provenance));
        self::assertTrue($origin['authority_empty']);
        self::assertTrue($provenance['authority_empty']);
        foreach ([
            'trusted_executor_implemented', 'execution_performed',
            'caller_result_accepted', 'result_produced',
            'dependency_graph_derived', 'complete_chain_exclusion_proved',
            'operational_receipt_created', 'continuing_authority',
        ] as $falseClaim) {
            self::assertFalse($provenance[$falseClaim]);
        }
    }

    public function testSourceBuildMissionAndExecutorSubstitutionRefuse(): void
    {
        $origin = $this->origin();
        foreach ([
            'disposable_mission_id' => 'mission.other',
            'source_commit' => str_repeat('f', 40),
            'build_artifact_digest' => str_repeat('e', 64),
            'executor_implementation_digest' => str_repeat('d', 64),
            'case_set_root' => str_repeat('c', 64),
        ] as $field => $replacement) {
            $provenance = $this->provenance($origin);
            $provenance[$field] = $replacement;
            $provenance = $this->reseal($provenance);
            try {
                (new Validator())->assertExecutionProvenance($provenance, $origin);
                self::fail('Expected provenance substitution refusal for '.$field);
            } catch (\RuntimeException $error) {
                self::assertSame('PBL995_EXECUTION_PROVENANCE_INVALID', $error->getMessage());
            }
        }
    }

    public function testFreshnessFalseExecutionAndProhibitedMaterialRefuse(): void
    {
        $origin = $this->origin();
        $origin['expires_at'] = $origin['not_before'];
        $origin = $this->reseal($origin);
        $this->expectExceptionMessage('PBL994_EVIDENCE_ORIGIN_INVALID');
        (new Validator())->assertOrigin($origin);
    }

    public function testFalseExecutionAndProhibitedMaterialRefuse(): void
    {
        $origin = $this->origin();
        $provenance = $this->provenance($origin);
        $provenance['execution_performed'] = true;
        $provenance = $this->reseal($provenance);
        try {
            (new Validator())->assertExecutionProvenance($provenance, $origin);
            self::fail('Expected false execution refusal');
        } catch (\RuntimeException $error) {
            self::assertSame('PBL995_EXECUTION_PROVENANCE_INVALID', $error->getMessage());
        }

        $origin = $this->origin();
        $origin['mission_dossier']['credential_value'] = 'forbidden';
        $origin = $this->reseal($origin);
        $this->expectExceptionMessage('PBL994_EVIDENCE_ORIGIN_INVALID');
        (new Validator())->assertOrigin($origin);
    }

    public function testContractsAndValidatorHaveNoExecutionOrPersistenceDependency(): void
    {
        foreach ([
            'AtomicTransitionEvidenceOriginContract.php',
            'AtomicTransitionExecutionProvenanceContract.php',
            'AtomicTransitionExecutionProvenanceContractValidator.php',
        ] as $file) {
            $source = (string) file_get_contents(
                dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'.$file,
            );
            foreach ([
                'AtomicTransitionEvidenceDeterministicCaseExecutor',
                'Persistence\\AtomicTransition', 'ImmutableRecordStore', 'MutableStateStore',
                'AuthorityConsumptionStore', 'ProviderInvocation',
                'public function execute', 'public function persist',
            ] as $forbidden) {
                self::assertStringNotContainsString($forbidden, $source);
            }
        }
    }

    public function testBatchBoundaryAndQualificationRemainExplicit(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-1-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-1-complete.md',
        );
        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_EVIDENCE_ORIGIN_AND_EXECUTION_PROVENANCE_CONTRACTS_COMPLETE',
            'do not authenticate the origin producer',
            'no caller result was accepted',
            'Validation is syntactic and relational.',
            'No future execution receipt or result digest appears in either Batch 1 record.',
            'does not repair, disable or subordinate the historical audit',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 2 trusted case-execution corridor may next be considered.',
            'accept no caller result or execution, finding or match boolean',
            'reserved for Batch 3', 'reserved for Batch 4', 'reserved for Batch 5',
            'reserved for Batch 6', 'remove the closure qualification',
            '`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`',
            'Estimated campaign countdown after Batch 1: six batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function origin(): array
    {
        return $this->seal([
            'schema' => Origin::SCHEMA,
            'evidence_origin_id' => 'atomic-evidence-origin.1',
            'experiment_id' => 'atomic-experiment.1',
            'disposable_mission_id' => 'disposable-mission.1',
            'replay_contention_root' => 'atomic-proof-root.1',
            'disposable_mission_authorization' => $this->reference('authorization.1', 'authorization/v1', '1'),
            'authorized_case_profile' => 'atomic-transition-required-cases.v1',
            'source_repository' => 'gatomontes/imperium-symfony',
            'source_commit' => str_repeat('a', 40),
            'source_tree_digest' => str_repeat('b', 64),
            'dirty_tree_refused' => true,
            'build_id' => 'atomic-build.1',
            'build_artifact_digest' => str_repeat('c', 64),
            'dependency_lock_digest' => str_repeat('d', 64),
            'runtime_version' => 'php-8.4.14',
            'build_command_identity' => 'composer-install-no-dev.v1',
            'executor_principal' => $this->reference('executor-principal.1', 'executor-principal/v1', '2'),
            'executor_implementation_digest' => str_repeat('e', 64),
            'executor_entry_point' => 'atomic-transition-trusted-executor.v1',
            'execution_environment_class' => 'disposable-local-one-root.v1',
            'mission_dossier' => $this->reference('mission-dossier.1', 'mission-dossier/v1', '3'),
            'fixture_set_root' => str_repeat('f', 64),
            'recovery_plan' => $this->reference('recovery-plan.1', 'recovery-plan/v1', '4'),
            'mutation_set_root' => str_repeat('5', 64),
            'expected_result_set_root' => str_repeat('6', 64),
            'case_set_root' => str_repeat('7', 64),
            'authoritative_evidence_root' => 'disposable-root.1',
            'fixture_custodian' => 'trusted-fixture-custodian.v1',
            'origin_producer' => 'future-origin-producer.v1',
            'issued_at' => '2026-09-01T12:00:00+00:00',
            'not_before' => '2026-09-01T12:00:00+00:00',
            'expires_at' => '2026-09-01T12:15:00+00:00',
            'prior_origin_disposition' => 'ABSENT',
            'limitations' => Origin::LIMITATIONS,
            'sanitized_evidence_package_id' => 'sanitized-evidence-package.1',
            'sanitized_evidence_package_digest' => str_repeat('8', 64),
            'raw_private_evidence_excluded' => true,
            'single_use' => true,
            'authority_empty' => true,
            'execution_performed' => false,
            'operational_receipt_created' => false,
            'continuing_authority' => false,
            'status' => Origin::STATUS,
            'sealed' => true,
        ]);
    }

    private function provenance(array $origin): array
    {
        $record = [
            'schema' => Provenance::SCHEMA,
            'execution_provenance_id' => 'atomic-execution-provenance.1',
            'evidence_origin' => $this->referenceFor($origin, 'evidence_origin_id'),
        ];
        foreach ([
            'experiment_id', 'disposable_mission_id', 'replay_contention_root',
            'source_commit', 'source_tree_digest', 'build_id',
            'build_artifact_digest', 'dependency_lock_digest', 'runtime_version',
            'executor_principal', 'executor_implementation_digest',
            'executor_entry_point', 'execution_environment_class',
            'mission_dossier', 'fixture_set_root', 'recovery_plan',
            'mutation_set_root', 'expected_result_set_root', 'case_set_root',
            'authoritative_evidence_root', 'fixture_custodian', 'origin_producer',
        ] as $field) {
            $record[$field] = $origin[$field];
        }
        $record['authorized_not_before'] = $origin['not_before'];
        $record['authorized_expires_at'] = $origin['expires_at'];
        foreach ([
            'limitations', 'sanitized_evidence_package_id',
            'sanitized_evidence_package_digest',
        ] as $field) {
            $record[$field] = $origin[$field];
        }

        return $this->seal($record + [
            'trusted_executor_implemented' => false,
            'execution_performed' => false,
            'caller_result_accepted' => false,
            'result_produced' => false,
            'dependency_graph_derived' => false,
            'complete_chain_exclusion_proved' => false,
            'operational_receipt_created' => false,
            'authority_empty' => true,
            'continuing_authority' => false,
            'status' => Provenance::STATUS,
            'sealed' => true,
        ]);
    }

    private function reference(string $id, string $schema, string $digit): array
    {
        return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema];
    }

    private function referenceFor(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function reseal(array $record): array
    {
        unset($record['record_digest']);

        return $this->seal($record);
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
