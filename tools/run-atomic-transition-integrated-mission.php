<?php

declare(strict_types=1);

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionCompleteChainExclusionService;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as CaseContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivationContractValidator as CaseValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDeterministicCaseExecutor as CaseExecutor;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceExpectedResultContract as Expected;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceFixtureContract as Fixture;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceMutationContract as Mutation;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceOriginContract as Origin;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContract as Provenance;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutionProvenanceContractValidator as ProvenanceValidator;
use App\Imperium\Runtime\Imperator\AtomicTransitionExecutorDependencyCapabilityGraphDeriver as GraphDeriver;
use App\Imperium\Runtime\Imperator\AtomicTransitionTrustedCaseExecutionCorridor as Corridor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionCombinedWinnerContract as Winner;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionDisposableProofClassifier as Classifier;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor as Reconstructor;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReceiptContract as Receipt;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract as Plan;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContractValidator as PlanValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionContractValidator as TransactionValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionTransactionJournalContract as Journal;

require dirname(__DIR__).'/vendor/autoload.php';

final class AtomicTransitionIntegratedDisposableMission
{
    private const string ROOT = 'atomic-transition-disposable-proof-1';

    public function run(): void
    {
        $source = $this->environment();
        $privateFile = $source['private_file'];
        $sanitizedFile = $source['sanitized_file'];
        unset($source['private_file'], $source['sanitized_file']);
        $transaction = new TransactionValidator();
        $classifier = new Classifier($transaction);
        $provenanceValidator = new ProvenanceValidator();
        $corridor = new Corridor(
            $provenanceValidator,
            new CaseExecutor(
                new CaseValidator($transaction),
                new Reconstructor(new PlanValidator(), $classifier),
                $classifier,
            ),
        );
        $plan = $this->plan();
        $origin = $this->origin($source, $plan, $corridor);
        $provenance = $this->provenance($origin);
        $fixture = $this->fixture();
        $mutation = $this->mutation();
        $expected = $this->expected();
        $case = $this->case($fixture, $mutation, $expected);
        $result = $corridor->executeCase(
            $origin, $provenance, $case, $fixture, null,
            $mutation, $expected, $plan,
        );
        $graph = (new GraphDeriver($provenanceValidator))->derive(
            'atomic-transition-integrated-graph.1',
            $origin,
            $provenance,
            $corridor,
        );
        $matrix = $this->acceptanceMatrix($classifier);

        $private = [
            'schema' => 'imperium.private-atomic-transition-integrated-disposable-mission/v1',
            'mission_id' => 'ATOMIC-TRANSITION-DISPOSABLE-PROOF-1',
            'source' => $source,
            'origin' => $origin,
            'provenance' => $provenance,
            'case' => $case,
            'fixture' => $fixture,
            'mutation' => $mutation,
            'expected' => $expected,
            'plan' => $plan,
            'trusted_result' => $result,
            'dependency_graph' => $graph,
            'acceptance_matrix' => $matrix,
            'caller_result_accepted' => false,
            'provider_or_external_effect_authorized' => false,
            'live_credential_or_capability_authorized' => false,
            'runtime_state_written' => false,
            'continuing_authority' => false,
        ];
        (new AtomicTransitionCompleteChainExclusionService())->assertClean($private);
        $private['complete_chain_content_exclusion_observed'] = true;
        $private['record_digest'] = hash('sha256', CanonicalJson::encode($private));

        $summary = [
            'schema' => 'imperium.sanitized-atomic-transition-integrated-disposable-mission-evidence/v1',
            'mission_id' => $private['mission_id'],
            'source_commit' => $source['source_commit'],
            'source_tree_digest' => $source['source_tree_digest'],
            'build_artifact_digest' => $source['build_artifact_digest'],
            'dependency_lock_digest' => $source['dependency_lock_digest'],
            'runner_digest' => $source['runner_digest'],
            'mission_implementation_digest' => $source['mission_implementation_digest'],
            'php_version' => PHP_VERSION,
            'evidence_origin_digest' => $origin['record_digest'],
            'execution_provenance_digest' => $provenance['record_digest'],
            'trusted_result_digest' => $result['record_digest'],
            'dependency_graph_digest' => $graph['record_digest'],
            'acceptance_matrix' => $matrix,
            'complete_chain_content_exclusion_observed' => true,
            'caller_result_accepted' => false,
            'provider_or_external_effect_authorized' => false,
            'live_credential_or_capability_authorized' => false,
            'runtime_state_written' => false,
            'continuing_authority' => false,
            'integrated_operational_receipt_created' => true,
            'private_receipt_digest' => $private['record_digest'],
            'private_receipt_retention' => 'OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT',
            'disposition' => 'PROVED',
        ];
        $summary['record_digest'] = hash('sha256', CanonicalJson::encode($summary));
        $this->write($privateFile, $private);
        $this->write($sanitizedFile, $summary);
    }

