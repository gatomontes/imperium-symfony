<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectCorridorActivationPreparationBatch0Test extends TestCase
{
    private const array ARTIFACTS = [
        'docs/canonical-native-effect-corridor-activation-preparation-inventory-v1.md',
        'docs/canonical-native-effect-corridor-activation-call-graph-v1.md',
        'docs/canonical-native-effect-corridor-activation-authority-effect-cut-matrix-v1.md',
        'docs/canonical-native-effect-corridor-activation-reading-ledger-v1.json',
        'docs/handoffs/canonical-native-effect-corridor-activation-preparation-batch-0-complete.md',
    ];

    public function testPreparationArtifactsExistAndPreserveTheHardStop(): void
    {
        foreach (self::ARTIFACTS as $path) {
            $contents = (string) file_get_contents($this->root().'/'.$path);
            self::assertNotSame('', $contents, $path);
            self::assertStringContainsString('Batch 0', $contents, $path);
        }

        $all = implode("\n", array_map(
            fn (string $path): string => (string) file_get_contents($this->root().'/'.$path),
            self::ARTIFACTS,
        ));
        foreach (['LIVE_EFFECT_AUTHORITY_ABSENT', 'UNKNOWN_REPLAY_PROHIBITED', 'Batch 1 is not authorized'] as $marker) {
            self::assertStringContainsString($marker, $all);
        }
    }

    public function testInventoryUsesOnlyTheCanonicalClassificationsAndNamesEveryRequiredBoundary(): void
    {
        $inventory = (string) file_get_contents($this->root().'/'.self::ARTIFACTS[0]);
        preg_match_all('/`(EXISTS_CANONICALLY|EXISTS_FRAGMENTED|ABSENT|DEFERRED_BOUNDARY)`/', $inventory, $matches);
        self::assertGreaterThanOrEqual(40, count($matches[0]));
        self::assertSame([], array_values(array_diff(array_unique($matches[1]), [
            'EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY',
        ])));
        foreach (['credential', 'idempotency', 'revocation', 'cancellation', 'Lazaretto', 'callback response', 'live-trial', 'Multi-host'] as $surface) {
            self::assertStringContainsString($surface, $inventory);
        }
    }

    public function testCallGraphAndCutMatrixPinTheIrreversibleCutLockOrderAndEightStages(): void
    {
        $graph = (string) file_get_contents($this->root().'/'.self::ARTIFACTS[1]);
        $matrix = (string) file_get_contents($this->root().'/'.self::ARTIFACTS[2]);
        foreach (['native-provider-transition', 'canonical-native-effect-authority', 'canonical-native-effect:', 'successful atomic rename', 'before credential'] as $proof) {
            self::assertStringContainsString($proof, $graph);
        }
        foreach (range(1, 8) as $batch) {
            self::assertStringContainsString('Batch '.$batch, $matrix);
        }
        self::assertStringContainsString('forward recovery is not retry', $matrix);
    }

    public function testReadingLedgerIsVersionedAndPinsTheReviewedBaseline(): void
    {
        $ledger = json_decode(
            (string) file_get_contents($this->root().'/'.self::ARTIFACTS[3]),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        self::assertSame('imperium.canonical-native-effect-corridor-activation-reading-ledger/v1', $ledger['schema']);
        self::assertSame('3f5d53ce8dfff0a74702b32500696373823b5e41', $ledger['audited_main']);
        self::assertGreaterThanOrEqual(40, count($ledger['sources']));
        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $source['normalized_sha256']);
            self::assertGreaterThan(0, $source['lines']);
        }
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
