<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationCampaignReadyTest extends TestCase
{
    private const string REVIEW = 'docs/canonical-native-effect-reconciliation-shared-exclusion-post-publication-blackquill-review-v1.md';
    private const string CAMPAIGN = 'docs/next-campaign-canonical-native-effect-reconciliation-shared-exclusion-remediation.md';
    private const string ENTRYPOINT = 'docs/handoffs/canonical-native-effect-reconciliation-shared-exclusion-remediation-preparation-batch-0-local-ready.md';

    public function testReviewPinsBoundaryBreachAndBothSharedExclusionFindings(): void
    {
        $review = $this->read(self::REVIEW);
        foreach ([
            'REMOTE_PUBLICATION_BOUNDARY_BREACHED',
            'CANDIDATE_RANGE_QUARANTINED_NOT_ACCEPTED',
            'DECISION_PUBLICATION_CURRENTNESS_RACE_UNRESOLVED',
            'AT_USE_SHARED_EXCLUSION_UNPROVED',
            '33911762126',
            '2607 tests / 52024 assertions',
        ] as $finding) {
            self::assertStringContainsStringIgnoringCase($finding, $review, $finding);
        }
    }

    public function testCampaignDefinesSixStageRepairWithoutAuthorizingImplementation(): void
    {
        $campaign = $this->read(self::CAMPAIGN);
        foreach ([
            'Campaign countdown: six stages',
            'Preparation Batch 0 — lock and interleaving inventory',
            'Batch 1 — canonical shared-exclusion and lock-order contract',
            'Batch 2 — decision publication correction',
            'Batch 3 — issuance and claim at-use correction',
            'Batch 4 — adversarial concurrency, interruption and platform proof',
            'Batch 5 — separately sequenced terminal audit',
            'PRODUCTION_CORRECTION_NOT_AUTHORIZED',
        ] as $stage) {
            self::assertStringContainsStringIgnoringCase($stage, $campaign, $stage);
        }
    }

    public function testPromptRequiresExactRaceInventoryAndClassifications(): void
    {
        $prompt = $this->read(self::ENTRYPOINT);
        foreach ([
            'DP01',
            'IU01',
            'CU01',
            'SHARED_EXCLUSION_PROVED',
            'DISJOINT_LOCK_RACE_REPRODUCED',
            'ORDERING_HAZARD',
            'EXISTS_SEQUENTIAL_ONLY',
            'DEFERRED_BOUNDARY',
            'real native-state mutation writers',
            'string assertion is inventory evidence only',
            'Sequential “revoke then use” tests do not prove the interleaving',
        ] as $requirement) {
            self::assertStringContainsStringIgnoringCase($requirement, $prompt, $requirement);
        }
    }

    public function testPromptEnforcesPreparationOnlyAndNoRemotePublication(): void
    {
        $prompt = $this->read(self::ENTRYPOINT);
        foreach ([
            'Do not modify production issuer',
            'Do not implement the correction',
            'Do not access credentials or providers',
            'Do not push any branch',
            'Do not claim GitHub CI',
            'Batch 1 is not authorized',
            'No shorthand continuation language',
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_SHARED_EXCLUSION_RACES_CLASSIFIED',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $prompt, $boundary);
        }
    }

    public function testPromptProvidesPowerShellSynchronizationAndLocalBranch(): void
    {
        $prompt = $this->read(self::ENTRYPOINT);
        foreach ([
            'git checkout main',
            'git pull --ff-only origin main',
            'git status --short',
            'git rev-parse HEAD',
            'git switch -c codex/reconciliation-shared-exclusion-preparation-batch-0-local',
            'php vendor/bin/phpunit',
        ] as $command) {
            self::assertStringContainsStringIgnoringCase($command, $prompt, $command);
        }
    }

    private function read(string $path): string
    {
        return preg_replace('/\s+/', ' ', (string) file_get_contents(dirname(__DIR__, 3).'/'.$path)) ?? '';
    }
}
