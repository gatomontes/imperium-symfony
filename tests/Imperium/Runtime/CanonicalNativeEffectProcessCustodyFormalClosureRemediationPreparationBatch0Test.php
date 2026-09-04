<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectProcessCustodyFormalClosureRemediationPreparationBatch0Test extends TestCase
{
    private const array ARTIFACTS = [
        'docs/canonical-native-effect-process-custody-formal-closure-remediation-preparation-inventory-v1.md',
        'docs/canonical-native-effect-process-custody-formal-closure-remediation-serialization-process-call-graph-v1.md',
        'docs/canonical-native-effect-process-custody-formal-closure-remediation-custody-recovery-matrix-v1.md',
        'docs/canonical-native-effect-process-custody-formal-closure-remediation-adversarial-proof-matrix-v1.md',
        'docs/canonical-native-effect-process-custody-formal-closure-remediation-reading-evidence-ledger-v1.json',
        'docs/handoffs/canonical-native-effect-process-custody-formal-closure-remediation-preparation-batch-0-complete.md',
    ];

    public function testVersionedArtifactsExistAndPreserveTheHardStop(): void
    {
        foreach (self::ARTIFACTS as $path) {
            self::assertFileExists($this->root().'/'.$path, $path);
            self::assertNotSame('', $this->read($path), $path);
        }
        $all = implode("\n", array_map($this->read(...), self::ARTIFACTS));
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_PROCESS_CUSTODY_AND_FORMAL_CLOSURE_GAPS_CLASSIFIED',
            'DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'Batch 1 is not authorized',
            'No production runtime behavior',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $all, $stop);
        }
    }

    public function testInventoryClassifiesEveryRequiredSurfaceAndConcreteCounterexample(): void
    {
        $inventory = $this->read(self::ARTIFACTS[0]);
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY'] as $classification) {
            self::assertStringContainsString('`'.$classification.'`', $inventory);
        }
        foreach ([
            'restored issuer recognizes the restored capability',
            'cloned issuer recognizes original registered capability',
            'clone $outcome', 'pcntl_loaded=false', 'PID alone is forbidden',
            'authority-supplied `execution_boundary.id` is a governance label',
            'ee6e983941a23b75d9ee77b4ba4aa741a34bdbd6',
            'dc62d4e564bfde3230117d740ec157e0928abf35',
            '2,291/49,398', 'GitHub CI',
        ] as $fact) {
            self::assertStringContainsStringIgnoringCase($fact, $inventory, $fact);
        }
    }

    public function testGraphAndRecoveryMatrixSeparateRuntimeIdentityAndThreeActs(): void
    {
        $graph = $this->read(self::ARTIFACTS[1]);
        $matrix = $this->read(self::ARTIFACTS[2]);
        foreach ([
            'LABEL, NOT PROCESS FACT', 'getmypid()', 'random_bytes',
            'executeFirst', 'reconstruct', 'forwardComplete',
            'NO custody validation', 'pcntl_fork',
        ] as $edge) {
            self::assertStringContainsStringIgnoringCase($edge, $graph, $edge);
        }
        foreach ([
            'admission continuation scope', 'exact reconciliation-claim scope',
            'receipt immutable-store scope', 'Abandoned pre-callback',
            'Provider-side idempotency', 'invoke callback; automatic retry',
        ] as $rule) {
            self::assertStringContainsStringIgnoringCase($rule, $matrix, $rule);
        }
    }

    public function testAdversarialMatrixCoversSerializationCloneForkCutsRecoveryAndProvenance(): void
    {
        $matrix = $this->read(self::ARTIFACTS[3]);
        foreach ([
            'Serialize issuer + capability graph', 'Clone issuer, use original capability',
            'Linux `pcntl_fork`', 'PID reused', 'Two corridor `continuationIssuer()` calls',
            'Exit after custody consume before start rename',
            'Existing receipt passed to `execute()` with garbage capability',
            'Sealed response passed to `execute()` with fabricated capability',
            'Forward complete with exact claim', 'Callback count one',
            'Batches 1–4 separately authorized/committed/merged',
            'Terminal audit from clean merged Batch 4 main',
        ] as $case) {
            self::assertStringContainsStringIgnoringCase($case, $matrix, $case);
        }
    }

    public function testReadingEvidenceLedgerPinsBytesProbesAndNoCiClaim(): void
    {
        $ledger = json_decode($this->read(self::ARTIFACTS[4]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.canonical-native-effect-process-custody-formal-closure-remediation-reading-evidence-ledger/v1', $ledger['schema']);
        self::assertSame('ef69362fd49252e15893af72ca71a3e2abb7a209', $ledger['audited_main']);
        self::assertFalse($ledger['serialization_probe']['authority_or_capability_issuer_called']);
        self::assertFalse($ledger['serialization_probe']['capability_or_authority_consumed']);
        self::assertTrue($ledger['serialization_probe']['observations']['restored_issuer_recognizes_restored_capability']);
        self::assertTrue($ledger['serialization_probe']['observations']['cloned_issuer_recognizes_original_capability']);
        self::assertNull($ledger['git_provenance']['separate_batch_merge_chain']);
        self::assertNull($ledger['git_provenance']['github_ci_for_merge']);
        self::assertNull($ledger['ci_result']);
        self::assertGreaterThanOrEqual(60, count($ledger['sources']));

        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status'], $source['path']);
            self::assertFileExists($this->root().'/'.$source['path'], $source['path']);
            self::assertSame($source['normalized_sha256'], hash('sha256', $this->read($source['path'])), $source['path']);
        }
    }

    public function testOnlyPreparationDocumentsAndDocumentaryGuardWereAdded(): void
    {
        foreach (self::ARTIFACTS as $path) {
            self::assertTrue(str_starts_with($path, 'docs/'), $path);
        }
        $source = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectContinuationCapabilityIssuer.php')
            .$this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectContinuationCapability.php')
            .$this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectDoubleExecutionService.php');
        self::assertStringNotContainsString('ProcessIncarnation', $source);
        self::assertStringNotContainsString('forwardComplete', $source);
        self::assertStringNotContainsString('__serialize', $source);
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