    private function acceptanceMatrix(Classifier $classifier): array
    {
        $complete = $this->completeEvidence('journal.1');
        $changed = $complete;
        $changed['journal']['source_decision']['digest'] = str_repeat('9', 64);
        $changed = $this->rebind($changed);
        $contender = $this->completeEvidence('journal.2');
        $matrix = [
            'interruption_before_journal' => $classifier->classify([]),
            'interruption_after_journal' => $classifier->classify(['journal' => $complete['journal']]),
            'interruption_after_winner' => $classifier->classify(['journal' => $complete['journal'], 'winner' => $complete['winner']]),
            'interruption_after_receipt' => $classifier->classify($complete),
            'exact_replay' => $classifier->compare($complete, $complete),
            'changed_evidence' => $classifier->compare($complete, $changed),
            'same_root_contention' => $classifier->compare($complete, $contender),
            'partial_write' => $classifier->classify(['journal' => $complete['journal'], 'receipt' => $complete['receipt']]),
        ];
        $expected = [
            'interruption_before_journal' => 'ABSENT',
            'interruption_after_journal' => 'PREPARED',
            'interruption_after_winner' => 'COMMITTING',
            'interruption_after_receipt' => 'COMMITTED',
            'exact_replay' => 'EXACT_REPLAY',
            'changed_evidence' => 'CHANGED_EVIDENCE_REFUSED',
            'same_root_contention' => 'SAME_ROOT_CONTENTION_REFUSED',
            'partial_write' => 'INCOMPLETE',
        ];
        if ($expected !== $matrix) {
            throw new RuntimeException('REFUSED_ACCEPTANCE_MATRIX_MISMATCH');
        }
        return $matrix;
    }

    private function completeEvidence(string $journalId): array
    {
        $ref = fn (string $id, string $digit, string $schema): array => [
            'id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema,
        ];
        $target = fn (string $id, string $schema): array => ['id' => $id, 'schema' => $schema];
        $journal = $this->seal([
            'schema' => Journal::SCHEMA, 'journal_id' => $journalId,
            'instance_id' => 'instance.1',
            'source_decision' => $ref('decision.1', 'a', 'decision/v1'),
            'transition_authority' => $ref('authority.1', 'b', 'authority/v1'),
            'replay_contention_root' => self::ROOT,
            'canonical_lock_order' => Journal::LOCK_ORDER,
            'write_set' => [
                'authority_consumption' => $target('authority.1', 'authority-consumption/v1'),
                'v3_admission' => $target('admission.1', 'admission/v3'),
                'adoption_join' => $target('join.1', 'adoption-join/v1'),
                'source_binding_transition' => $target('source-binding.1', 'binding-transition/v1'),
                'successor_binding_activation' => $target('successor-binding.1', 'binding-activation/v1'),
                'winner_target' => $target('winner.1', Winner::SCHEMA),
                'receipt_target' => $target('receipt.1', Receipt::SCHEMA),
            ],
            'recovery_states' => Journal::RECOVERY_STATES,
            'status' => Journal::STATUS, 'journal_opened' => false,
            'combined_commit_performed' => false, 'continuing_authority' => false,
            'sealed' => true,
        ]);
        $winner = $this->seal([
            'schema' => Winner::SCHEMA, 'winner_id' => 'winner.'.$journalId,
            'instance_id' => 'instance.1', 'transaction_journal' => $this->reference($journal, 'journal_id'),
            'source_decision' => $journal['source_decision'], 'transition_authority' => $journal['transition_authority'],
            'v3_admission' => $ref('admission.1', 'c', 'admission/v3'),
            'adoption_join' => $ref('join.1', 'd', 'adoption-join/v1'),
            'source_binding_transition' => $ref('source-binding.1', 'e', 'binding-transition/v1'),
            'successor_binding_activation' => $ref('successor-binding.1', 'f', 'binding-activation/v1'),
            'replay_contention_root' => self::ROOT, 'authority_consumed' => false,
            'execution_admitted' => false, 'successor_adopted' => false,
            'source_binding_deactivated' => false, 'successor_binding_activated' => false,
            'combined_commit_performed' => false, 'continuing_authority' => false,
            'status' => Winner::STATUS, 'sealed' => true,
        ]);
        $receipt = $this->seal([
            'schema' => Receipt::SCHEMA, 'receipt_id' => 'receipt.'.$journalId,
            'instance_id' => 'instance.1', 'combined_winner' => $this->reference($winner, 'winner_id'),
            'transaction_journal' => $this->reference($journal, 'journal_id'),
            'replay_contention_root' => self::ROOT, 'combined_commit_observed' => false,
            'provider_effect_started' => false, 'continuing_authority' => false,
            'status' => Receipt::STATUS, 'sealed' => true,
        ]);
        return ['journal' => $journal, 'winner' => $winner, 'receipt' => $receipt];
    }

