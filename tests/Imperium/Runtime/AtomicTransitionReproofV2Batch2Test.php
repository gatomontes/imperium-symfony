<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Contract;
use App\ReproofV2\PackageStore;
use App\ReproofV2\PayloadExclusion;
use App\ReproofV2\Records;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionReproofV2Batch2Test extends TestCase
{
    public function testRunnerRetainsEightBoundCasesWithActualRuntimeObservations(): void
    {
        $package = ReproofV2SyntheticFixture::package();
        $matrix = $package['receipt']['matrix'];
        self::assertSame(Contract::CASES, array_column(array_column($matrix['cases'], 'input'), 'case_id'));
        foreach ($matrix['cases'] as $case) {
            foreach (['classification', 'directive', 'comparison', 'validator_error', 'findings'] as $field) {
                self::assertSame($case['expected'][$field], $case['observed'][$field]);
            }
            self::assertSame($case['input']['record_digest'], $case['observed']['input_digest']);
            self::assertSame($case['expected']['record_digest'], $case['observed']['expected_digest']);
        }
        foreach (['input', 'expected', 'observed'] as $kind) {
            self::assertSame(Records::hash(array_column(array_column($matrix['cases'], $kind), 'record_digest')), $matrix[$kind.'_root']);
        }
        self::assertSame('INCOMPLETE', $matrix['cases'][7]['observed']['classification']);
        self::assertArrayNotHasKey('winner', $matrix['cases'][7]['input']['primary']);
        self::assertSame('CANDIDATE_NOT_VERIFIED', $package['candidate']['disposition']);
        self::assertSame(Contract::RETENTION, $package['candidate']['retention']);
        self::assertSame(['origin', 'matrix', 'graph'], array_keys($package['receipt']['exclusion']['sections']));
    }

    public function testSyntheticPublicationFinalizesOnceAndRefusesReplay(): void
    {
        $parent = sys_get_temp_dir().'/imperium-reproof-unit-'.uniqid();
        self::assertTrue(mkdir($parent));
        $store = new PackageStore();
        $directory = $store->reserve($parent, 'reproof-v2-synthetic-test');
        try {
            try { $store->readFinalized($directory); self::fail('Unfinalized package accepted'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_PACKAGE_INCOMPLETE', $e->getMessage()); }
            $package = ReproofV2SyntheticFixture::package();
            $store->publish($directory, $package);
            self::assertSame($package, $store->readFinalized($directory));
            try { $store->reserve($parent, 'reproof-v2-synthetic-test'); self::fail('Replay reserved'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_RESERVATION_EXISTS_OR_FAILED', $e->getMessage()); }
            try { $store->publish($directory, $package); self::fail('Overwrite accepted'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_WRITE_REFUSED', $e->getMessage()); }
            self::assertSame($package, $store->readFinalized($directory));
        } finally {
            foreach (['reservation', 'receipt', 'candidate', 'finalized'] as $name) {
                $file = $directory.'/'.$name.'.json';
                if (is_file($file)) { unlink($file); }
            }
            rmdir($directory); rmdir($parent);
        }
    }

    public function testSyntheticForbiddenEncodingsAndSplitFormsAreRefused(): void
    {
        $marker = 'REPROOF_SYNTHETIC_FORBIDDEN';
        foreach ([$marker, base64_encode($marker), bin2hex($marker), rawurlencode('Bearer synthetic-only'),
            ['REPROOF_SYNTHETIC_', 'FORBIDDEN'], ['private_key' => 'synthetic-only']] as $value) {
            try { (new PayloadExclusion())->check($value); self::fail('Forbidden synthetic vector accepted'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_PAYLOAD_REFUSED', $e->getMessage()); }
        }
    }

    public function testCliIsGuardedAndRunnerExcludedFromContainer(): void
    {
        $root = dirname(__DIR__, 3);
        $source = file_get_contents($root.'/tools/run-atomic-transition-reproof-v2.php');
        self::assertStringContainsString("realpath(\$_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__", $source);
        self::assertStringNotContainsString('vendor/autoload', $source);
        self::assertStringNotContainsString('getenv(', $source);
        self::assertStringContainsString("- '../src/ReproofV2/'", file_get_contents($root.'/config/services.yaml'));
    }
}
