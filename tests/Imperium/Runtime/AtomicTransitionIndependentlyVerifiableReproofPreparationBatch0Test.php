<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

/** Documentary checks only: no runtime, mission, verifier or signing class is loaded. */
final class AtomicTransitionIndependentlyVerifiableReproofPreparationBatch0Test extends TestCase
{
    private const string INVENTORY = 'docs/atomic-transition-independently-verifiable-reproof-preparation-inventory-v1.md';
    private const string HANDOFF = 'docs/handoffs/atomic-transition-independently-verifiable-reproof-preparation-batch-0-complete.md';
    private const string RESULT = 'PREPARATION_BATCH_0_COMPLETE_INDEPENDENTLY_VERIFIABLE_REPROOF_BOUNDARY_CLASSIFIED';
    private const string QUALIFICATION = 'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT';

    public function testEightCaseInventoryAccountsForEveryRetainedV1LabelWithoutInventingEvidence(): void
    {
        $summary = json_decode($this->read('docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertArrayNotHasKey('acceptance_case_evidence_digest', $summary);
        self::assertSame('OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT', $summary['private_receipt_retention']);
        $lines = preg_split('/\r?\n/', trim($this->read('docs/atomic-transition-independently-verifiable-reproof-preparation-cases-v1.tsv')));
        self::assertSame("case_id\tv1_outcome\tmissing_inputs\tmissing_expectations\tmissing_observations\tretention\tstatus", array_shift($lines));
        self::assertCount(8, $lines);
        $inventory = [];
        foreach ($lines as $line) {
            $fields = explode("\t", $line);
            self::assertCount(7, $fields);
            [$id, $outcome, $inputs, $expectations, $observations, $retention, $status] = $fields;
            self::assertArrayNotHasKey($id, $inventory);
            foreach ([$inputs, $expectations, $observations] as $description) {
                self::assertGreaterThan(25, strlen($description));
            }
            self::assertSame('OPERATOR_LOCAL_PAYLOAD_PUBLIC_DIGESTS_ONLY', $retention);
            self::assertSame('NOT_RETAINED_IN_V1_MATRIX_NOT_EXECUTED_IN_BATCH_0', $status);
            $inventory[$id] = $outcome;
        }
        self::assertSame($summary['acceptance_matrix'], $inventory);
    }

    public function testInventoryClassifiesAllRequiredBoundariesAndSourceEvidence(): void
    {
        $inventory = $this->read(self::INVENTORY);
        foreach ([
            'Missing acceptance-case evidence', 'Current schemas and proof/verifier coupling',
            'Public, operator-local and forbidden evidence', 'Provenance derivation and acyclic binding',
            'Persistence, interruption and replay boundaries', 'Execution, receipt and signing custody',
            'Closure consumers and historical bypasses', 'Smallest ordered v2 sequence and Batch 1 boundary',
            'SOURCE_OBSERVED', 'PROPOSED_V2_REQUIREMENT', 'UNKNOWN_NOT_INSPECTED',
            'PUBLIC_REPOSITORY', 'OPERATOR_LOCAL_ONLY', 'FORBIDDEN_EVIDENCE_PAYLOAD', 'SEPARATE_SIGNING_CUSTODY',
            'single', 'acceptanceMatrix()', 'placeholder roots', 'LOCK_EX', 'same_root_contention',
            'passingReport()', 'source_and_build', 'receipt_structure', 'origin_and_provenance',
            'trusted_result', 'dependency_graph', 'acceptance_matrix', 'complete_chain_exclusion',
            'non_authority_perimeter',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $inventory, $boundary);
        }
        // Every explicitly required public source is traceable in the reading ledger.
        $ready = $this->read('docs/handoffs/atomic-transition-independently-verifiable-reproof-campaign-ready.md');
        preg_match_all('/^\d+\. `([^`]+)`/m', $ready, $matches);
        self::assertCount(15, $matches[1]);
        foreach ($matches[1] as $source) {
            self::assertStringContainsString('`'.$source.'`', $inventory, $source);
        }
    }

    public function testCurrentSourceExplainsTheGapWithoutExecutingItsMethods(): void
    {
        $runner = $this->read('tools/run-atomic-transition-integrated-mission.php');
        self::assertSame(1, substr_count($runner, '$corridor->executeCase('));
        self::assertStringContainsString('$matrix = $this->acceptanceMatrix($classifier);', $runner);
        self::assertStringContainsString("'fixture_kind' => 'EMPTY'", $runner);
        self::assertStringNotContainsString('acceptance_case_evidence_digest', $runner);
        $verifier = $this->read('src/IndependentVerification/AtomicTransitionArtifactAndReceiptVerifier.php');
        self::assertStringContainsString("\$domains['acceptance_matrix'] = 'INDETERMINATE';", $verifier);
        $admission = $this->read('src/IndependentVerification/AtomicTransitionIndependentVerificationAdmissionConsumer.php');
        foreach (["'qualification_removed' => false", "'campaign_closed' => false", 'INDEPENDENT_VERIFICATION_ADMITTED_PENDING_TERMINAL_AUDIT'] as $boundary) {
            self::assertStringContainsString($boundary, $admission);
        }
        $services = $this->read('config/services.yaml');
        foreach ([
            'ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService' => 'PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED',
            'AtomicTransitionEvidenceCorrectedClosureService' => 'PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED',
            'AtomicTransitionEvidenceTerminalAdversarialAuditor' => 'PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED',
        ] as $class => $refusal) {
            self::assertStringContainsString($refusal, $this->read('src/Imperium/Runtime/Imperator/'.$class.'.php'));
            self::assertStringContainsString("- '../src/Imperium/Runtime/Imperator/".$class.".php'", $services);
        }
        self::assertStringContainsString("- '../src/IndependentVerification/AtomicTransitionIndependentVerificationAdmissionConsumer.php'", $services);
    }

    public function testHandoffPreservesApprovedBaselineAndStopsBeforeImplementation(): void
    {
        $handoff = $this->normalized(self::HANDOFF);
        foreach ([
            self::RESULT, self::QUALIFICATION, '3c4f8b2328570bdd0467463204301cddca99007a',
            'ancestry check failed', 'Only Batch 1 authority-empty contracts may next be considered under a new instruction',
            'No Batch 1 work is performed or implicitly authorized',
            'Batch 5 execution', 'Batch 6 verification/signing', 'Batch 8 terminal audit',
            'merged Batch 7 main', 'separately authorized and separately sequenced',
            'CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT',
            'UNKNOWN_NOT_INSPECTED', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED',
            'Do not inspect private operator-local material', 'implement v2 contracts or runtime behavior',
            'execute a mission or verifier', 'create or use signing material', 'invoke a provider',
            'perform external I/O', 'handle a live credential or capability', 'mutate runtime state',
            'repair or replace v1 evidence', 'admit v2 evidence', 'remove the qualification',
            'close the campaign', 'The campaign remains open',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
        self::assertStringNotContainsString('CAMPAIGN_CLOSURE_ACCEPTED_AFTER_INDEPENDENTLY_ATTESTED_REPROOF', $handoff);
    }

    public function testCampaignFlowIndexAndLedgerRetainTheOrderedRemainingStages(): void
    {
        foreach ([
            'docs/next-campaign-atomic-transition-independently-verifiable-reproof.md',
            'docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md',
        ] as $path) {
            $document = $this->normalized($path);
            foreach ([self::RESULT, self::QUALIFICATION, self::HANDOFF, 'Eight stages remain', 'campaign remains open'] as $boundary) {
                self::assertStringContainsString($boundary, $document, $path.': '.$boundary);
            }
        }
        $inventory = $this->read(self::INVENTORY);
        preg_match_all('/^\| Batch ([1-8]) \|/m', $inventory, $matches);
        self::assertSame(['1', '2', '3', '4', '5', '6', '7', '8'], $matches[1]);
        $ledger = $this->read('todo/blackquill-todos.md');
        self::assertStringContainsString('- [x] Preparation Batch 0: inventory all eight missing case chains', $ledger);
        for ($batch = 1; $batch <= 8; ++$batch) {
            self::assertStringContainsString('- [ ] Batch '.$batch.':', $ledger);
        }
    }

    private function read(string $path): string
    {
        $bytes = file_get_contents(dirname(__DIR__, 3).'/'.$path);
        self::assertNotFalse($bytes, $path);

        return $bytes;
    }

    private function normalized(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', $this->read($path));
    }
}
