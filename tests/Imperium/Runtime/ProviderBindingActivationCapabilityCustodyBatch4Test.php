<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityContract;
use App\Imperium\Runtime\LaCortine\EnvironmentCredentialBroker;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyBatch4Test extends TestCase
{
    public function testEnvironmentBrokerCannotTransferExactIssuedCapability(): void
    {
        $issuer = new EnvironmentCredentialBroker();
        $recipient = new EnvironmentCredentialBroker();
        $capability = $issuer->issue('env:TEST_ONLY', 'commission-test', 'email.send', new \DateTimeImmutable('+5 minutes'));
        self::assertTrue($issuer->recognizesExactCapability($capability));
        self::assertFalse($recipient->recognizesExactCapability($capability));
        self::assertFalse($issuer->supportsCrossProcessCustody());
        self::assertFalse($recipient->supportsCrossProcessCustody());
    }

    public function testFeasibilityRefusalCreatesNoCustodyDeliveryOrIoAuthority(): void
    {
        self::assertSame('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL);
        foreach (CrossProcessCapabilityCustodyFeasibilityContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Clavium/CrossProcessCapabilityCustodyFeasibilityService.php');
        foreach (["'custody_created' => false", "'delivery_created' => false", "'capability_issued' => false", "'capability_reconstructed' => false", "'credential_reference_persisted' => false", "'secret_material_persisted' => false", "'external_action_performed' => false"] as $proof) self::assertStringContainsString($proof, $source);
        foreach (['OpaqueCapabilityCustodyContract::SCHEMA', 'OneTimeCapabilityDeliveryContract::SCHEMA', 'resolveCredential', 'providerOperation'] as $forbidden) self::assertStringNotContainsString($forbidden, $source);
    }

    public function testCampaignTerminatesInTruthfulRefusal(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-capability-custody-campaign-terminal-refusal.md');
        foreach (['Campaign terminal refusal', 'cross-process capability custody is unprovable', 'No Batch 5 is authorized', 'may not reconstruct', 'Provider Execution Assurance remains paused', 'Iron Gate', 'Lazaretto', 'external I/O'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
