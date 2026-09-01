<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationPreparationBatch0Test extends TestCase
{
    public function testCounterfeitabilityAndProvenanceGapsAreExplicit(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-provenance-operational-proof-remediation-preparation-inventory.md');
        foreach ([
            'Case input', 'Result input', 'Reference integrity', 'Producer identity',
            'Execution identity', 'Source and build identity', 'Mission root',
            'Terminal recomputation', 'Corrected closure', 'Focused acceptance test',
            '`EXISTS_COUNTERFEITABLE`', '`EXISTS_FRAGMENTED`', '`ABSENT`',
            'No caller-supplied result, execution boolean, finding boolean, match boolean',
            'never call `AtomicTransitionEvidenceDeterministicCaseExecutor`',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testTrustedBoundaryOriginSchemaAndCapabilityDerivationAreDefined(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-provenance-operational-proof-remediation-preparation-inventory.md');
        foreach ([
            'Smallest trusted proof boundary', 'Evidence-origin schema',
            '`atomic-transition-evidence-origin/v1`', 'disposable-mission authorization',
            'source commit', 'source tree digest', 'build artifact digest',
            'dependency-lock digest', 'executor implementation digest', 'case-set root',
            'case-set and sanitized package', 'Recursive dependency traversal',
            'Container-resolved dependency graph', 'Transitive capability classification',
            'Unknown/substituted dependency refusal', 'actual resolved executor graph',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $inventory);
        }
    }

    public function testCompleteChainSecretGapsAndHistoricalReachabilityAreClassified(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-provenance-operational-proof-remediation-preparation-inventory.md');
        foreach ([
            'Complete-chain secret and capability exclusion gaps',
            'current proof scans only supplied result records',
            'evidence origin, mission authorization, dossier, provenance',
            'one strict Base64 decoding layer',
            'Historical audit-service reachability',
            '`ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService`',
            '`REACHABLE_INTERNAL_UNSUBORDINATED`', '`NO_PRODUCTION_CONSUMER_PROVED`',
            '`NOT_REPAIRED`', 'remains an explicit Batch 6 disposition gate',
        ] as $finding) {
            self::assertStringContainsString($finding, $inventory);
        }
    }

    public function testDisposableMissionMatrixDesignsLaterAcceptanceWithoutAuthorizingIt(): void
    {
        $inventory = $this->document('docs/atomic-transition-evidence-provenance-operational-proof-remediation-preparation-inventory.md');
        foreach ([
            'This matrix designs later acceptance; it authorizes no mission now.',
            'Exact bounded run', 'Interruption cuts', 'Exact replay', 'Changed evidence',
            'Same-root contention', 'Partial write', 'Tamper', 'Secret injection',
            'Executor substitution', 'Provider/effect reachability',
            'Passing tests against hand-assembled arrays is insufficient.',
        ] as $case) {
            self::assertStringContainsString($case, $inventory);
        }
    }

    public function testHandoffAuthorizesContractsOnlyAndPreservesQualification(): void
    {
        $handoff = $this->document('docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-preparation-batch-0-complete.md');
        foreach ([
            'Only Atomic Transition Evidence Provenance and Operational Proof Remediation Batch 1 separately versioned execution-provenance and evidence-origin contracts with pure validation may next be considered.',
            'may not implement or invoke a trusted executor',
            'may not execute a case or mission, produce an operational receipt',
            'repair or disable the historical audit', 'remove the closure qualification',
            'may not mutate runtime state',
            'may not handle a live credential or capability', 'invoke a provider',
            'perform external I/O', 'open Iron Gate or Lazaretto',
            '`CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_EVIDENCE_PROVENANCE_DEFECT`',
            'Estimated campaign countdown after Preparation Batch 0: seven batches',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
