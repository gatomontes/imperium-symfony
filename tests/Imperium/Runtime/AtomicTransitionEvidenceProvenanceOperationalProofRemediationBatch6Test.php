<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceIndependentReconstructionContract as Reconstruction;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceIndependentReconstructor as Reconstructor;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch6Test extends TestCase
{
    public function testRetainedMissionEvidenceReconstructsWithoutProducerConclusion(): void
    {
        $result = (new Reconstructor())->reconstruct('atomic-transition-independent-reconstruction.1', $this->evidence());

        self::assertSame(Reconstruction::REQUIRED_FIELDS, array_keys($result));
        self::assertSame(Reconstruction::STATUS, $result['status']);
        foreach ([
            'source_and_build_binding_reconstructed',
            'trusted_execution_binding_reconstructed',
            'acceptance_matrix_reconstructed',
            'complete_chain_exclusion_reconstructed',
            'non_authority_perimeter_reconstructed', 'read_only',
        ] as $derived) {
            self::assertTrue($result[$derived]);
        }
        foreach ([
            'producer_disposition_imported', 'historical_boolean_audit_accepted',
            'historical_self_recomputed_closure_accepted', 'runtime_state_written',
            'authority_issued_or_consumed', 'execution_admitted',
            'provider_binding_changed', 'credential_or_capability_handled',
            'provider_invoked', 'external_io_started', 'provider_effect_started',
            'continuing_authority', 'qualification_removed', 'campaign_closed',
        ] as $refusal) {
            self::assertFalse($result[$refusal]);
        }

        $source = $this->source('AtomicTransitionEvidenceIndependentReconstructor.php');
        self::assertStringNotContainsString("['disposition']", $source);
    }

    public function testAnyRetainedPackageOrBindingTamperFailsClosed(): void
    {
        $evidence = $this->evidence();
        $evidence['acceptance_matrix']['exact_replay'] = 'CALLER_SAYS_PROVED';

        $this->expectExceptionMessage('PBL1017_INDEPENDENT_RECONSTRUCTION_EVIDENCE_INVALID');
        (new Reconstructor())->reconstruct('atomic-transition-independent-reconstruction.1', $evidence);
    }

    public function testEveryHistoricalClosurePathIsDisabledAndContainerExcluded(): void
    {
        $audit = $this->source('ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService.php');
        $closure = $this->source('AtomicTransitionEvidenceCorrectedClosureService.php');
        self::assertStringContainsString('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED', $audit);
        self::assertStringContainsString('PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED', $closure);

        $services = (string) file_get_contents(dirname(__DIR__, 3).'/config/services.yaml');
        self::assertStringContainsString('ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService.php', $services);
        self::assertStringContainsString('AtomicTransitionEvidenceCorrectedClosureService.php', $services);
    }

    public function testBatchBoundaryPreservesQualificationAndAuthorizesOnlyTerminalAudit(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-6-independent-reconstruction.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-6-complete.md',
        );
        foreach ([
            'BATCH_6_INDEPENDENT_RECONSTRUCTION_COMPLETE_HISTORICAL_CLOSURE_PATHS_DISABLED',
            'imports no producer disposition', 'PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED',
            'PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 7',
            'may not run another mission', 'may not invoke a provider',
            'may not perform external I/O', 'may not handle a live credential or capability',
            'may not mutate runtime state', 'may not remove the closure qualification',
            'Estimated campaign countdown after Batch 6: one batch',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function evidence(): array
    {
        $json = (string) file_get_contents(
            dirname(__DIR__, 3).'/docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json',
        );

        return json_decode(ltrim($json, "\xEF\xBB\xBF"), true, 512, JSON_THROW_ON_ERROR);
    }

    private function source(string $file): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/'.$file);
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
