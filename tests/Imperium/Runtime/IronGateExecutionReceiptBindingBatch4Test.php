<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\OutboundEmailAuthorizationIssuanceContract;
use PHPUnit\Framework\TestCase;

final class IronGateExecutionReceiptBindingBatch4Test extends TestCase
{
    public function testCompetentRouteSeparatesRequestDecisionIssuanceAndConsumption(): void
    {
        self::assertSame('imperium.curia-deterministic-outbound-email-request/v1', OutboundEmailAuthorizationIssuanceContract::REQUEST_SCHEMA);
        self::assertSame('imperium.imperator-deterministic-outbound-email-decision/v1', OutboundEmailAuthorizationIssuanceContract::DECISION_SCHEMA);
        self::assertSame('imperium.imperator-outbound-email-authorization-issuance/v1', OutboundEmailAuthorizationIssuanceContract::ISSUANCE_SCHEMA);
        self::assertSame('curia.seneschal', OutboundEmailAuthorizationIssuanceContract::COMPETENT_ROUTE['request_owner']);
        self::assertSame('imperator', OutboundEmailAuthorizationIssuanceContract::COMPETENT_ROUTE['decision_owner']);
        self::assertSame('clavium.locksmith', OutboundEmailAuthorizationIssuanceContract::COMPETENT_ROUTE['credential_capability_issuer']);
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::COMPETENT_ROUTE['perimeter_authority_issuer']);
        self::assertSame(['AUTHORIZED', 'REFUSED'], OutboundEmailAuthorizationIssuanceContract::DISPOSITIONS);
    }

    public function testIssuerCannotWidenScopeUseCredentialsOrPerformTheAct(): void
    {
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['request_grants_authority']);
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['refusal_opens_issuance_authority']);
        self::assertTrue(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['authorized_decision_opens_one_issuance_authority']);
        self::assertTrue(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['issuance_scope_must_equal_request_scope']);
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['issuer_may_resolve_credentials']);
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['issuer_may_dispatch']);
        self::assertFalse(OutboundEmailAuthorizationIssuanceContract::ROUTE_RULES['issuer_may_start_external_io']);
        self::assertNotContains(true, OutboundEmailAuthorizationIssuanceContract::CONTRACT_BOUNDARY);
    }

    public function testBatchFourKeepsImplementationAndPerimeterClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $route = (string) file_get_contents($root.'/docs/iron-gate-outbound-email-issuer-route.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-execution-receipt-binding-batch-4-complete.md');

        foreach (['`BATCH_4_COMPETENT_ROUTE_AND_ISSUER_CONTRACT_DEFINED_ONLY`', 'Imperator owns the external-action decision', 'Clavium may later issue', 'Iron Gate is a consumer', '`BLOCKED_ROUTE_NOT_IMPLEMENTED_AND_NO_DURABLE_CLAIM`'] as $invariant) {
            self::assertStringContainsString($invariant, $route);
        }
        self::assertStringContainsString('No request, decision, issuance authority', $handoff);
        self::assertStringContainsString('Batch 5 is not authorized', $handoff);
        self::assertStringContainsString('Runtime behavior is unchanged.', $handoff);
    }
}
