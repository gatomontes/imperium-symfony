<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationCampaignReadyTest extends TestCase
{
    public function testReviewRetainsCorrectionsButRefusesProcessCustodyAndClosure(): void
    {
        $review = $this->read('docs/canonical-native-effect-continuation-exclusivity-post-merge-blackquill-review-v2.md');
        foreach ([
            'CONTINUATION_EXCLUSIVITY_REMEDIATION_FORMAL_CLOSURE_REFUSED_PROCESS_CUSTODY_UNPROVED',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'process-local custody is not process-bound',
            'forward completion lacks its own governed boundary',
            'required sequencing and independent verification absent',
            'canonical steps and flow were not advanced',
        ] as $finding) {
            self::assertStringContainsStringIgnoringCase($finding, $review, $finding);
        }
    }

    public function testSixStagesSeparateInventoryRuntimeProofAndIndependentAudit(): void
    {
        $campaign = $this->read('docs/next-campaign-canonical-native-effect-process-custody-formal-closure-remediation.md');
        foreach ([
            'Campaign countdown at selection: six stages including Preparation Batch 0',
            'Preparation Batch 0 — process, serialization, recovery and provenance inventory',
            'Batch 1 — process-incarnation and recovery contracts',
            'Batch 2 — process-bound custody implementation',
            'Batch 3 — execution/reconciliation separation',
            'Batch 4 — adversarial/application proof',
            'Batch 5 — separately sequenced terminal audit',
        ] as $stage) {
            self::assertStringContainsStringIgnoringCase($stage, $campaign, $stage);
        }
    }

    public function testPreparationIsAHardStopAgainstBatchOneAndLiveEffects(): void
    {
        $documents = $this->read('docs/next-campaign-canonical-native-effect-process-custody-formal-closure-remediation.md')
            .$this->read('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-campaign-ready.md')
            .$this->read('docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-preparation-batch-0-local-ready.md');
        foreach ([
            'PREPARATION_BATCH_0_ONLY_HARD_STOP',
            'Creating or modifying runtime code',
            'authority breach',
            'Do not create Batch 1 contracts',
            'No later batch and no provider effect is authorized',
            'restore Batch 7',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $documents, $stop);
        }
    }

    public function testLocalPromptAndCanonicalConsumersPublishTheEntrypoint(): void
    {
        $path = 'docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach ([
            'git pull --ff-only origin main',
            'CanonicalNativeEffectProcessCustodyFormalClosureRemediationCampaignReadyTest.php',
            'serialization/unserialization',
            'fork/inherited-memory',
            'PREPARATION_BATCH_0_COMPLETE_PROCESS_CUSTODY_AND_FORMAL_CLOSURE_GAPS_CLASSIFIED',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $handoff, $boundary);
        }
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $consumer) {
            self::assertStringContainsString($path, $this->read($consumer), $consumer);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
