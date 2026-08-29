<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class GovernedToolProviderSeparationPreparationDocumentationTest extends TestCase
{
    public function testPreparationInventoriesTheFusedBoundaryAndAuthorizesContractsOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/governed-tool-provider-separation-preparation-inventory.md');
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-governed-tool-provider-separation.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/governed-tool-provider-separation-preparation-batch-0-complete.md');

        foreach (['`EXISTS_CANONICALLY`', '`EXISTS_FRAGMENTED`', '`ABSENT`', '`DEFERRED_BOUNDARY`'] as $classification) {
            self::assertStringContainsString($classification, $inventory);
        }
        foreach (['`GENERIC_IDENTITY_SUBSTRATE`', '`FUSED_AUTHORITY_PROVIDER_SCOPE`', '`BROKER_PROVIDER_COUPLING_REFUSED`', '`FUSED_ADMISSION_DECODER`', '`SELF_ASSEMBLED_AUTHORITY_PROHIBITED`'] as $posture) {
            self::assertStringContainsString($posture, $inventory);
        }
        foreach (['28 source and test files', 'ten production PHP files', 'seven production files belong to the outbound corridor', 'Provider Execution Assurance remains paused', 'Runtime behavior is unchanged.', 'No step is authorized by this inventory'] as $proof) {
            self::assertNotFalse(stripos($inventory.$handoff, $proof), $proof);
        }
        foreach (['GovernedToolOperationContract', 'ProviderImplementationBindingContract', 'ProviderRequestEncoderContract', 'ProviderEvidenceDecoderContract', 'NormalizedToolResultContract'] as $contract) {
            self::assertStringContainsString($contract, $inventory.$handoff);
        }
        foreach (['Iron Gate', 'Lazaretto', 'AgentMail', 'credential', 'external I/O', 'inbound webhook', 'sortie', 'revocation', 'telemetry', 'incident'] as $boundary) {
            self::assertNotFalse(stripos($inventory.$handoff, $boundary), $boundary);
        }

        self::assertStringContainsString('`CAMPAIGN_COMPLETE_PROVIDER_EXECUTION_ASSURANCE_REMAINS_PAUSED`', $campaign);
        self::assertStringContainsString('Only Batch 1 is authorized', $handoff);
        self::assertStringContainsString('No producer or consumer is implemented', $campaign);
    }
}
