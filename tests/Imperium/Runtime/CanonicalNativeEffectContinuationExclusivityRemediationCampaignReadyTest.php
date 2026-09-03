<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectContinuationExclusivityRemediationCampaignReadyTest extends TestCase
{
    public function testReviewRecordsAllMaterialRefusalsAndSuspendsBatchSeven(): void
    {
        $review = $this->read('docs/canonical-native-effect-corridor-post-batch-6-blackquill-review-v1.md');
        foreach ([
            'CANONICAL_NATIVE_EFFECT_CORRIDOR_BATCH_6_CLOSURE_REQUALIFIED_CONTINUATION_AND_EXCLUSIVITY_UNPROVED',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'uninterrupted same-process continuation is not enforced',
            'cross-authority effect-tuple exclusivity is absent',
            'caller-supplied authority semantics',
            '48,253 assertions',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $review, $boundary);
        }
    }

    public function testSixStagesSeparateInventoryContractsRuntimeProofAndAudit(): void
    {
        $campaign = $this->read('docs/next-campaign-canonical-native-effect-continuation-exclusivity-remediation.md');
        foreach ([
            'Campaign countdown at selection: six stages including Preparation Batch 0',
            'Preparation Batch 0 — continuation, tuple and evidence inventory',
            'Batch 1 — corrected contracts and identities',
            'Batch 2 — atomic tuple winner and continuation custody',
            'Batch 3 — admission-derived continuation and receipt binding',
            'Batch 4 — adversarial process, contention and substitution proof',
            'Batch 5 — evidence reconciliation and terminal Blackquill audit',
        ] as $stage) {
            self::assertStringContainsString($stage, $campaign, $stage);
        }
    }

    public function testPreparationOnlyAndLiveEffectStopsAreExplicit(): void
    {
        $documents = $this->read('docs/next-campaign-canonical-native-effect-continuation-exclusivity-remediation.md')
            .$this->read('docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-campaign-ready.md')
            .$this->read('docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-preparation-batch-0-local-ready.md');
        foreach ([
            'PREPARATION_BATCH_0_AUTHORIZED_ONLY',
            'Do not implement the correction',
            'No later batch and no provider effect is authorized',
            'restore Batch 7',
            'provider invocation',
            'network/external I/O',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $documents, $stop);
        }
    }

    public function testLocalPromptAndCanonicalConsumersPublishTheNewEntrypoint(): void
    {
        $path = 'docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach ([
            'git pull --ff-only origin main',
            'CanonicalNativeEffectContinuationExclusivityRemediationCampaignReadyTest.php',
            'PREPARATION_BATCH_0_COMPLETE_CONTINUATION_EXCLUSIVITY_GAPS_CLASSIFIED',
            'New-chat prompt',
            'distinct authorities targeting the same tuple',
            'fresh-process first-continuation',
        ] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
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
