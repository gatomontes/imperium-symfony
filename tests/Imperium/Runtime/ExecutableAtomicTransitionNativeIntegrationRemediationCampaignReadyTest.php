<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ExecutableAtomicTransitionNativeIntegrationRemediationCampaignReadyTest extends TestCase
{
    public function testSelectionRetainsSubstrateAndOwnsFourNativeGaps(): void
    {
        $campaign = $this->read('docs/next-campaign-executable-atomic-transition-native-integration-remediation.md');
        foreach (['EXECUTABLE_ATOMIC_TRANSITION_NATIVE_INTEGRATION_REMEDIATION_SELECTED',
            'EXECUTABLE_ATOMIC_TRANSITION_TERMINAL_AUDIT_REFUSED_NATIVE_INTEGRATION_ABSENT',
            'native principal competence', 'native eligible-successor',
            'governed-provider-execution admission v3', 'provider-binding consumer'] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $campaign, $boundary);
        }
    }

    public function testOnlyPreparationIsAuthorized(): void
    {
        $documents = $this->read('docs/next-campaign-executable-atomic-transition-native-integration-remediation.md')
            .$this->read('docs/handoffs/executable-atomic-transition-native-integration-remediation-campaign-ready.md');
        foreach (['Only Preparation Batch 0 may next be considered', 'Do not implement the corrections',
            'BOUND_INACTIVE', 'NOT_IMPLEMENTED', 'UNKNOWN_REPLAY_PROHIBITED'] as $boundary) {
            self::assertStringContainsString($boundary, $documents, $boundary);
        }
    }

    public function testEightStageSequenceSeparatesNativeRepairFromProof(): void
    {
        $campaign = $this->read('docs/next-campaign-executable-atomic-transition-native-integration-remediation.md');
        foreach (['Campaign countdown at selection: eight stages including Preparation Batch 0',
            'Batch 1 — native principal competence and authority lineage',
            'Batch 2 — native successor provenance',
            'Batch 3 — canonical v3 admission and binding consumer',
            'Batch 4 — native atomic integration',
            'Batch 5 — native contention, interruption and lifecycle proof',
            'Batch 6 — reconstruction and adversarial audit',
            'Batch 7 — separately sequenced terminal Blackquill audit'] as $boundary) {
            self::assertStringContainsString($boundary, $campaign, $boundary);
        }
    }

    public function testShortcutRefusalsAreExplicit(): void
    {
        $ready = $this->read('docs/handoffs/executable-atomic-transition-native-integration-remediation-campaign-ready.md');
        foreach (['merely adding a scope field', 'renaming the new admission schema',
            'configured hashes as native provenance', 'fixture or offline successor stores',
            'unread binding projection'] as $refusal) {
            self::assertStringContainsString($refusal, $ready, $refusal);
        }
    }

    public function testLocalPromptAndCurrentConsumersPointToPreparation(): void
    {
        $path = 'docs/handoffs/executable-atomic-transition-native-integration-remediation-preparation-batch-0-local-ready.md';
        $handoff = $this->read($path);
        foreach (['git pull --ff-only origin main',
            'EXECUTABLE_ATOMIC_TRANSITION_NATIVE_INTEGRATION_REMEDIATION_CAMPAIGN_READY',
            'PREPARATION_BATCH_0_COMPLETE_NATIVE_INTEGRATION_GAPS_CLASSIFIED',
            'ExecutableAtomicTransitionNativeIntegrationRemediationPreparationBatch0Test.php',
            'New-chat prompt', 'Reject constant-only scope widening'] as $boundary) {
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
