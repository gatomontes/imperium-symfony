<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationIntegrityRemediationBatch6Test extends TestCase
{
    public function testAbsentPrincipalProvenanceCreatesNoRuntimeDispositionProducer(): void
    {
        $root = dirname(__DIR__, 3);
        $allowedVocabulary = array_map('realpath', [
            $root.'/src/Imperium/Runtime/LaCortine/StrandedActivationArtifactDispositionContract.php',
            $root.'/src/Imperium/Runtime/Evidence/ActivationCorridorDispositionInterruptionDemonstration.php',
            $root.'/src/Imperium/Runtime/Evidence/CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration.php',
            $root.'/src/Imperium/Runtime/Imperator/ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract.php',
            $root.'/src/Imperium/Runtime/Imperator/ActivationCorridorDispositionContract.php',
            $root.'/src/Imperium/Runtime/Imperator/ActivationCorridorDispositionContractValidator.php',
            $root.'/src/Imperium/Runtime/Imperator/ActivationCorridorDispositionEligibilityContract.php',
        ]);
        $runtime = $root.'/src/Imperium/Runtime';
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($runtime)) as $file) {
            if (!$file->isFile() || 'php' !== $file->getExtension() || in_array(realpath($file->getPathname()), $allowedVocabulary, true)) continue;
            $source = (string) file_get_contents($file->getPathname());
            self::assertStringNotContainsString("'QUARANTINED_PENDING_REMEDIATION'", $source, $file->getPathname());
            self::assertStringNotContainsString("'RETIRE_CORRIDOR'", $source, $file->getPathname());
        }
    }

    public function testCampaignTerminatesWithoutImpliedAuthority(): void
    {
        $handoff = (string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/provider-binding-activation-integrity-remediation-campaign-terminal.md');
        foreach (['CORRIDOR_DISPOSITION_REFUSED_PRINCIPAL_PROVENANCE_ABSENT', 'No implied continuation', 'authorizes no implementation batch', 'no disposition record', 'no successor authority', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }
}
