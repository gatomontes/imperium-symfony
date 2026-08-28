<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicProviderInvocationAdmissionContract;
use App\Imperium\Runtime\LaCortine\DeterministicProviderInvocationCheckpointContract;
use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch4Test extends TestCase
{
    public function testVersionedContractsSeparateAdmissionAttemptAndCallbackStart(): void
    {
        self::assertSame('imperium.la-cortine.deterministic-provider-invocation-admission/v2', DeterministicProviderInvocationAdmissionContract::SCHEMA);
        self::assertSame('imperium.la-cortine.deterministic-credential-consumption-attempt/v1', DeterministicProviderInvocationCheckpointContract::CREDENTIAL_ATTEMPT_SCHEMA);
        self::assertSame('imperium.la-cortine.deterministic-provider-callback-start/v1', DeterministicProviderInvocationCheckpointContract::CALLBACK_START_SCHEMA);
    }

    public function testHandoffNamesTruthfulFailureWindowsAndAuthorizesReconstructionOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-4-complete.md');
        foreach (['credential not yet attempted', 'Credential failure', 'Callback failure', '`UNKNOWN_REPLAY_PROHIBITED`', 'Only Batch 5 may next be considered', 'Batch 5 is not authorized'] as $proof) self::assertStringContainsString($proof, $handoff);
    }
}