    private function rebind(array $evidence): array
    {
        $evidence['journal'] = $this->seal($evidence['journal']);
        $evidence['winner']['transaction_journal'] = $this->reference($evidence['journal'], 'journal_id');
        $evidence['winner']['source_decision'] = $evidence['journal']['source_decision'];
        $evidence['winner'] = $this->seal($evidence['winner']);
        $evidence['receipt']['transaction_journal'] = $this->reference($evidence['journal'], 'journal_id');
        $evidence['receipt']['combined_winner'] = $this->reference($evidence['winner'], 'winner_id');
        $evidence['receipt'] = $this->seal($evidence['receipt']);
        return $evidence;
    }

    private function environment(): array
    {
        $map = [
            'source_commit' => 'IMPERIUM_PROOF_SOURCE_COMMIT',
            'source_tree_digest' => 'IMPERIUM_PROOF_SOURCE_TREE_DIGEST',
            'build_artifact_digest' => 'IMPERIUM_PROOF_BUILD_DIGEST',
            'dependency_lock_digest' => 'IMPERIUM_PROOF_LOCK_DIGEST',
            'runner_digest' => 'IMPERIUM_PROOF_RUNNER_DIGEST',
            'mission_implementation_digest' => 'IMPERIUM_PROOF_MISSION_DIGEST',
            'private_file' => 'IMPERIUM_PROOF_PRIVATE_FILE',
            'sanitized_file' => 'IMPERIUM_PROOF_SANITIZED_FILE',
        ];
        $result = [];
        foreach ($map as $field => $name) {
            $value = getenv($name);
            if (!is_string($value) || '' === $value) {
                throw new RuntimeException('REFUSED_MISSION_ENVIRONMENT_INCOMPLETE');
            }
            $result[$field] = $value;
        }
        return $result;
    }

    private function origin(array $source, array $plan, Corridor $corridor): array
    {
        $file = (new ReflectionClass($corridor))->getFileName();
        $stub = fn (string $id, string $digit): array => [
            'id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => 'imperium.reference/v1',
        ];
        return $this->seal([
            'schema' => Origin::SCHEMA, 'evidence_origin_id' => 'integrated-evidence-origin.1',
            'experiment_id' => 'integrated-experiment.1', 'disposable_mission_id' => 'integrated-disposable-mission.1',
            'replay_contention_root' => self::ROOT,
            'disposable_mission_authorization' => $stub('operator-authorization.1', '1'),
            'authorized_case_profile' => 'atomic-transition-integrated.v1',
            'source_repository' => 'gatomontes/imperium-symfony', 'source_commit' => $source['source_commit'],
            'source_tree_digest' => $source['source_tree_digest'], 'dirty_tree_refused' => true,
            'build_id' => 'integrated-build.1', 'build_artifact_digest' => $source['build_artifact_digest'],
            'dependency_lock_digest' => $source['dependency_lock_digest'], 'runtime_version' => 'php-'.PHP_VERSION,
            'build_command_identity' => 'composer-locked.v1', 'executor_principal' => $stub('integrated-executor.1', '2'),
            'executor_implementation_digest' => hash_file('sha256', (string) $file),
            'executor_entry_point' => 'atomic-transition-trusted-executor.v1',
            'execution_environment_class' => 'disposable-local-one-root.v1',
            'mission_dossier' => $stub('integrated-dossier.1', '3'),
            'fixture_set_root' => hash('sha256', 'integrated-fixtures'),
            'recovery_plan' => $this->reference($plan, 'recovery_plan_id'),
            'mutation_set_root' => hash('sha256', 'integrated-mutations'),
            'expected_result_set_root' => hash('sha256', 'integrated-expectations'),
            'case_set_root' => hash('sha256', 'integrated-cases'),
            'authoritative_evidence_root' => 'integrated-disposable-root.1',
            'fixture_custodian' => 'integrated-fixture-custodian.v1', 'origin_producer' => 'integrated-runner.v1',
            'issued_at' => '2026-09-01T19:00:00+00:00', 'not_before' => '2026-09-01T19:00:00+00:00',
            'expires_at' => '2026-09-02T19:00:00+00:00', 'prior_origin_disposition' => 'ABSENT',
            'limitations' => Origin::LIMITATIONS, 'sanitized_evidence_package_id' => 'integrated-package.1',
            'sanitized_evidence_package_digest' => hash('sha256', 'integrated-package'),
            'raw_private_evidence_excluded' => true, 'single_use' => true, 'authority_empty' => true,
            'execution_performed' => false, 'operational_receipt_created' => false,
            'continuing_authority' => false, 'status' => Origin::STATUS, 'sealed' => true,
        ]);
    }

