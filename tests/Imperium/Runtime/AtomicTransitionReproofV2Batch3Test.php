<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\ReproofV2Verifier;
use App\ReproofV2\Contract;
use App\ReproofV2\Records;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionReproofV2Batch3Test extends TestCase
{
    public function testIndependentDerivationPassesAllDomainsWithoutAdmittingSyntheticEvidence(): void
    {
        $package = ReproofV2SyntheticFixture::package();
        $report = (new ReproofV2Verifier())->verify($package, self::trust($package));
        self::assertSame('SYNTHETIC_PASS_NOT_ADMISSIBLE', $report['disposition']);
        self::assertSame(array_fill_keys(Contract::DOMAINS, 'PASS'), $report['domain_outcomes']);
        self::assertSame($package['candidate']['record_digest'], $report['candidate_digest']);
        self::assertFalse($report['qualification_removed']); self::assertFalse($report['campaign_closed']);
        self::assertSame(Contract::FIELDS['report'], array_keys($report));
        foreach (['receipt', 'source', 'matrix', 'graph', 'exclusion', 'authorization_digest'] as $field) {
            self::assertArrayNotHasKey($field, $report);
        }
    }

    public function testPreflightRefusesV1AndMissingInputsWithoutIntakeOrSigning(): void
    {
        $verifier = new ReproofV2Verifier();
        self::assertSame('INDETERMINATE_MISSING_PACKAGE', $verifier->preflight(null));
        self::assertSame('REFUSED_NOT_V2', $verifier->preflight(['receipt' => ['schema' => 'v1']]));
        $package = ReproofV2SyntheticFixture::package();
        self::assertSame('ELIGIBLE_FOR_VERIFICATION_NOT_AUTHORIZED', $verifier->preflight($package));
        array_pop($package['receipt']['matrix']['cases']);
        self::assertSame('REFUSED_MISSING_CASE_EVIDENCE', $verifier->preflight($package));
    }

    public function testSourceBindingAndCallerObservationsCannotBeSelfResealedIntoSuccess(): void
    {
        $package = ReproofV2SyntheticFixture::package();
        $trust = self::trust($package);
        $package['receipt']['matrix']['cases'][0]['observed']['classification'] = 'COMMITTED';
        self::reseal($package);
        self::assertSame('REFUSED', (new ReproofV2Verifier())->verify($package, $trust)['disposition']);
        $package = ReproofV2SyntheticFixture::package();
        $package['receipt']['source']['files']['src/ReproofV2/Runner.php']['bytes'] = base64_encode('<?php /* substituted */');
        $package['receipt']['source'] = Records::seal($package['receipt']['source']);
        self::reseal($package);
        $report = (new ReproofV2Verifier())->verify($package, $trust);
        self::assertSame('REFUSED', $report['domain_outcomes']['source_and_build']);
    }

    public function testIndependentDependencyClosureSharesOnlyNeutralHashingAndSerialization(): void
    {
        $root = dirname(__DIR__, 3);
        foreach (['ReproofV2Verifier', 'ReproofV2SourceProof', 'ReproofV2CaseEvaluator', 'ReproofV2Exclusion'] as $name) {
            $source = file_get_contents($root.'/src/IndependentVerification/'.$name.'.php');
            preg_match_all('/^use ([^;]+);/m', $source, $imports);
            self::assertSame(['App\\ReproofV2\\Records'], $imports[1], $name);
            foreach (['new Runner', 'new Classifier', 'new Validator', 'CaseProfile::', 'SourceBundle::', 'PayloadExclusion',
                'sodium_crypto_sign(', 'eval(', 'proc_open(', 'file_put_contents('] as $forbidden) {
                $code = '';
                foreach (token_get_all($source) as $token) {
                    if (is_array($token)) {
                        if (!in_array($token[0], [T_COMMENT, T_DOC_COMMENT, T_CONSTANT_ENCAPSED_STRING], true)) { $code .= $token[1]; }
                    } else { $code .= $token; }
                }
                self::assertStringNotContainsString($forbidden, $code);
            }
        }
        $records = file_get_contents($root.'/src/ReproofV2/Records.php');
        preg_match_all('/^use ([^;]+);/m', $records, $imports);
        self::assertSame(['App\\Bootstrap\\CanonicalJson'], $imports[1]);
        $canonical = file_get_contents($root.'/src/Bootstrap/CanonicalJson.php');
        self::assertDoesNotMatchRegularExpression('/^use /m', $canonical);
    }

    public static function trust(array $package): array
    {
        // Test-only anchor. The operational trust record must be supplied independently by the operator.
        return ['proof_id' => $package['receipt']['proof_id'], 'source_commit' => $package['receipt']['source']['commit'],
            'source_manifest_root' => $package['receipt']['source']['manifest_root'],
            'authorization_digest' => Records::hash(['synthetic_authorization' => false]), 'runtime_version' => PHP_VERSION,
            'verifier_root' => ReproofV2Verifier::implementationRoot(), 'identity_digest' => Records::hash(['synthetic_identity' => true]),
            'evidence_class' => 'SYNTHETIC_TEST'];
    }

    public static function reseal(array &$package): void
    {
        foreach ($package['receipt']['matrix']['cases'] as &$case) {
            foreach (['input', 'expected', 'observed'] as $kind) { $case[$kind] = Records::seal($case[$kind]); }
        }
        unset($case);
        $package['receipt']['matrix'] = Records::seal($package['receipt']['matrix']);
        $package['receipt'] = Records::seal($package['receipt']);
        $package['candidate']['receipt_digest'] = $package['receipt']['record_digest'];
        $package['candidate'] = Records::seal($package['candidate']);
    }
}
