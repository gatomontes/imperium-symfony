<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCapabilityCustodyBatch3Test extends TestCase
{
    public function testActivationLeaseRemainsSingleExecutionAndNonOperational(): void
    {
        self::assertSame('ACTIVATED_UNCONSUMED', SingleExecutionProviderBindingActivationContract::STATUSES[0]);
        self::assertSame(['execution_id', 'operation', 'exact_destination', 'provider_substitution_permitted'], SingleExecutionProviderBindingActivationContract::REQUIRED_SCOPE_FIELDS);
        foreach (SingleExecutionProviderBindingActivationContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/SingleExecutionProviderBindingActivationService.php');
        foreach (["'status' => 'ACTIVATED_UNCONSUMED'", "'single_execution' => true", "'consumed' => true", "'continuing_authority' => false", "'BOUND_INACTIVE'", "'CLAIMED_PRE_IO'"] as $proof) self::assertStringContainsString($proof, $source);
        foreach (['EnvironmentCredentialBroker', 'resolveCredential', 'AgentMail', 'IronGate', 'Lazaretto'] as $forbidden) self::assertStringNotContainsString($forbidden, $source);
    }

    public function testBatchDocumentationAuthorizesOnlyCustodyAndDeliveryNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-capability-custody-batch-3-complete.md');
        foreach (['Only Batch 4 is authorized', 'opaque capability custody', 'one-time delivery', 'may not issue a credential capability', 'atomic execution admission', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
