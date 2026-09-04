<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest extends TestCase
{
    private const string CAMPAIGN = 'docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md';
    private const string READY = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-campaign-ready.md';
    private const string CURRENT = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-local-ready.md';
    private const string COMPLETE = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-5-local-complete-ci-pending.md';
    private const string HISTORICAL = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-local-ready.md';

    public function testCorrectedPreparationAuthorizesOnlyBatchOneContracts(): void
    {
        $documents = $this->read(self::CAMPAIGN).$this->read(self::READY).$this->read(self::CURRENT);
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED',
            'BATCH_1_CONTRACTS_ONLY_AUTHORIZED',
            'BATCH_2_NOT_AUTHORIZED',
            'Five stages remain',
            'authority-empty',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $documents, $boundary);
        }
    }

    public function testBatchOnePromptDefinesEveryRequiredContractDistinction(): void
    {
        $prompt = $this->read(self::CURRENT);
        foreach ([
            'issuance decision',
            'single-purpose, single-use issuance authority',
            'non-serializable process-local typed custody',
            'atomic consumption/publication semantics',
            'present-tense Root/native/source currentness',
            'continuing_authority: false',
            'missing, counterfeit, expired, replayed, substituted, consumed, stale, revoked, suspended, superseded, retired, migration-required and conflicted',
            'RR07-RR10 require distinct `SUSPEND`, `SUPERSEDE`, `REVOKE`, `EXPIRE`, `RETIRE` and v3 migration/currentness refusal outcomes',
            'current untimestamped Operator Root revocation',
            'timestamped native/source lifecycle history',
            'RR02, RR05 and RR11',
            'CUR08A',
            'CUR08B',
        ] as $contract) {
            self::assertStringContainsStringIgnoringCase($contract, $prompt, $contract);
        }
    }

    public function testBatchOneHardStopRejectsImplementationAndEffects(): void
    {
        $prompt = $this->read(self::CURRENT);
        foreach ([
            'Do not modify any existing production issuer',
            'Do not wire a new service',
            'Do not create or consume a real issuance decision',
            'Do not mutate runtime state',
            'Do not claim GitHub CI until the exact SHA has actually passed it',
            'Batch 2 is not authorized',
            'No shorthand continuation language',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $prompt, $stop);
        }
    }

    public function testCurrentConsumersPublishLatestCompletionAndRetireLocalEntrypoints(): void
    {
        foreach ([
            'docs/delegate-mission-flow.md',
            'docs/handoffs/README.md',
            'todo/blackquill-todos.md',
            self::CAMPAIGN,
        ] as $consumer) {
            $document = $this->read($consumer);
            self::assertStringContainsString(self::COMPLETE, $document, $consumer);
            self::assertStringNotContainsString(self::CURRENT, $document, $consumer);
        }

        $historical = $this->read(self::HISTORICAL);
        self::assertStringContainsStringIgnoringCase('Historical entrypoint', $historical);
        self::assertStringContainsString(self::CURRENT, $historical);
        self::assertStringContainsString(self::CURRENT, $this->read(self::READY));
    }

    public function testPromptProvidesPowerShellSynchronizationAndCompletionMarker(): void
    {
        $prompt = $this->read(self::CURRENT);
        foreach ([
            'git checkout main',
            'git pull --ff-only origin main',
            'git status --short',
            'php vendor/bin/phpunit',
            'BATCH_1_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_CURRENTNESS_CONTRACTS_DEFINED',
            'four remaining stages',
        ] as $instruction) {
            self::assertStringContainsStringIgnoringCase($instruction, $prompt, $instruction);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
