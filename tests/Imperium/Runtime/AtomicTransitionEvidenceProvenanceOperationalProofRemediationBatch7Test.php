<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceAuthenticatedClosureContract as Closure;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceIndependentReconstructor as Reconstructor;
use App\Imperium\Runtime\Imperator\AtomicTransitionEvidenceTerminalAdversarialAuditor as Auditor;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceProvenanceOperationalProofRemediationBatch7Test extends TestCase
{
    public function testAuthenticatedOperationalEvidenceSurvivesTerminalAuditAndClosesCampaign(): void
    {
        $evidence = $this->evidence();
        $reconstruction = (new Reconstructor())->reconstruct(
            'atomic-transition-independent-reconstruction.1',
            $evidence,
        );
        $this->expectExceptionMessage('PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED');
        $this->auditor()->close('atomic-transition-authenticated-operational-closure.1', $evidence, $reconstruction);
    }

    public function testTamperedPackageAndCounterfeitReconstructionFailClosed(): void
    {
        $evidence = $this->evidence();
        $reconstruction = (new Reconstructor())->reconstruct(
            'atomic-transition-independent-reconstruction.1',
            $evidence,
        );
        $this->expectExceptionMessage('PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED');
        $this->auditor()->close('closure.1', $evidence, $reconstruction);
    }

    public function testHistoricalClosurePathsRemainRefusingAndUnregistered(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService.php');
        $closure = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceCorrectedClosureService.php');
        $services = (string) file_get_contents($root.'/config/services.yaml');
        self::assertStringContainsString('PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED', $audit);
        self::assertStringContainsString('PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED', $closure);
        self::assertStringContainsString('ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService.php', $services);
        self::assertStringContainsString('AtomicTransitionEvidenceCorrectedClosureService.php', $services);

        $auditor = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceTerminalAdversarialAuditor.php');
        self::assertStringNotContainsString('AtomicTransitionEvidenceCorrectedClosureService', $auditor);
        self::assertStringNotContainsString('ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService', $auditor);
    }

    public function testTerminalDocumentationClosesOnlyTheEvidenceCampaign(): void
    {
        $document = $this->document(
            'docs/atomic-transition-evidence-provenance-operational-proof-remediation-batch-7-terminal-audit.md',
        );
        $handoff = $this->document(
            'docs/handoffs/atomic-transition-evidence-provenance-operational-proof-remediation-complete.md',
        );
        foreach ([
            'BATCH_7_TERMINAL_ADVERSARIAL_AUDIT_PASSED',
            'CAMPAIGN_CLOSURE_ACCEPTED_AFTER_AUTHENTICATED_OPERATIONAL_EVIDENCE_PROOF',
            'producer disposition is not imported',
            'historical caller-boolean audit remains disabled',
            'historical self-recomputed closure remains disabled',
        ] as $finding) {
            self::assertStringContainsString($finding, $document);
        }
        foreach ([
            'There is no Batch 8', 'No further Atomic Transition Evidence Provenance and Operational Proof Remediation batch is authorized.',
            'Provider binding remains `BOUND_INACTIVE`',
            'Required v3 execution admission remains `NOT_IMPLEMENTED`',
            '`UNKNOWN_REPLAY_PROHIBITED` remains binding',
            'does not authorize a second mission', 'does not authorize provider invocation',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function auditor(): Auditor
    {
        return new Auditor(new Reconstructor());
    }

    private function evidence(): array
    {
        $json = (string) file_get_contents(
            dirname(__DIR__, 3).'/docs/evidence/atomic-transition-integrated-disposable-proof-1-sanitized.json',
        );

        return json_decode(ltrim($json, "\xEF\xBB\xBF"), true, 512, JSON_THROW_ON_ERROR);
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
