<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationCampaignSelectionDocumentationTest extends TestCase
{
    public function testOnlyPreparationBatchZeroIsAuthorizedAndRefusalRemainsBinding(): void
    {
        $root = dirname(__DIR__, 3);
        $campaign = (string) file_get_contents($root.'/docs/next-campaign-provider-binding-activation-integrity-remediation.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-integrity-remediation-campaign-ready.md');
        $review = (string) file_get_contents($root.'/docs/provider-binding-activation-integrity-blackquill-review.md');
        foreach (['Only Preparation Batch 0 is authorized', 'EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY', 'terminal custody refusal remains authoritative', 'Provider Execution Assurance remains paused'] as $proof) self::assertNotFalse(stripos($campaign, $proof), $proof);
        foreach (['activation-principal reachability', 'interruption recovery', 'stranded', 'credential-reference memory exposure', 'cross-process refusal proof'] as $finding) self::assertNotFalse(stripos($handoff, $finding), $finding);
        foreach (['Activation-principal reachability is unproved', 'New-consumer interruption recovery is unproved', 'Activation artifacts are stranded', 'Credential-reference handling needs an exact boundary', 'cross-process proof is partly declarative'] as $finding) self::assertNotFalse(stripos($review, $finding), $finding);
    }

    public function testPreparationInventoryAuthorizesOnlyAuthorityEmptyContracts(): void
    {
        $root = dirname(__DIR__, 3);
        $inventory = (string) file_get_contents($root.'/docs/provider-binding-activation-integrity-remediation-preparation-inventory.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-integrity-remediation-preparation-batch-0-complete.md');
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY', 'Interruption matrix', 'Credential-reference memory lifetime', 'Real process-loss demonstration', 'Only Batch 1 is authorized'] as $proof) self::assertStringContainsString($proof, $inventory);
        foreach (['activation-principal provenance evidence', 'interruption evidence', 'terminal disposition', 'credential-reference exposure observation', 'process-loss custody evidence', 'may not implement a producer', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
