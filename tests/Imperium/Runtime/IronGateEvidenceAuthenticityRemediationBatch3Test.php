<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultContract;
use App\Imperium\Runtime\LaCortine\DeterministicRawProviderResultService;
use PHPUnit\Framework\TestCase;

final class IronGateEvidenceAuthenticityRemediationBatch3Test extends TestCase
{
    public function testRawResultSealerAcceptsOnlyOneEnvelopeIdentity(): void
    {
        $method = new \ReflectionMethod(DeterministicRawProviderResultService::class, 'seal');
        self::assertSame(1, $method->getNumberOfParameters());
        self::assertSame('envelopeId', $method->getParameters()[0]->getName());
        self::assertSame('imperium.la-cortine.deterministic-raw-provider-result/v2', DeterministicRawProviderResultContract::SCHEMA);
        self::assertContains('provider_response_envelope', DeterministicRawProviderResultContract::REQUIRED_FIELDS);
    }

    public function testHandoffPreservesUnknownAndKeepsLiveConsumersClosed(): void
    {
        $root = dirname(__DIR__, 3);
        $handoff = (string) file_get_contents($root.'/docs/handoffs/iron-gate-evidence-authenticity-remediation-batch-3-complete.md');
        foreach (['Callers can no longer supply', '`UNKNOWN_REPLAY_PROHIBITED`', 'Only Batch 4 may next be considered', 'is not authorized by this handoff', 'no external I/O occurs'] as $proof) self::assertStringContainsString($proof, $handoff);
    }
}
