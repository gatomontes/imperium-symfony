<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\CredentialCapability;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch4Test extends TestCase
{
    public function testGenericCapabilityStateAndMetadataExcludeClearReference(): void
    {
        $reference = 'env:BATCH4_UNMISTAKABLE_REFERENCE';
        $capability = new CredentialCapability('capability.batch4', $reference, 'commission.batch4', 'email.send', new \DateTimeImmutable('+5 minutes'));

        self::assertArrayNotHasKey('credentialRef', get_object_vars($capability));
        self::assertArrayNotHasKey('credential_ref', $capability->metadata());
        self::assertSame(hash('sha256', $reference), $capability->metadata()['credential_reference_digest']);
        self::assertStringNotContainsString($reference, json_encode($capability->metadata(), JSON_THROW_ON_ERROR));

        $runtime = dirname(__DIR__, 3).'/src/Imperium/Runtime';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($runtime)) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension()) continue;
            self::assertDoesNotMatchRegularExpression('/->credentialRef\\b/', (string) file_get_contents($file->getPathname()), $file->getPathname());
        }
    }

    public function testDocumentationAuthorizesOnlyOfflineProcessLossEvidenceNext(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-batch-4-complete.md');
        foreach (['Only Batch 5 is authorized', 'offline process-loss', 'without persisting', 'may not produce', 'principal provenance', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