    private function provenance(array $origin): array
    {
        $record = ['schema' => Provenance::SCHEMA, 'execution_provenance_id' => 'integrated-provenance.1', 'evidence_origin' => $this->reference($origin, 'evidence_origin_id')];
        foreach (['experiment_id','disposable_mission_id','replay_contention_root','source_commit','source_tree_digest','build_id','build_artifact_digest','dependency_lock_digest','runtime_version','executor_principal','executor_implementation_digest','executor_entry_point','execution_environment_class','mission_dossier','fixture_set_root','recovery_plan','mutation_set_root','expected_result_set_root','case_set_root','authoritative_evidence_root','fixture_custodian','origin_producer'] as $field) {
            $record[$field] = $origin[$field];
        }
        $record['authorized_not_before'] = $origin['not_before'];
        $record['authorized_expires_at'] = $origin['expires_at'];
        $record['limitations'] = $origin['limitations'];
        $record['sanitized_evidence_package_id'] = $origin['sanitized_evidence_package_id'];
        $record['sanitized_evidence_package_digest'] = $origin['sanitized_evidence_package_digest'];
        return $this->seal($record + ['trusted_executor_implemented' => false, 'execution_performed' => false, 'caller_result_accepted' => false, 'result_produced' => false, 'dependency_graph_derived' => false, 'complete_chain_exclusion_proved' => false, 'operational_receipt_created' => false, 'authority_empty' => true, 'continuing_authority' => false, 'status' => Provenance::STATUS, 'sealed' => true]);
    }

    private function plan(): array { return $this->seal(['schema' => Plan::SCHEMA, 'recovery_plan_id' => 'integrated-plan.1', 'instance_id' => 'instance.1', 'replay_contention_root' => self::ROOT, 'classification_directives' => Plan::DIRECTIVES, 'automatic_repair_permitted' => false, 'state_write_permitted' => false, 'authority_action_permitted' => false, 'plan_applied' => false, 'continuing_authority' => false, 'status' => Plan::STATUS, 'sealed' => true]); }
    private function fixture(): array { return $this->seal(['schema' => Fixture::SCHEMA, 'fixture_id' => 'integrated-fixture.1', 'instance_id' => 'instance.1', 'replay_contention_root' => self::ROOT, 'fixture_kind' => 'EMPTY', 'evidence' => [], 'source_contracts' => [Journal::SCHEMA, Winner::SCHEMA, Receipt::SCHEMA], 'immutable' => true, 'status' => Fixture::STATUS, 'sealed' => true]); }
    private function mutation(): array { return $this->seal(['schema' => Mutation::SCHEMA, 'mutation_id' => 'integrated-mutation.1', 'mutation_kind' => 'NONE', 'target_path' => null, 'replacement_digest' => null, 'expected_validator_error' => null, 'mutation_applied' => false, 'status' => Mutation::STATUS, 'sealed' => true]); }
    private function expected(): array { return $this->seal(['schema' => Expected::SCHEMA, 'expected_result_id' => 'integrated-expected.1', 'expected_classification' => 'ABSENT', 'expected_directive' => 'NO_ACTION', 'expected_comparison' => 'NOT_APPLICABLE', 'expected_validator_error' => null, 'expected_finding_codes' => ['ABSENT_NO_ACTION_ONLY'], 'result_derived' => false, 'status' => Expected::STATUS, 'sealed' => true]); }
    private function case(array $fixture, array $mutation, array $expected): array { return $this->seal(['schema' => CaseContract::SCHEMA, 'case_id' => 'integrated-case.1', 'case_kind' => 'INTERRUPTION', 'replay_contention_root' => self::ROOT, 'primary_fixture' => $this->reference($fixture, 'fixture_id'), 'comparison_fixture' => null, 'mutation' => $this->reference($mutation, 'mutation_id'), 'expected_result' => $this->reference($expected, 'expected_result_id'), 'case_executed' => false, 'finding_derived' => false, 'status' => CaseContract::STATUS, 'sealed' => true]); }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
    private function seal(array $record): array { unset($record['record_digest']); $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); return $record; }
    private function write(string $path, array $record): void { if (false === file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL, LOCK_EX)) { throw new RuntimeException('REFUSED_EVIDENCE_WRITE_FAILED'); } }
}

(new AtomicTransitionIntegratedDisposableMission())->run();
