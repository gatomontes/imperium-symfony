<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingActivationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\LaCortine\DeterministicTransitionCallerAuthorityContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyBatch2Test extends TestCase
{
    public function testDecisionAndIssuanceAreSeparateSingleUseTransitions(): void
    {
        self::assertContains('DECIDE_EXACT_PROVIDER_BINDING_ACTIVATION', DeterministicTransitionCallerAuthorityContract::TRANSITIONS);
        self::assertContains('ISSUE_EXACT_PROVIDER_BINDING_ACTIVATION_AUTHORITY', DeterministicTransitionCallerAuthorityContract::TRANSITIONS);
        self::assertSame(['AUTHORIZED', 'REFUSED'], ProviderBindingActivationIssuanceContract::DISPOSITIONS);
        self::assertContains('issuance_authority', ProviderBindingActivationIssuanceContract::REQUIRED_DECISION_FIELDS);
        self::assertContains('issued_activation_authority', ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_FIELDS);
        self::assertContains('authority_single_use', ProviderBindingActivationAuthorityContract::REQUIRED_FIELDS);
        self::assertContains('execution_claim', ProviderBindingActivationAuthorityContract::REQUIRED_FIELDS);
        self::assertContains('provider_binding', ProviderBindingActivationAuthorityContract::REQUIRED_FIELDS);
    }

    public function testImplementationKeepsActivationCustodyAndIoClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $decision = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/ProviderBindingActivationDecisionService.php');
        $issuance = (string) file_get_contents($root.'/src/Imperium/Runtime/Imperator/ProviderBindingActivationIssuanceService.php');
        foreach (['BOUND_INACTIVE', 'CLAIMED_PRE_IO', 'source_effect_authorization', 'execution_claim', 'provider_binding'] as $proof) self::assertStringContainsString($proof, $decision.$issuance);
        foreach (['provider_binding_activated\' => false', 'credential_capability_issued\' => false', 'external_action_performed\' => false'] as $proof) self::assertStringContainsString($proof, $issuance);
        foreach (ProviderBindingActivationIssuanceContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
    }

    public function testBatchDocumentationAuthorizesOnlyImmutableActivationNext(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-capability-custody-batch-2-complete.md');
        foreach (['Only Batch 3 is authorized', 'single-execution activation', 'does not activate', 'credential capability', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
