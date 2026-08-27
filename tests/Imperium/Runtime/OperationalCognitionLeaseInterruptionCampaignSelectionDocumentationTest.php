<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class OperationalCognitionLeaseInterruptionCampaignSelectionDocumentationTest extends TestCase
{
    public function testSelectionAuthorizesPreparationOnlyAndKeepsDeferredBoundariesClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $document = (string) file_get_contents($root.'/docs/next-campaign-operational-cognition-lease-interruption.md');
        $index = (string) file_get_contents($root.'/docs/handoffs/README.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/operational-cognition-lease-interruption-campaign-ready.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');

        self::assertStringContainsString('`BATCH_2_ENFORCEMENT_AUTHORITY_COMPLETE_BATCH_3_UNOPENED`', $document);
        self::assertStringContainsString('## Batch 2 result', $document);
        self::assertStringContainsString('`oca-lease:<hash leaseId>`', $document);
        self::assertStringContainsString('must not be reused by string substitution', $document);
        self::assertStringContainsString('No implementation step below is authorized merely because it is listed.', $document);
        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $document);
        }
        foreach (['Generalized revocation', 'telemetry', 'containment', 'incidents', 'Iron Gate', 'Lazaretto', 'sorties', 'new credential-platform work'] as $boundary) {
            self::assertStringContainsString($boundary, $document);
        }
        self::assertStringContainsString('Operational Cognition Lease Interruption Batch 2 is complete', $index);
        self::assertStringContainsString('Only the exact disposition and enforcement-authority boundaries are open', $index);
        self::assertStringContainsString('Only Preparation Batch 0 is authorized.', $handoff);
        self::assertStringContainsString('`oca-cognition-authority` → `oca-lease` lock order', $handoff);
        self::assertStringContainsString('Operational Cognition Lease Interruption', $flow);
        self::assertStringContainsString('does not alter this route', $flow);
    }
}
