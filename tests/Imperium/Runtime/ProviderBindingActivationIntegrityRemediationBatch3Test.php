<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\StrandedActivationArtifactDispositionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch3Test extends TestCase
{
    public function testMechanicalDispositionIsLimitedToExpiredUnusedArtifacts(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/LaCortine/StrandedActivationArtifactDispositionService.php');
        self::assertStringContainsString("'QUARANTINED_EXPIRED_UNUSED'", $source);
        self::assertStringContainsString("'source_artifact_mutated' => false", $source);
        self::assertStringContainsString("'successor_authority_created' => false", $source);
        self::assertStringContainsString('REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', $source);
        self::assertStringContainsString('6 !== count($evidence)', $source);
        self::assertStringNotContainsString("'QUARANTINED_PENDING_REMEDIATION'", $source);
        self::assertStringNotContainsString("'RETIRE_CORRIDOR'", $source);
        foreach (StrandedActivationArtifactDispositionContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
    }

    public function testDocumentationAuthorizesOnlyCredentialReferenceHardeningNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-batch-3-complete.md');
        foreach (['Only Batch 4 is authorized', 'credential-reference boundary hardening', 'expired and unused', 'may not produce', 'principal provenance', 'process-loss custody', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
