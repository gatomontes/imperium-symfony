<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest extends TestCase
{
    private const string CAMPAIGN = 'docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md';
    private const string CURRENT = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batches-2-through-5-local-ready.md';
    private const string BATCH_ONE = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-complete.md';
    private const string OLD_LOCAL = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-local-ready.md';
    private const string PREPARATION_LOCAL = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-local-ready.md';

    public function testCurrentEntrypointExplicitlyAuthorizesOnlyRemainingBoundedCampaign(): void
    {
        $documents = $this->read(self::CAMPAIGN).$this->read(self::CURRENT);
        foreach ([
            'BATCHES_2_THROUGH_5_EXPLICITLY_AUTHORIZED_FOR_SEQUENTIAL_LOCAL_EXECUTION',
            'SEPARATE_COMMIT_AND_TEST_GATE_PER_BATCH_REQUIRED',
            'REMOTE_PUBLICATION_REQUIRES_SEPARATE_REVIEW',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'LOCAL_RECONCILIATION_ISSUANCE_CAMPAIGN_CANDIDATE_COMPLETE_PENDING_REMOTE_REVIEW',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $documents, $boundary);
        }
    }

    public function testPromptDefinesAllFourSequentialStages(): void
    {
        $prompt = $this->read(self::CURRENT);
        self::assertStringNotContainsString('BATCH_2_NOT_AUTHORIZED', $this->read(self::CAMPAIGN));
        self::assertStringContainsStringIgnoringCase('clean committed Batch 4 local candidate', $this->read(self::CAMPAIGN));

        foreach ([
            'Batch 2 — rooted issuance decision, custody and atomic publication',
            'BATCH_2_COMPLETE_ROOTED_DECISION_CUSTODY_AND_ATOMIC_PUBLICATION',
            'Batch 3 — issuer enforcement and revocation at use',
            'BATCH_3_COMPLETE_TYPED_ISSUER_AND_AT_USE_CURRENTNESS',
            'Batch 4 — adversarial, application, concurrency and interruption proof',
            'BATCH_4_COMPLETE_ADVERSARIAL_APPLICATION_AND_INTERRUPTION_PROOF',
            'Batch 5 — separately sequenced terminal Blackquill audit',
            'one ordered commit SHA per batch',
        ] as $stage) {
            self::assertStringContainsStringIgnoringCase($stage, $prompt, $stage);
        }
    }

    public function testPromptRejectsHistoricalReuseRemotePublicationAndLiveEffects(): void
    {
        $prompt = $this->read(self::CURRENT);
        foreach ([
            'Do not cherry-pick, restore or copy',
            'do not push any branch',
            'do not represent local tests as GitHub CI',
            'do not access credentials or providers',
            'do not perform network/external I/O',
            'do not open Iron Gate or Lazaretto',
            'do not repair the untimestamped Operator Root history limitation',
            'do not restore or authorize Batch 7',
            'do not invent authority',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $prompt, $stop);
        }
    }

    public function testCurrentConsumersPublishRemainingCampaignEntrypoint(): void
    {
        foreach ([
            'docs/delegate-mission-flow.md',
            'docs/handoffs/README.md',
            'todo/blackquill-todos.md',
            self::CAMPAIGN,
        ] as $consumer) {
            $document = $this->read($consumer);
            self::assertStringContainsString(self::CURRENT, $document, $consumer);
            self::assertStringNotContainsString(self::OLD_LOCAL, $document, $consumer);
        }

        self::assertStringContainsString('BATCH_2_NOT_AUTHORIZED', $this->read(self::BATCH_ONE));
        self::assertStringContainsString(self::OLD_LOCAL, $this->read(self::PREPARATION_LOCAL));
    }

    public function testPromptProvidesPowerShellGuardsAndLocalOnlyCompletionMarker(): void
    {
        $prompt = $this->read(self::CURRENT);
        foreach ([
            'git checkout main',
            'git pull --ff-only origin main',
            'git status --short',
            'git rev-parse HEAD',
            'git switch -c codex/reconciliation-issuance-batches-2-through-5-local',
            'php vendor/bin/phpunit',
            'If a gate fails, authority does not advance',
            'Do not push',
        ] as $instruction) {
            self::assertStringContainsStringIgnoringCase($instruction, $prompt, $instruction);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
