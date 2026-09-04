<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationCampaignCompleteTest extends TestCase
{
    public function testTerminalAuditAndHandoffCarryTheBoundedVerdict(): void
    {
        $docs = $this->read('docs/canonical-native-effect-reconciliation-authority-provenance-remediation-terminal-audit-v1.md')
            .$this->read('docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-campaign-complete.md');
        foreach (['CANONICAL_NATIVE_EFFECT_RECONCILIATION_AUTHORITY_PROVENANCE_REMEDIATION_COMPLETE', 'RECONCILIATION_AUTHORITY_PROVENANCE_ACCEPTED_BOUNDED_NO_LIVE_EFFECT', 'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', '98f9777959efa279aa6f93e0e240fe861409cef1', '33874716024', '2480 / 51049'] as $marker) {
            self::assertStringContainsString($marker, $docs, $marker);
        }
    }

    public function testEvidenceLedgerBindsSuccessfulCiToTheExactCandidateSha(): void
    {
        $ledger = json_decode($this->read('docs/canonical-native-effect-reconciliation-authority-provenance-remediation-evidence-ledger-v2.json'), true, 32, JSON_THROW_ON_ERROR);
        self::assertSame('98f9777959efa279aa6f93e0e240fe861409cef1', $ledger['external_ci']['head_sha']);
        self::assertSame(33874716024, $ledger['external_ci']['run_id']);
        self::assertSame(101028835208, $ledger['external_ci']['job_id']);
        self::assertSame('success', $ledger['external_ci']['conclusion']);
        self::assertSame(2480, $ledger['external_ci']['tests']);
        self::assertSame(51049, $ledger['external_ci']['assertions']);
        self::assertSame(0, $ledger['remaining_campaign_stages']);
        self::assertFalse($ledger['provider_effect_performed']);
        self::assertFalse($ledger['credential_accessed']);
        self::assertFalse($ledger['batch_7_authorized']);
    }

    public function testCanonicalConsumersNoLongerPresentTheProvenanceGapAsCurrent(): void
    {
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $path) {
            $document = $this->read($path);
            $start = strpos($document, 'RECONCILIATION_AUTHORITY_PROVENANCE_REMEDIATION_COMPLETE');
            self::assertNotFalse($start, $path);
            $historicalCompletion = substr($document, $start, 1800);
            self::assertStringContainsString('RECONCILIATION_AUTHORITY_PROVENANCE', $historicalCompletion, $path);
            self::assertStringContainsString('COMPLETE', $historicalCompletion, $path);
            self::assertStringNotContainsString('FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT', $historicalCompletion, $path);
        }
    }

    public function testClosureDocumentsDoNotAuthorizeAProviderOrBatchSeven(): void
    {
        $handoff = $this->read('docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-campaign-complete.md');
        foreach (['authorizes no', 'credential', 'provider effect', 'mission', 'email', 'Iron Gate', 'Lazaretto', 'Batch 7'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $handoff, $boundary);
        }
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
