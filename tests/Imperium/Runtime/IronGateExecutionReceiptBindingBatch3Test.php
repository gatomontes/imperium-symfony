<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicOutboundEmailAuthorizationContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch3Test extends TestCase
{
    public function testOutboundEmailAuthorizationShapeIsExactAndDeclarative(): void
    {
        self::assertSame(
            'imperium.la-cortine.deterministic-outbound-email-authorization/v1',
            DeterministicOutboundEmailAuthorizationContract::SCHEMA,
        );
        self::assertSame(1, DeterministicOutboundEmailAuthorizationContract::VERSION);
        foreach (['source_decision', 'issuer', 'holder', 'scope', 'provider_safety', 'single_use', 'expires_at'] as $field) {
            self::assertContains($field, DeterministicOutboundEmailAuthorizationContract::REQUIRED_FIELDS);
        }
        foreach (['recipient_set_digest', 'payload_digest', 'credential_reference_digest', 'expected_return_contract'] as $field) {
            self::assertContains($field, DeterministicOutboundEmailAuthorizationContract::REQUIRED_SCOPE_FIELDS);
        }
        self::assertSame('email.send', DeterministicOutboundEmailAuthorizationContract::EXACT_SCOPE_RULES['operation']);
        self::assertTrue(DeterministicOutboundEmailAuthorizationContract::CONSUMPTION_RULES['single_use']);
        self::assertFalse(DeterministicOutboundEmailAuthorizationContract::CONSUMPTION_RULES['retry_after_provider_key_expiry_permitted']);
        self::assertNotContains(true, DeterministicOutboundEmailAuthorizationContract::CONTRACT_BOUNDARY);
    }

    public function testBatchThreeProvesProviderSafetyButKeepsConsumerBlocked(): void
    {
        $root = dirname(__DIR__, 3);
        $provider = (string) file_get_contents($root.'/docs/iron-gate-agentmail-idempotent-send-assessment.md');
        $contract = (string) file_get_contents($root.'/docs/iron-gate-outbound-email-authorization-contract.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-3-complete.md');

        foreach (['`BATCH_3_PROVIDER_IDEMPOTENCY_PROVED_NOT_ADOPTED`', '`EXISTS_CANONICALLY`', '`PROVIDER_PREREQUISITE_SATISFIED`', '`BLOCKED_NATIVE_ISSUER_AND_DURABLE_CLAIM`', '409 Conflict', '24 hours'] as $invariant) {
            self::assertStringContainsString($invariant, $provider);
        }
        self::assertStringContainsString('`BATCH_3_AUTHORIZATION_SHAPE_DEFINED_NOT_ISSUED`', $contract);
        self::assertStringContainsString('does not identify', $contract);
        self::assertStringContainsString('Batch 4 is not authorized', $handoff);
        self::assertStringContainsString('No issuer or consumer was migrated', $handoff);
    }
}
