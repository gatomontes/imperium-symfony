<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\ReproofV2Exclusion;
use App\IndependentVerification\ReproofV2SourceProof;
use App\IndependentVerification\ReproofV2Verifier;
use App\ReproofV2\PackageStore;
use App\ReproofV2\Records;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionReproofV2Batch4Test extends TestCase
{
    public static function mutations(): iterable
    {
        foreach (['missing-case', 'reordered-case', 'duplicate-case', 'extra-case', 'wrong-observation',
            'wrong-expectation', 'placeholder-input-root', 'changed-root', 'broken-journal-join',
            'opaque-reference', 'live-effect-flag', 'mutation-substitution', 'partial-as-complete',
            'contender-as-replay', 'changed-as-replay', 'extra-payload-slot', 'secret-payload',
            'injected-conclusion', 'graph-omission', 'graph-edge-substitution', 'exclusion-self-assertion',
            'candidate-root', 'candidate-private-field', 'candidate-success', 'origin-authorization',
            'stale-proof-id', 'runtime-substitution', 'wrong-verifier-root', 'wrong-identity',
            'source-byte-substitution', 'source-file-omission', 'source-file-extra'] as $name) { yield $name => [$name]; }
    }

    #[DataProvider('mutations')]
    public function testSelfResealedCounterfeitsRemainRefused(string $name): void
    {
        $package = ReproofV2SyntheticFixture::package();
        $trust = AtomicTransitionReproofV2Batch3Test::trust($package);
        $matrix =& $package['receipt']['matrix'];
        switch ($name) {
            case 'missing-case': array_pop($matrix['cases']); break;
            case 'reordered-case': [$matrix['cases'][0], $matrix['cases'][1]] = [$matrix['cases'][1], $matrix['cases'][0]]; break;
            case 'duplicate-case': $matrix['cases'][1] = $matrix['cases'][0]; break;
            case 'extra-case': $matrix['cases'][] = $matrix['cases'][0]; break;
            case 'wrong-observation': $matrix['cases'][0]['observed']['classification'] = 'COMMITTED'; break;
            case 'wrong-expectation': $matrix['cases'][0]['expected']['classification'] = 'COMMITTED'; break;
            case 'changed-root': $matrix['cases'][0]['input']['root'] = 'reproof-v2-another-root'; break;
            case 'broken-journal-join': $matrix['cases'][3]['input']['primary']['winner']['transaction_journal']['digest'] = str_repeat('a', 64); break;
            case 'opaque-reference': $matrix['cases'][3]['input']['auxiliary']['decision']['extra'] = 'opaque'; break;
            case 'live-effect-flag': $matrix['cases'][3]['input']['primary']['winner']['authority_consumed'] = true; break;
            case 'mutation-substitution': $matrix['cases'][5]['input']['mutation']['replacement'] = null; break;
            case 'partial-as-complete': $matrix['cases'][7]['input']['primary'] = $matrix['cases'][3]['input']['primary']; break;
            case 'contender-as-replay': $matrix['cases'][6]['input']['comparison'] = $matrix['cases'][6]['input']['primary']; break;
            case 'changed-as-replay': $matrix['cases'][5]['input']['comparison'] = $matrix['cases'][5]['input']['primary']; break;
            case 'extra-payload-slot': $matrix['cases'][0]['input']['free_text'] = 'opaque'; break;
            case 'secret-payload': $matrix['cases'][0]['input']['auxiliary']['decision']['kind'] = base64_encode('REPROOF_SYNTHETIC_FORBIDDEN'); break;
            case 'injected-conclusion': $package['receipt']['disposition'] = 'PASS'; break;
            case 'graph-omission': array_pop($package['receipt']['graph']['nodes']); break;
            case 'graph-edge-substitution': $package['receipt']['graph']['nodes'][1]['imports'][] = 'App\\UnexpectedCapability'; break;
            case 'exclusion-self-assertion': $package['receipt']['exclusion']['synthetic_negative_vectors']['plain'] = 'PASS'; break;
            case 'candidate-root': $package['candidate']['input_root'] = str_repeat('a', 64); break;
            case 'candidate-private-field': $package['candidate']['private_locator'] = 'synthetic-private-locator'; break;
            case 'candidate-success': $package['candidate']['disposition'] = 'PASS'; break;
            case 'origin-authorization': $package['receipt']['origin']['authorization_digest'] = str_repeat('a', 64); break;
            case 'stale-proof-id': $package['receipt']['origin']['proof_id'] = 'reproof-v2-previous-event'; break;
            case 'runtime-substitution': $package['receipt']['origin']['runtime_version'] = '8.4.0'; break;
            case 'wrong-verifier-root': $trust['verifier_root'] = str_repeat('a', 64); break;
            case 'wrong-identity': $package['candidate']['trusted_identity_digest'] = str_repeat('a', 64); break;
            case 'source-byte-substitution': $package['receipt']['source']['files']['src/ReproofV2/Runner.php']['bytes'] = base64_encode('<?php return true;'); break;
            case 'source-file-omission': unset($package['receipt']['source']['files']['src/ReproofV2/Runner.php']); break;
            case 'source-file-extra': $package['receipt']['source']['files']['src/Unexpected.php'] = ['blob' => str_repeat('a', 40), 'bytes' => base64_encode('<?php')]; break;
        }
        self::rebind($package);
        if ('candidate-root' === $name) {
            $package['candidate']['input_root'] = str_repeat('a', 64);
            $package['candidate'] = Records::seal($package['candidate']);
        }
        if ('placeholder-input-root' === $name) {
            $package['receipt']['matrix']['input_root'] = str_repeat('a', 64);
            $package['receipt']['origin']['input_root'] = str_repeat('a', 64);
            $package['candidate']['input_root'] = str_repeat('a', 64);
            self::sealOuter($package);
        }
        $report = (new ReproofV2Verifier())->verify($package, $trust);
        self::assertSame('REFUSED', $report['disposition'], $name);
        if (in_array($name, ['missing-case', 'reordered-case', 'duplicate-case', 'extra-case', 'wrong-observation',
            'wrong-expectation', 'changed-root', 'broken-journal-join', 'opaque-reference', 'live-effect-flag',
            'mutation-substitution', 'partial-as-complete', 'contender-as-replay', 'changed-as-replay',
            'extra-payload-slot', 'secret-payload'], true)) {
            self::assertSame('PASS', $report['domain_outcomes']['origin_and_provenance'], 'Counterfeit must reach semantic evaluation');
            self::assertSame('REFUSED', $report['domain_outcomes']['acceptance_matrix']);
        }
        self::assertFalse($report['qualification_removed']); self::assertFalse($report['campaign_closed']);
        $public = json_encode($report, JSON_THROW_ON_ERROR);
        self::assertStringNotContainsString('SYNTHETIC_FORBIDDEN', $public);
        self::assertStringNotContainsString('synthetic-private-locator', $public);
    }

    public function testTreeMembershipIsRequiredEvenAfterAllSourceHashesAreRecomputed(): void
    {
        $source = ReproofV2SyntheticFixture::source();
        $path = 'src/ReproofV2/Runner.php'; $bytes = '<?php /* harmless substituted source */';
        $source['files'][$path] = ['blob' => hash('sha1', 'blob '.strlen($bytes)."\0".$bytes), 'bytes' => base64_encode($bytes)];
        $manifest = [];
        foreach ($source['files'] as $name => $file) {
            $manifest[$name] = ['blob' => $file['blob'], 'sha256' => hash('sha256', base64_decode($file['bytes']))];
        }
        $source['manifest_root'] = Records::hash($manifest); $source = Records::seal($source);
        // Deliberately pin the counterfeit manifest here to isolate commit/tree/blob checking.
        $trust = ['source_commit' => $source['commit'], 'source_manifest_root' => $source['manifest_root']];
        $this->expectExceptionMessage('REPROOF_SOURCE_PROOF_REFUSED');
        (new ReproofV2SourceProof())->verify($source, $trust);
    }

    public static function malformed(): iterable
    {
        foreach (['receipt-null', 'source-trees-string', 'case-input-null', 'base-case-missing', 'cases-string'] as $name) { yield $name => [$name]; }
    }

    #[DataProvider('malformed')]
    public function testMalformedPrivateInputCannotLeakWarnings(string $name): void
    {
        $package = ReproofV2SyntheticFixture::package();
        $trust = AtomicTransitionReproofV2Batch3Test::trust($package);
        switch ($name) {
            case 'receipt-null': $package['receipt'] = null; break;
            case 'source-trees-string': $package['receipt']['source']['trees'] = 'synthetic-private-data'; break;
            case 'case-input-null': $package['receipt']['matrix']['cases'][0]['input'] = null; break;
            case 'base-case-missing': unset($package['receipt']['matrix']['cases'][3]['input']['primary']); break;
            case 'cases-string': $package['receipt']['matrix']['cases'] = 'synthetic-private-data'; break;
        }
        if (is_array($package['receipt'])) {
            if (is_array($package['receipt']['source'])) { $package['receipt']['source'] = Records::seal($package['receipt']['source']); }
            $package['receipt']['origin']['source_digest'] = $package['receipt']['source']['record_digest'];
            $package['receipt']['matrix'] = Records::seal($package['receipt']['matrix']);
            self::sealOuter($package);
        }
        $warnings = [];
        set_error_handler(static function (int $code, string $message) use (&$warnings): bool { $warnings[] = $code; return true; });
        try {
            $report = (new ReproofV2Verifier())->verify($package, $trust);
            self::assertSame('REFUSED', $report['disposition']);
            self::assertSame([], $warnings, 'A private-input diagnostic escaped');
        } finally { restore_error_handler(); }
    }

    public function testIndependentExclusionObservesEverySafeNegativeEncoding(): void
    {
        $marker = 'REPROOF_SYNTHETIC_FORBIDDEN';
        $vectors = [$marker, base64_encode($marker), bin2hex($marker), '%52'.substr($marker, 1),
            ['REPROOF_SYNTHETIC_', 'FORBIDDEN'], base64_encode(base64_encode($marker)), ['private_key' => 'synthetic-only']];
        foreach ($vectors as $vector) {
            try { (new ReproofV2Exclusion())->scan($vector); self::fail('Synthetic exclusion vector accepted'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_INDEPENDENT_EXCLUSION_REFUSED', $e->getMessage()); }
        }
    }

    public static function cuts(): iterable
    {
        foreach (['reserved', 'receipt-only', 'candidate-no-final', 'truncated-receipt', 'truncated-candidate', 'truncated-final', 'stale-final'] as $cut) { yield $cut => [$cut]; }
    }

    #[DataProvider('cuts')]
    public function testInterruptedPublicationCannotBeReadOrReexecuted(string $cut): void
    {
        $parent = sys_get_temp_dir().'/imperium-reproof-cut-'.uniqid();
        mkdir($parent); $store = new PackageStore(); $directory = $store->reserve($parent, 'reproof-v2-synthetic-test');
        try {
            $package = ReproofV2SyntheticFixture::package();
            if ('reserved' !== $cut) { $store->publish($directory, $package); }
            switch ($cut) {
                case 'receipt-only': unlink($directory.'/candidate.json'); unlink($directory.'/finalized.json'); break;
                case 'candidate-no-final': unlink($directory.'/finalized.json'); break;
                case 'truncated-receipt': file_put_contents($directory.'/receipt.json', '{'); break;
                case 'truncated-candidate': file_put_contents($directory.'/candidate.json', '{'); break;
                case 'truncated-final': file_put_contents($directory.'/finalized.json', '{'); break;
                case 'stale-final':
                    $final = json_decode(file_get_contents($directory.'/finalized.json'), true);
                    $final['proof_id'] = 'reproof-v2-stale-event';
                    file_put_contents($directory.'/finalized.json', json_encode($final)); break;
            }
            try { $store->readFinalized($directory); self::fail('Incomplete package accepted'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_PACKAGE_INCOMPLETE', $e->getMessage()); }
            try { $store->reserve($parent, 'reproof-v2-synthetic-test'); self::fail('Interrupted mission retried'); }
            catch (\RuntimeException $e) { self::assertSame('REPROOF_RESERVATION_EXISTS_OR_FAILED', $e->getMessage()); }
        } finally {
            foreach (['reservation', 'receipt', 'candidate', 'finalized'] as $name) {
                $file = $directory.'/'.$name.'.json'; if (is_file($file)) { unlink($file); }
            }
            rmdir($directory); rmdir($parent);
        }
    }

    private static function rebind(array &$package): void
    {
        $matrix =& $package['receipt']['matrix'];
        foreach ($matrix['cases'] as &$case) {
            foreach (['primary', 'comparison'] as $side) {
                if (is_array($case['input'][$side])) {
                    foreach ($case['input'][$side] as &$record) { $record = Records::seal($record); } unset($record);
                }
            }
            $case['input'] = Records::seal($case['input']); $case['expected'] = Records::seal($case['expected']);
            $case['observed']['input_digest'] = $case['input']['record_digest'];
            $case['observed']['expected_digest'] = $case['expected']['record_digest'];
            $case['observed'] = Records::seal($case['observed']);
        }
        unset($case);
        foreach (['input', 'expected', 'observed'] as $kind) {
            $matrix[$kind.'_root'] = Records::hash(array_column(array_column($matrix['cases'], $kind), 'record_digest'));
        }
        foreach (['input_root', 'expected_root'] as $key) { $package['receipt']['origin'][$key] = $matrix[$key]; }
        foreach (['input_root', 'expected_root', 'observed_root'] as $key) { $package['candidate'][$key] = $matrix[$key]; }
        $package['receipt']['source'] = Records::seal($package['receipt']['source']);
        $package['receipt']['origin']['source_digest'] = $package['receipt']['source']['record_digest'];
        self::sealOuter($package);
    }

    public function testExactExecutionRequestRemainsUnapprovedAndSeparatesSigning(): void
    {
        $root = dirname(__DIR__, 3);
        $request = json_decode(file_get_contents($root.'/docs/atomic-transition-reproof-v2-execution-request.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('REQUEST_NOT_AUTHORIZATION', $request['status']);
        self::assertSame(1, $request['maximum_executions']);
        self::assertSame(['-n'], $request['php_options']);
        foreach (['provider_authorized', 'network_authorized', 'live_runtime_state_write_authorized',
            'signing_authorized', 'admission_authorized', 'closure_authorized'] as $field) { self::assertFalse($request[$field]); }
        $handoff = file_get_contents($root.'/docs/handoffs/atomic-transition-reproof-v2-batch-5-execution-approval.md');
        self::assertStringContainsString(Records::hash($request), $handoff);
        self::assertStringContainsString($request['source_commit'], $handoff);
        self::assertStringContainsString($request['source_manifest_root'], $handoff);
        self::assertStringContainsString('only after the operator approves this exact request', $handoff);
        self::assertStringContainsString('CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT', $handoff);
    }

    private static function sealOuter(array &$package): void
    {
        $package['receipt']['matrix'] = Records::seal($package['receipt']['matrix']);
        $package['receipt']['origin'] = Records::seal($package['receipt']['origin']);
        foreach (['origin', 'matrix', 'graph'] as $key) { $package['receipt']['exclusion']['sections'][$key] = Records::hash($package['receipt'][$key]); }
        $package['receipt'] = Records::seal($package['receipt']);
        $package['candidate']['receipt_digest'] = $package['receipt']['record_digest'];
        $package['candidate']['origin_digest'] = $package['receipt']['origin']['record_digest'];
        $package['candidate'] = Records::seal($package['candidate']);
    }
}
