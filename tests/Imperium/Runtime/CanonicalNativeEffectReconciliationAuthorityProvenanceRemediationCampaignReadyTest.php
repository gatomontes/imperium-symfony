<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationCampaignReadyTest extends TestCase
{
    public function testReviewAcceptsCustodyButRefusesForgeableRecoveryAuthority(): void
    {
        $review = $this->read('docs/canonical-native-effect-process-custody-post-merge-blackquill-review-v1.md');
        foreach ([
            'PROCESS_CUSTODY_CORRECTION_ACCEPTED',
            'FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT',
            'caller-supplied authority array',
            'public deterministic digest',
            'self-authored prose into durable authority',
        ] as $finding) {
            self::assertStringContainsStringIgnoringCase($finding, $review, $finding);
        }
    }

    public function testCampaignHasSixSeparatelyBoundedStages(): void
    {
        $campaign = $this->read('docs/next-campaign-canonical-native-effect-reconciliation-authority-provenance-remediation.md');
        foreach ([
            'Campaign countdown at selection: six stages including Preparation Batch 0',
            'Preparation Batch 0 — authority provenance and bypass inventory',
            'Batch 1 — canonical issuance and custody contracts',
            'Batch 2 — Root-provenanced issuance and atomic custody',
            'Batch 3 — recovery admission replacement and corridor integration',
            'Batch 4 — adversarial, application and process-loss proof',
            'Batch 5 — separately sequenced terminal audit',
        ] as $stage) {
            self::assertStringContainsStringIgnoringCase($stage, $campaign, $stage);
        }
    }

    public function testPreparationHardStopForbidsRuntimeAuthorityAndLiveEffects(): void
    {
        $documents = $this->read('docs/next-campaign-canonical-native-effect-reconciliation-authority-provenance-remediation.md')
            .$this->read('docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-campaign-ready.md')
            .$this->read('docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-preparation-batch-0-local-ready.md');
        foreach ([
            'PREPARATION_BATCH_0_ONLY_HARD_STOP',
            'Creating Batch 1 contracts',
            'authority breach',
            'Do not modify production runtime behavior',
            'No later batch and no provider effect is authorized',
            'restore Batch 7',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $documents, $stop);
        }
    }

    public function testHistoricalPromptIsPreservedAndCanonicalConsumersPublishCompletion(): void
    {
        $path = 'docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach ([
            'git pull --ff-only origin main',
            'self-sealed-array counterexample',
            'digest integrity from authenticated issuance',
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_AUTHORITY_PROVENANCE_GAPS_CLASSIFIED',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $handoff, $boundary);
        }
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $consumer) {
            $current = $this->read($consumer);
            self::assertStringContainsString('CANONICAL_NATIVE_EFFECT_RECONCILIATION_AUTHORITY_PROVENANCE_REMEDIATION_COMPLETE', $current, $consumer);
            self::assertStringContainsString('RECONCILIATION_AUTHORITY_PROVENANCE_ACCEPTED_BOUNDED_NO_LIVE_EFFECT', $current, $consumer);
            self::assertStringNotContainsString('FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT', substr($current, 0, 3000), $consumer);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
