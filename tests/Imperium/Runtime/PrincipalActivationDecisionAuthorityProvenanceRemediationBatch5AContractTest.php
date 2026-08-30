<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionV3Contract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract;
use PHPUnit\Framework\TestCase;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationBatch5AContractTest extends TestCase
{
    public function testSuccessorPrincipalAddsExactlyTheDecisionAuthorityScope(): void
    {
        self::assertSame(ImperatorRuntimePrincipalVersionContract::SCHEMA, ImperatorRuntimePrincipalVersionV3Contract::PREDECESSOR_SCHEMA);
        self::assertSame(ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_FIELDS);
        self::assertSame(ImperatorRuntimePrincipalVersionContract::REQUIRED_IDENTITY_FIELDS, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_IDENTITY_FIELDS);
        self::assertSame(ImperatorRuntimePrincipalVersionContract::REQUIRED_LIFECYCLE_FIELDS, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_LIFECYCLE_FIELDS);
        self::assertSame(ImperatorRuntimePrincipalVersionContract::SECRET_EXCLUSION, ImperatorRuntimePrincipalVersionV3Contract::SECRET_EXCLUSION);
        self::assertSame(
            ['provider_executor_principal_activation_decision_authority'],
            array_values(array_diff(
                ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_AUTHORITY_SCOPE_FIELDS,
                ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS,
            )),
        );
        self::assertSame(
            ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS,
            array_slice(ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_AUTHORITY_SCOPE_FIELDS, 0, 5),
        );
        self::assertNotContains(true, ImperatorRuntimePrincipalVersionV3Contract::NON_AUTHORITIES);
    }

    public function testProductionEnvelopeCarriesTheCompleteDecisionShape(): void
    {
        self::assertSame(
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTOR_FIELDS,
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_ACTOR_FIELDS,
        );
        self::assertSame(
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS,
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_SCOPE_FIELDS,
        );
        self::assertSame(
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        );
        self::assertSame(
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_VALIDITY_FIELDS,
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_VALIDITY_FIELDS,
        );
        self::assertSame(
            ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::PERMITTED_TRANSITION,
            ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::PERMITTED_TRANSITION,
        );
        foreach (['issuance_authorization', 'actor', 'scope', 'disposition', 'rationale', 'limitations', 'activation_authority', 'validity', 'decision_id'] as $field) {
            self::assertContains($field, ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_FIELDS);
        }
        self::assertNotContains(true, ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::NON_AUTHORITIES);
    }

    public function testDocumentationPreservesThePausedRuntimePerimeter(): void
    {
        $documentation = file_get_contents(__DIR__.'/../../../docs/principal-activation-decision-authority-provenance-remediation-batch-5a-contracts.md');
        $handoff = file_get_contents(__DIR__.'/../../../docs/handoffs/principal-activation-decision-authority-provenance-remediation-batch-5a-complete.md');

        self::assertIsString($documentation);
        self::assertIsString($handoff);
        self::assertStringContainsString('BATCH_5A_AUTHORITY_EMPTY_SUCCESSOR_PRINCIPAL_AND_DECISION_ENVELOPE_CONTRACTS_COMPLETE', $documentation);
        self::assertStringContainsString('creates no principal, scope, lifecycle disposition, decision, activation authority or consumption', $documentation);
        self::assertStringContainsString('Provider Effect Principal and Binding Activation remains paused', $handoff);
        self::assertStringContainsString('Iron Gate and Lazaretto remain closed', $handoff);
        self::assertStringContainsString('UNKNOWN_REPLAY_PROHIBITED remains binding', $handoff);
    }
}
