<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as CaseContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAggregateAuditBuilder as Builder;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivedCaseResultContract as ResultContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceTerminalRecomputationContract as TerminalContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceTerminalRecomputer as Recomputer;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceValueAwareSecretExclusionService as SecretService;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationBatch4Test extends TestCase
{
    public function testEverySealReferenceAndOrderedDigestIsRecomputed(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate] = $this->chain();
        $terminal = $this->recomputer()->recompute('atomic-transition-terminal-recomputation.1', $cases, $results, $manifest, $proof, $aggregate);

        self::assertSame(TerminalContract::REQUIRED_FIELDS, array_keys($terminal));
        foreach (['all_record_seals_recomputed', 'all_references_recomputed', 'ordered_result_set_recomputed', 'capability_manifest_recomputed', 'secret_exclusion_proof_recomputed', 'aggregate_receipt_recomputed', 'material_evidence_defect_corrected', 'terminal_recomputation_performed', 'read_only'] as $derived) {
            self::assertTrue($terminal[$derived]);
        }
        foreach (['qualification_removed', 'closure_replacement_authorized', 'journal_persisted', 'live_lock_acquired', 'state_written_or_repaired', 'authority_issued_or_consumed', 'execution_admitted', 'successor_adopted', 'binding_state_changed', 'durable_winner_or_receipt_created', 'provider_effect_started', 'continuing_authority'] as $prohibited) {
            self::assertFalse($terminal[$prohibited]);
        }
        self::assertSame($aggregate['ordered_result_set_digest'], $terminal['recomputed_result_set_digest']);
        self::assertSame(TerminalContract::STATUS, $terminal['status']);
    }

    public function testResealedManifestLieIsRefusedByIndependentRecomputation(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate] = $this->chain();
        $manifest['capabilities']['external_io'] = true;
        $manifest = $this->reseal($manifest);

        $this->expectExceptionMessage('PBL990_TERMINAL_MANIFEST_RECOMPUTATION_MISMATCH');
        $this->recomputer()->recompute('atomic-transition-terminal-recomputation.1', $cases, $results, $manifest, $proof, $aggregate);
    }

    public function testResealedSecretProofLieIsRefusedByIndependentRecomputation(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate] = $this->chain();
        $proof['all_attacks_refused'] = false;
        $proof = $this->reseal($proof);

        $this->expectExceptionMessage('PBL991_TERMINAL_SECRET_PROOF_RECOMPUTATION_MISMATCH');
        $this->recomputer()->recompute('atomic-transition-terminal-recomputation.1', $cases, $results, $manifest, $proof, $aggregate);
    }

    public function testResultReorderingCannotSurviveAggregateRecomputation(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate] = $this->chain();
        [$results[0], $results[1]] = [$results[1], $results[0]];
        $proof = (new SecretService())->prove($proof['proof_id'], $results);

        $this->expectExceptionMessage('PBL986_AGGREGATE_RESULT_UNBOUND');
        $this->recomputer()->recompute('atomic-transition-terminal-recomputation.1', $cases, $results, $manifest, $proof, $aggregate);
    }

    public function testBatchBoundaryReservesQualificationRemovalForClosure(): void
    {
        $doc = $this->document('docs/atomic-transition-evidence-derivation-remediation-batch-4-terminal-recomputation.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-batch-4-complete.md');
        foreach (['BATCH_4_TERMINAL_EVIDENCE_CHAIN_RECOMPUTATION_COMPLETE', 'every seal, reference and ordered digest', 'MATERIAL_EVIDENCE_DEFECT_CORRECTED_PENDING_CLOSURE', 'does not remove the campaign qualification'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['Only Atomic Transition Evidence Derivation Remediation Batch 5 read-only closure replacement', 'may not persist a journal', 'may not issue or consume authority', 'may not handle or resolve a live credential or capability', 'may not invoke a provider', 'may not perform external I/O', 'Estimated remediation countdown after Batch 4: one batch'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function chain(): array
    {
        $cases = [];
        $results = [];
        foreach (CaseContract::KINDS as $index => $kind) {
            $case = $this->seal([
                'schema' => CaseContract::SCHEMA, 'case_id' => 'terminal-case.'.strtolower($kind).'.1',
                'case_kind' => $kind, 'replay_contention_root' => 'terminal-root.1',
                'primary_fixture' => $this->stubReference('fixture.'.$index, 'fixture/v1', (string) $index),
                'comparison_fixture' => null,
                'mutation' => $this->stubReference('mutation.'.$index, 'mutation/v1', 'a'),
                'expected_result' => $this->stubReference('expected.'.$index, 'expected/v1', 'b'),
                'case_executed' => false, 'finding_derived' => false,
                'status' => CaseContract::STATUS, 'sealed' => true,
            ]);
            $cases[] = $case;
            $results[] = $this->derivedCaseResult($case, $index);
        }
        $builder = new Builder();
        $manifest = $builder->manifest('terminal-manifest.1');
        $proof = (new SecretService())->prove('terminal-secret-proof.1', $results);
        $aggregate = $builder->build('terminal-aggregate.1', 'terminal-root.1', $cases, $results, $manifest, $proof);

        return [$cases, $results, $manifest, $proof, $aggregate];
    }

    private function derivedCaseResult(array $case, int $index): array
    {
        return $this->seal([
            'schema' => ResultContract::SCHEMA, 'case_reference' => $this->reference($case, 'case_id'),
            'plan_reference' => $this->stubReference('plan.1', 'plan/v1', 'c'),
            'primary_fixture_reference' => $this->stubReference('fixture.'.$index, 'fixture/v1', (string) $index),
            'comparison_fixture_reference' => null,
            'mutation_reference' => $this->stubReference('mutation.'.$index, 'mutation/v1', 'a'),
            'expected_result_reference' => $this->stubReference('expected.'.$index, 'expected/v1', 'b'),
            'replacement_digest_observed' => null, 'observed_classification' => 'ABSENT',
            'observed_directive' => 'NO_ACTION', 'observed_comparison' => 'NOT_APPLICABLE',
            'observed_validator_error' => null, 'derived_finding_codes' => ['READ_ONLY_FINDING_DERIVED'],
            'expectation_matched' => true, 'case_executed' => true, 'finding_derived' => true,
            'read_only' => true, 'journal_persisted' => false, 'live_lock_acquired' => false,
            'state_written_or_repaired' => false, 'authority_issued_or_consumed' => false,
            'execution_admitted' => false, 'successor_adopted' => false,
            'binding_state_changed' => false, 'durable_winner_or_receipt_created' => false,
            'provider_effect_started' => false, 'continuing_authority' => false,
            'status' => ResultContract::STATUS, 'sealed' => true,
        ]);
    }

    private function recomputer(): Recomputer
    {
        return new Recomputer(new Builder(), new SecretService());
    }

    private function stubReference(string $id, string $schema, string $digit): array
    {
        return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema];
    }

    private function reference(array $record, string $id): array
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
