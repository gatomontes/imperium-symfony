<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\OneTimeCapabilityDeliveryContract;
use App\Imperium\Runtime\Clavium\OpaqueCapabilityCustodyContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationAuthorityContract;
use App\Imperium\Runtime\LaCortine\AtomicProviderExecutionAdmissionContract;
use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyBatch1Test extends TestCase
{
    public function testContractsAreSeparatelyVersionedAndNameExactPostures(): void
    {
        $contracts = [
            ProviderBindingActivationAuthorityContract::class,
            SingleExecutionProviderBindingActivationContract::class,
            OpaqueCapabilityCustodyContract::class,
            OneTimeCapabilityDeliveryContract::class,
            AtomicProviderExecutionAdmissionContract::class,
        ];
        self::assertCount(5, array_unique(array_map(static fn (string $contract): string => $contract::SCHEMA, $contracts)));
        foreach ($contracts as $contract) {
            self::assertSame(1, $contract::VERSION);
            self::assertNotSame('', $contract::PRODUCER_POSTURE);
            self::assertNotEmpty($contract::CONSUMER_POSTURES);
            self::assertContains('record_digest', $contract::REQUIRED_FIELDS);
            foreach ($contract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
        }
    }

    public function testCustodyContractExcludesCapabilityBearingMaterial(): void
    {
        foreach (OpaqueCapabilityCustodyContract::SECRET_EXCLUSION as $permission) self::assertFalse($permission);
        self::assertNotContains('credential_reference', OpaqueCapabilityCustodyContract::REQUIRED_FIELDS);
        self::assertNotContains('credential_secret', OpaqueCapabilityCustodyContract::REQUIRED_FIELDS);
        self::assertNotContains('serialized_capability', OpaqueCapabilityCustodyContract::REQUIRED_FIELDS);
    }

    public function testActivationDeliveryAndAdmissionRemainDistinct(): void
    {
        self::assertSame(['execution_id', 'operation', 'exact_destination', 'provider_substitution_permitted'], SingleExecutionProviderBindingActivationContract::REQUIRED_SCOPE_FIELDS);
        self::assertContains('DELIVERED_ACKNOWLEDGED', OneTimeCapabilityDeliveryContract::STATUSES);
        self::assertSame(['authoritative_root', 'activation_consumed', 'custody_consumed', 'delivery_consumed', 'single_transaction', 'committed_pre_resolution', 'committed_pre_io'], AtomicProviderExecutionAdmissionContract::REQUIRED_ATOMIC_CONSUMPTION_FIELDS);
        self::assertSame(['ADMITTED_PRE_RESOLUTION_PRE_IO', 'EXPIRED', 'REVOKED'], AtomicProviderExecutionAdmissionContract::STATUSES);
    }

    public function testBatchDocumentationAuthorizesOnlyTheNextDecisionIssuanceRoute(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = (string) file_get_contents($root.'/docs/provider-binding-activation-capability-custody-contracts.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-capability-custody-batch-1-complete.md');
        foreach (['`BATCH_1_CONTRACTS_COMPLETE_NO_IMPLEMENTATION`', 'Contract existence grants no authority', 'No producer, custodian, delivery mechanism or consumer is implemented', 'Provider Execution Assurance remains paused'] as $proof) self::assertStringContainsString($proof, $contracts);
        foreach (['Only Batch 2 is authorized', 'pre-existing competent source decision', 'may not activate a binding', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
