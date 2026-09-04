<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationCampaignReadyTest extends TestCase
{
    public function testReviewRetainsProvenanceButRefusesDerivationAndAtUseClosure(): void
    {
        $review = $this->read('docs/canonical-native-effect-reconciliation-authority-provenance-post-merge-blackquill-review-v1.md');
        foreach ([
            'SELF_SEALED_AUTHORITY_INGRESS_CORRECTED',
            'ROOT_PROVENANCE_JOIN_ACCEPTED',
            'FORMAL_CLOSURE_REFUSED_RECONCILIATION_DERIVATION_AUTHORITY_ABSENT',
            'REVOCATION_AT_CONSUMPTION_UNPROVED',
            'continuing_authority: false',
            'resolve -> revoke -> consume',
        ] as $finding) {
            self::assertStringContainsStringIgnoringCase($finding, $review, $finding);
        }
    }

    public function testCampaignHasSixSeparatelyBoundedStages(): void
    {
        $campaign = $this->read('docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md');
        foreach ([
            'Campaign countdown at selection: six stages including Preparation Batch 0',
            'Preparation Batch 0 — derivation authority and revocation-race inventory',
            'Batch 1 — issuance authority and at-use currentness contracts',
            'Batch 2 — rooted issuance decision, custody and atomic consumption',
            'Batch 3 — issuer enforcement and revocation-at-use integration',
            'Batch 4 — adversarial, application, concurrency and interruption proof',
            'Batch 5 — separately sequenced terminal audit',
        ] as $stage) {
            self::assertStringContainsStringIgnoringCase($stage, $campaign, $stage);
        }
    }

    public function testPreparationHardStopForbidsRuntimeAuthorityAndLiveEffects(): void
    {
        $documents = $this->read('docs/next-campaign-canonical-native-effect-reconciliation-issuance-authority-revocation-remediation.md')
            .$this->read('docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-campaign-ready.md')
            .$this->read('docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-local-ready.md');
        foreach ([
            'PREPARATION_BATCH_0_ONLY_HARD_STOP',
            'Do not create Batch 1 contracts/tests',
            'Do not modify production runtime behavior',
            'No later batch and no provider effect is authorized',
            'restore Batch 7',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $documents, $stop);
        }
    }

    public function testPromptAndCanonicalConsumersPublishTheOnlyEntrypoint(): void
    {
        $path = 'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach ([
            'git pull --ff-only origin main',
            'issue(admissionId, at, expiresAt)',
            'source provenance from derivation authorization',
            'resolve -> revoke -> consume race',
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $handoff, $boundary);
        }
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $consumer) {
            self::assertStringContainsString($path, $this->read($consumer), $consumer);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
