<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest extends TestCase
{
    private const string HISTORICAL_CAMPAIGN = 'docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md';
    private const string HISTORICAL_HANDOFF = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-campaign-ready.md';
    private const string HISTORICAL_ENTRYPOINT = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batches-2-through-5-local-ready.md';
    private const string CURRENT_CAMPAIGN = 'docs/next-campaign-canonical-native-effect-reconciliation-shared-exclusion-remediation.md';
    private const string CURRENT_ENTRYPOINT = 'docs/handoffs/canonical-native-effect-reconciliation-shared-exclusion-remediation-preparation-batch-0-local-ready.md';
    private const string LEDGER = 'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-reading-evidence-ledger-v1.json';

    public function testSupersededCampaignAndEntrypointAreNonExecutable(): void
    {
        foreach ([self::HISTORICAL_CAMPAIGN, self::HISTORICAL_HANDOFF, self::HISTORICAL_ENTRYPOINT] as $path) {
            $document = $this->read($path);
            self::assertStringContainsStringIgnoringCase('HISTORICAL', $document, $path);
            self::assertStringContainsString('CANDIDATE_RANGE_QUARANTINED_NOT_ACCEPTED', $document, $path);
            self::assertStringContainsStringIgnoringCase('DO NOT EXECUTE', $document, $path);
            self::assertStringContainsString(self::CURRENT_ENTRYPOINT, $document, $path);
        }
    }

    public function testReadingLedgerPublishesOnlyCorrectiveAuthority(): void
    {
        $ledger = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/'.self::LEDGER), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('HISTORICAL_ISSUANCE_CAMPAIGN_INDEX_QUARANTINED', $ledger['ledger_status']);
        self::assertSame(self::CURRENT_ENTRYPOINT, $ledger['current_authority_document']);
        self::assertFalse($ledger['quarantined_candidate']['publication_authorized']);
        self::assertFalse($ledger['quarantined_candidate']['implementation_accepted']);
        self::assertFalse($ledger['quarantined_candidate']['closure_accepted']);
        self::assertFalse($ledger['production_correction_authorized']);
        self::assertFalse($ledger['remote_publication_authorized']);
        self::assertFalse($ledger['batch_7_authorized']);
    }

    public function testCurrentConsumersPointToCorrectiveCampaign(): void
    {
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $path) {
            $document = $this->read($path);
            self::assertStringContainsString(self::CURRENT_CAMPAIGN, $document, $path);
            self::assertStringContainsString(self::CURRENT_ENTRYPOINT, $document, $path);
            self::assertStringNotContainsString(self::HISTORICAL_ENTRYPOINT, $document, $path);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
