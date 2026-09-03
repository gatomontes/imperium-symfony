<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ExecutableAtomicTransitionCanonicalConsumerIntegrationCorrectionCampaignReadyTest extends TestCase
{
    public function testSelectionRetainsTheSubstrateButRefusesSelfDeclaredIntegration(): void
    {
        $campaign = $this->read('docs/next-campaign-executable-atomic-transition-canonical-consumer-integration-correction.md');
        foreach (['EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_SELECTED',
            'NATIVE_INTEGRATION_TERMINAL_AUDIT_REFUSED_CANONICAL_CONSUMER_NOT_INTEGRATED',
            'Eleven historical', 'descriptor readers retain separate meanings',
            'A new route cannot prove canonical', 'integration merely by consuming itself'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $campaign, $boundary);
        }
    }

    public function testFiveStagesSeparateInventoryIntegrationProofAndAudit(): void
    {
        $campaign = $this->read('docs/next-campaign-executable-atomic-transition-canonical-consumer-integration-correction.md');
        foreach (['Campaign countdown at selection: five stages including Preparation Batch 0',
            'Preparation Batch 0 — canonical-consumer and bypass inventory',
            'Batch 1 — canonical interpretation boundary',
            'Batch 2 — established-consumer integration and bypass closure',
            'Batch 3 — application and adversarial proof',
            'Batch 4 — separately sequenced terminal Blackquill audit'] as $boundary) {
            self::assertStringContainsString($boundary, $campaign, $boundary);
        }
    }

    public function testOnlyPreparationIsAuthorizedAndRefusalTrapsRemain(): void
    {
        $documents = $this->read('docs/next-campaign-executable-atomic-transition-canonical-consumer-integration-correction.md')
            .$this->read('docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-campaign-ready.md');
        foreach (['Only Preparation Batch 0 may next be considered', 'Do not implement the correction',
            'wrapper', 'container/application wiring', 'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $documents, $boundary);
        }
    }

    public function testLocalHandoffContainsCopyReadyPromptAndIsPublished(): void
    {
        $path = 'docs/handoffs/executable-atomic-transition-canonical-consumer-integration-correction-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach (['git pull --ff-only origin main',
            'EXECUTABLE_ATOMIC_TRANSITION_CANONICAL_CONSUMER_INTEGRATION_CORRECTION_CAMPAIGN_READY',
            'PREPARATION_BATCH_0_COMPLETE_CANONICAL_CONSUMER_BYPASS_CLASSIFIED',
            'ExecutableAtomicTransitionCanonicalConsumerIntegrationCorrectionPreparationBatch0Test.php',
            'New-chat prompt', 'Do not accept the new command consuming `NativeBindingReader`'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff, $boundary);
        }
        foreach (['docs/delegate-mission-flow.md', 'docs/handoffs/README.md', 'todo/blackquill-todos.md'] as $consumer) {
            self::assertStringContainsString($path, $this->read($consumer), $consumer);
        }
    }

    private function read(string $path): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/'.$path);
    }
}
