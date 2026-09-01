<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAdversarialCaseContract as CaseContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAggregateAuditBuilder as Builder;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceCorrectedClosureContract as ClosureContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceCorrectedClosureService as ClosureService;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceDerivedCaseResultContract as ResultContract;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceTerminalRecomputer as Recomputer;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceValueAwareSecretExclusionService as SecretService;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceDerivationRemediationBatch5Test extends TestCase
{
    public function testCorrectedClosureIsDerivedFromRecomputedTerminalChain(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate, $terminal] = $this->chain();
        $closure = $this->service()->close('atomic-transition-corrected-closure.1', $cases, $results, $manifest, $proof, $aggregate, $terminal);

        self::assertSame(ClosureContract::REQUIRED_FIELDS, array_keys($closure));
        self::assertSame(ClosureContract::PRIOR_CLOSURE, $closure['superseded_closure_status']);
        self::assertSame(ClosureContract::STATUS, $closure['status']);
        self::assertTrue($closure['material_evidence_defect_corrected']);
        self::assertTrue($closure['qualification_removed']);
        self::assertTrue($closure['campaign_closed']);
        self::assertTrue($closure['read_only']);
        self::assertSame('BOUND_INACTIVE', $closure['provider_binding_status']);
        self::assertSame('NOT_IMPLEMENTED', $closure['required_v3_execution_admission']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $closure['unknown_replay_posture']);
        foreach (['runtime_state_written', 'authority_issued_or_consumed', 'execution_admitted', 'provider_binding_changed', 'durable_winner_or_runtime_receipt_created', 'provider_effect_started', 'continuing_authority'] as $prohibited) {
            self::assertFalse($closure[$prohibited]);
        }
    }

    public function testResealedTerminalClaimCannotAuthorizeClosure(): void
    {
        [$cases, $results, $manifest, $proof, $aggregate, $terminal] = $this->chain();
        $terminal['qualification_removed'] = true;
        $terminal = $this->reseal($terminal);

        $this->expectExceptionMessage('PBL993_CORRECTED_CLOSURE_TERMINAL_CHAIN_INVALID');
        $this->service()->close('atomic-transition-corrected-closure.1', $cases, $results, $manifest, $proof, $aggregate, $terminal);
    }

    public function testClosureServiceHasNoRuntimeMutationDependencies(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceCorrectedClosureService.php');
        foreach (['ImmutableRecordStore', 'MutableStateStore', 'AuthorityConsumptionStore', 'ProviderInvocation', 'AtomicTransition::run'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testCampaignClosureAndNextBoundaryAreExplicit(): void
    {
        $doc = $this->document('docs/atomic-transition-evidence-derivation-remediation-batch-5-corrected-closure.md');
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-derivation-remediation-complete.md');
        foreach (['ATOMIC_TRANSITION_EVIDENCE_DERIVATION_REMEDIATION_COMPLETE', 'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_MATERIAL_EVIDENCE_REMEDIATION', 'supersedes `CAMPAIGN_CLOSURE_ACCEPTED_WITH_MATERIAL_EVIDENCE_DEFECT`', 'Provider binding remains `BOUND_INACTIVE`'] as $finding) {
            self::assertStringContainsString($finding, $doc);
        }
        foreach (['There is no Batch 6', 'No further Atomic Transition Evidence Derivation Remediation batch is authorized.', 'Required v3 execution admission remains `NOT_IMPLEMENTED`', '`UNKNOWN_REPLAY_PROHIBITED` remains binding'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function chain(): array
    {
        $cases = [];
        $results = [];
        foreach (CaseContract::KINDS as $index => $kind) {
            $case = $this->seal([
                'schema' => CaseContract::SCHEMA, 'case_id' => 'closure-case.'.strtolower($kind).'.1',
                'case_kind' => $kind, 'replay_contention_root' => 'closure-root.1',
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
        $secretService = new SecretService();
        $manifest = $builder->manifest('closure-manifest.1');
        $proof = $secretService->prove('closure-secret-proof.1', $results);
        $aggregate = $builder->build('closure-aggregate.1', 'closure-root.1', $cases, $results, $manifest, $proof);
        $terminal = (new Recomputer($builder, $secretService))->recompute('closure-terminal.1', $cases, $results, $manifest, $proof, $aggregate);

        return [$cases, $results, $manifest, $proof, $aggregate, $terminal];
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

    private function service(): ClosureService
    {
        return new ClosureService(new Recomputer(new Builder(), new SecretService()));
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
