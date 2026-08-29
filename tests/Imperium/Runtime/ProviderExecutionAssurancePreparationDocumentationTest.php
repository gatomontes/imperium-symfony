<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderExecutionAssurancePreparationDocumentationTest extends TestCase
{
    public function testPreparationClassifiesProviderAssuranceAndKeepsRuntimeClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/provider-execution-assurance-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-execution-assurance-preparation-batch-0-complete.md');
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-execution-assurance.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach (['`UNKNOWN_REPLAY_PROHIBITED`', '`NO_QUERY_NO_RETRY`', '`UNMIGRATED_LIVE_CONSUMER`', '`HOSTILE_WRITER_UNPROVED`', '`MULTI_HOST_UNPROVED`'] as $posture) {
            self::assertStringContainsString($posture, $inventory.$handoff);
        }
        foreach (['organization', '24 hours after completion', '`409 Conflict`', 'duplicate while first request is in progress', 'remote cryptographic authorship', 'No step is authorized merely because it appears here.', 'Runtime behavior is unchanged.'] as $proof) {
            self::assertNotFalse(stripos($inventory.$handoff, $proof), $proof);
        }
        foreach (['Iron Gate', 'Lazaretto', 'sortie', 'credential-platform', 'revocation', 'propagation', 'telemetry', 'reassessment', 'containment', 'incident'] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }

        self::assertStringContainsString('`PREPARATION_BATCH_0_COMPLETE_PAUSED_FOR_TOOL_PROVIDER_SEPARATION`', $campaign);
        self::assertStringContainsString('No Provider Execution Assurance Batch 1 is authorized', $handoff);
        self::assertStringContainsString('active continuation is Governed Tool and Provider Separation', $handoff);
    }
}
