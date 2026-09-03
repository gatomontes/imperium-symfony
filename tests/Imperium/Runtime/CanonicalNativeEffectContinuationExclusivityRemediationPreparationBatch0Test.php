<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectContinuationExclusivityRemediationPreparationBatch0Test extends TestCase
{
    private const array ARTIFACTS = [
        'docs/canonical-native-effect-continuation-exclusivity-remediation-preparation-inventory-v1.md',
        'docs/canonical-native-effect-continuation-exclusivity-remediation-corrected-call-graph-v1.md',
        'docs/canonical-native-effect-continuation-exclusivity-remediation-identity-lock-matrix-v1.md',
        'docs/canonical-native-effect-continuation-exclusivity-remediation-adversarial-proof-matrix-v1.md',
        'docs/canonical-native-effect-continuation-exclusivity-remediation-reading-ledger-v1.json',
        'docs/handoffs/canonical-native-effect-continuation-exclusivity-remediation-preparation-batch-0-complete.md',
    ];

    public function testVersionedArtifactsExistAndPreserveTheStop(): void
    {
        foreach (self::ARTIFACTS as $path) {
            self::assertFileExists($this->root().'/'.$path, $path);
            self::assertNotSame('', $this->read($path), $path);
        }
        $all = implode("\n", array_map($this->read(...), self::ARTIFACTS));
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_CONTINUATION_EXCLUSIVITY_GAPS_CLASSIFIED',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'Batch 1 is not authorized',
            'No production runtime behavior',
        ] as $stop) {
            self::assertStringContainsStringIgnoringCase($stop, $all, $stop);
        }
    }

    public function testInventoryClassifiesEveryRequiredSurfaceAndBlackquillCounterexample(): void
    {
        $inventory = $this->read(self::ARTIFACTS[0]);
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY'] as $classification) {
            self::assertStringContainsString('`'.$classification.'`', $inventory);
        }
        foreach ([
            'BQ-CNE-01', 'BQ-CNE-02', 'BQ-CNE-03', 'BQ-CNE-04',
            'fresh process', 'losing authority', 'old digest', '48,253', '48,255',
        ] as $surface) {
            self::assertStringContainsStringIgnoringCase($surface, $inventory, $surface);
        }
    }

    public function testGraphAndIdentityMatrixPinTheCorrectedCustodyTupleAndLockOrder(): void
    {
        $graph = $this->read(self::ARTIFACTS[1]);
        $matrix = $this->read(self::ARTIFACTS[2]);
        foreach ([
            'NO continuation object', 'FRESH PROCESS MAY START FIRST CALLBACK',
            'same uninterrupted process', 'forwardComplete',
        ] as $edge) {
            self::assertStringContainsStringIgnoringCase($edge, $graph, $edge);
        }
        foreach ([
            'semantic_effect_tuple_id', 'authority_consumption_id',
            'native-provider-transition', 'canonical-native-effect-tuple:',
            'losing authority remains unconsumed', 'never reconstruct/reissue',
        ] as $identity) {
            self::assertStringContainsStringIgnoringCase($identity, $matrix, $identity);
        }
    }

    public function testAdversarialMatrixCoversProcessContentionSubstitutionAndAllCuts(): void
    {
        $matrix = $this->read(self::ARTIFACTS[3]);
        foreach ([
            'Distinct authorities, identical semantic tuple, simultaneous processes',
            'Admit-and-exit, then fresh process first continuation',
            'Tamper expected-return contract, retain old digest',
            'Exit after admission rename before return',
            'Exit after response rename before receipt',
            'Concurrent revocation vs first winner',
            'Auto-discovered facade', 'local and CI totals',
        ] as $case) {
            self::assertStringContainsStringIgnoringCase($case, $matrix, $case);
        }
    }

    public function testReadingLedgerPinsBaselineAndEveryListedSource(): void
    {
        $ledger = json_decode($this->read(self::ARTIFACTS[4]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.canonical-native-effect-continuation-exclusivity-remediation-reading-ledger/v1', $ledger['schema']);
        self::assertSame('77d26f4c7f5655dcce67b5c3765714b5c0ede85e', $ledger['audited_main']);
        self::assertGreaterThanOrEqual(40, count($ledger['sources']));
        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status'], $source['path']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $source['normalized_sha256'], $source['path']);
            self::assertGreaterThan(0, $source['lines'], $source['path']);
        }
        self::assertStringContainsString('Only CanonicalNativeEffectCorridor constructs', $ledger['call_site_scan']['production_result']);
    }

    private function read(string $path): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
