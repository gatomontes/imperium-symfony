<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch5Test extends TestCase
{
    public function testSanitizedIntegratedEvidenceIsSourceBoundAndNonAuthorizing(): void
    {
        $evidence = json_decode($this->document(
            'docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json',
        ), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(
            'imperium.sanitized-atomic-transition-integrated-disposable-mission-evidence/v1',
            $evidence['schema'],
        );
        self::assertSame('ATOMIC-TRANSITION-DISPOSABLE-PROOF-1', $evidence['mission_id']);
        self::assertSame('44b9e9151d522c3ff6ac82fe0166947c1d8d8377', $evidence['source_commit']);
        self::assertSame('8.4.14', $evidence['php_version']);
        self::assertSame([
            'interruption_before_journal' => 'ABSENT',
            'interruption_after_journal' => 'PREPARED',
            'interruption_after_winner' => 'COMMITTING',
            'interruption_after_receipt' => 'COMMITTED',
            'exact_replay' => 'EXACT_REPLAY',
            'changed_evidence' => 'CHANGED_EVIDENCE_REFUSED',
            'same_root_contention' => 'SAME_ROOT_CONTENTION_REFUSED',
            'partial_write' => 'INCOMPLETE',
        ], $evidence['acceptance_matrix']);
        self::assertTrue($evidence['complete_chain_content_exclusion_observed']);
        self::assertTrue($evidence['integrated_operational_receipt_created']);
        foreach ([
            'caller_result_accepted', 'provider_or_external_effect_authorized',
            'live_credential_or_capability_authorized', 'runtime_state_written',
            'continuing_authority',
        ] as $refusal) {
            self::assertFalse($evidence[$refusal]);
        }
        foreach ([
            'source_tree_digest', 'build_artifact_digest', 'dependency_lock_digest',
            'runner_digest', 'mission_implementation_digest', 'evidence_origin_digest',
            'execution_provenance_digest', 'trusted_result_digest',
            'dependency_graph_digest', 'private_receipt_digest', 'record_digest',
        ] as $digest) {
            self::assertMatchesRegularExpression('/\A[0-9a-f]{64}\z/', $evidence[$digest]);
        }
        self::assertSame('OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT', $evidence['private_receipt_retention']);
        self::assertSame('PROVED', $evidence['disposition']);
    }

    public function testBatchBoundaryPreservesQualificationAndAuthorizesOnlyBatch6(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-5-integrated-disposable-mission.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-5-complete.md',
        );
        foreach ([
            'BATCH_5_INTEGRATED_DISPOSABLE_INTERNAL_MISSION_OPERATIONAL_EVIDENCE_ACCEPTED',
            'private receipt remains operator-local and uncommitted',
            'does not independently reconstruct the private receipt',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 6',
            'without importing producer conclusions',
            'may not execute another mission, invoke a provider, perform external I/O',
            'handle a live credential or capability', 'mutate runtime state',
            'remove the closure qualification', 'Estimated campaign countdown after Batch 5: two batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        $document = (string) file_get_contents(dirname(__DIR__, 3).'/'.$path);

        return (string) preg_replace('/\s+/', ' ', ltrim($document, "\xEF\xBB\xBF"));
    }
}
