<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationCampaignSelectionDocumentationTest extends TestCase
{
    public function testPreparationClassifiesAuthenticityGapsAndAuthorizesContractOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-iron-gate-evidence-authenticity-remediation.md');
        $inventory = (string) file_get_contents($root.'/docs/iron-gate-evidence-authenticity-remediation-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-preparation-batch-0-complete.md');

        self::assertStringContainsString('`BATCH_7_CALLER_AUTHORITY_ISSUER_IMPLEMENTED`', $campaign);
        foreach (['`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) self::assertStringContainsString($classification, $campaign);
        foreach (['`PROVIDER_RESPONSE_ENVELOPE_REQUIRED`', '`COMPLETE_CHAIN_RECONSTRUCTION_REQUIRED`', '`CALLER_AUTHORITY_REQUIRED`', '`THREAT_MODEL_BOUNDED_INTEGRITY`', '`PROVIDER_PROVENANCE_REQUIRED`', '`SINGLE_AUTHORITATIVE_ROOT_ONLY`'] as $posture) self::assertStringContainsString($posture, $campaign);
        self::assertStringContainsString('can nominate accepted response bytes', $inventory);
        self::assertStringContainsString('proves shape, not provider provenance', $inventory);
        self::assertStringContainsString('Only Batch 1 is authorized', $handoff);
        self::assertStringContainsString('may not implement the producer', $handoff);
    }

    public function testPreparationKeepsLiveAndDeferredBoundariesClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-iron-gate-evidence-authenticity-remediation.md');
        $flow = (string) file_get_contents($root.'/docs/delegate-mission-flow.md');
        $index = (string) file_get_contents($root.'/docs/handoffs/README.md');

        foreach (['live consumer behavior', 'external I/O', '`AgentMailEmailSendCommand`', '`DeterministicBoundaryExecutor`', '`AgentMailEmailTransport`', '`IronGate`', 'expand Lazaretto', 'redesign credentials', 'assess sortie', 'revocation', 'telemetry', 'containment', 'incident'] as $boundary) self::assertStringContainsString($boundary, $campaign);
        self::assertStringContainsString('next separately selected campaign is Iron Gate Evidence Authenticity Remediation', $flow);
        self::assertStringContainsString('only three-consumer authority enforcement', $index);
        self::assertStringContainsString('Live deterministic consumer adoption and sortie remain deferred', $index);
    }
}
