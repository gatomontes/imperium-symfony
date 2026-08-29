<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clavium\CredentialReferenceExposureObservationContract;
use App\Imperium\Runtime\Clavium\ProcessLossCapabilityCustodyEvidenceContract;
use App\Imperium\Runtime\Imperator\ActivationPrincipalProvenanceEvidenceContract;
use App\Imperium\Runtime\Imperator\ActivationTransitionInterruptionEvidenceContract;
use App\Imperium\Runtime\LaCortine\StrandedActivationArtifactDispositionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch1Test extends TestCase
{
    public function testFiveContractsAreDistinctVersionedAndAuthorityEmpty(): void
    {
        $contracts = [ActivationPrincipalProvenanceEvidenceContract::class, ActivationTransitionInterruptionEvidenceContract::class, StrandedActivationArtifactDispositionContract::class, CredentialReferenceExposureObservationContract::class, ProcessLossCapabilityCustodyEvidenceContract::class];
        self::assertCount(5, array_unique(array_map(static fn (string $contract): string => $contract::SCHEMA, $contracts)));
        foreach ($contracts as $contract) {
            self::assertSame(1, $contract::VERSION);
            self::assertNotSame('', $contract::PRODUCER_POSTURE);
            self::assertNotEmpty($contract::CONSUMER_POSTURES);
            self::assertContains('record_digest', $contract::REQUIRED_FIELDS);
            foreach ($contract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
        }
    }

    public function testSecretObservationContractCannotPersistBearingMaterial(): void
    {
        self::assertNotContains('credential_reference', CredentialReferenceExposureObservationContract::REQUIRED_FIELDS);
        self::assertNotContains('credential_secret', CredentialReferenceExposureObservationContract::REQUIRED_FIELDS);
        foreach (CredentialReferenceExposureObservationContract::SECRET_EXCLUSION as $permission) self::assertFalse($permission);
        self::assertContains('credential_reference_persisted', ProcessLossCapabilityCustodyEvidenceContract::REQUIRED_FIELDS);
        self::assertContains('capability_reconstructed', ProcessLossCapabilityCustodyEvidenceContract::REQUIRED_FIELDS);
    }

    public function testDocumentationAuthorizesOnlyOfflineInterruptionDemonstrationsNext(): void
    {
        $root = dirname(__DIR__, 3);
        $contracts = (string) file_get_contents($root.'/docs/provider-binding-activation-integrity-remediation-contracts.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-integrity-remediation-batch-1-complete.md');
        foreach (['BATCH_1_AUTHORITY_EMPTY_CONTRACTS_COMPLETE_NO_IMPLEMENTATION', 'Contract existence grants no authority', 'No producer', 'terminal custody refusal remains authoritative', 'Provider Execution Assurance remains paused'] as $proof) self::assertNotFalse(stripos($contracts, $proof), $proof);
        foreach (['Only Batch 2 is authorized', 'offline interruption demonstrations', 'same-consumer convergence', 'expiry refusal', 'conflicting replay', 'may not implement principal provenance', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }
}
