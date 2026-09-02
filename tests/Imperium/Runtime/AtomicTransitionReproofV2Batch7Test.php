<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\ReproofV2AdmissionConsumer as Admission;
use App\ReproofV2\Records;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionReproofV2Batch7Test extends TestCase
{
    public function testPinnedSignedPublicEvidenceAdmitsOnlyPendingTerminalAudit(): void
    {
        $p = $this->fixture(); $now = new \DateTimeImmutable($p['identity']['not_before']);
        $result = (new Admission($p['anchor']))->admit($p['candidate'], $p['identity'], $p['report'], $p['attestation'], $now);
        self::assertSame(Admission::FIELDS, array_keys($result));
        self::assertSame(Admission::STATUS, $result['disposition']);
        self::assertSame($result, Records::seal($result));
        foreach (['candidate', 'identity', 'report', 'attestation'] as $kind) { self::assertSame($p[$kind]['record_digest'], $result[$kind.'_digest']); }
        self::assertFalse($result['qualification_removed']); self::assertFalse($result['campaign_closed']);
        self::assertFalse($result['continuing_authority']);
        self::assertSame('BOUND_INACTIVE', $result['provider_binding_status']);
        self::assertSame('NOT_IMPLEMENTED', $result['required_v3_execution_admission']);
        self::assertSame('UNKNOWN_REPLAY_PROHIBITED', $result['unknown_replay_posture']);
    }

    public static function counterfeits(): iterable
    {
        foreach (['unsigned', 'signature', 'key', 'identity', 'purpose', 'v1', 'producer-report', 'indeterminate',
            'synthetic', 'missing-domain', 'extra-domain', 'receipt', 'source', 'case-root', 'verifier', 'closure', 'private-field'] as $name) { yield $name => [$name]; }
    }

    #[DataProvider('counterfeits')]
    public function testResealedPublicSubstitutionsRefuse(string $name): void
    {
        $p = $this->fixture();
        switch ($name) {
            case 'unsigned': $p['attestation']['signature'] = null; break;
            case 'signature': $p['attestation']['signature'] = str_repeat('0', 128); break;
            case 'key': $p['identity']['public_key'] = str_repeat('0', 64); break;
            case 'identity': $p['report']['trusted_identity_digest'] = str_repeat('a', 64); break;
            case 'purpose': $p['attestation']['purpose'] = 'another-purpose'; break;
            case 'v1': $p['candidate']['schema'] = 'imperium.sanitized-atomic-transition-integrated-disposable-mission-evidence/v1'; break;
            case 'producer-report': $p['report']['schema'] = $p['candidate']['schema']; break;
            case 'indeterminate': $p['report']['domain_outcomes']['acceptance_matrix'] = 'INDETERMINATE'; break;
            case 'synthetic': $p['report']['disposition'] = 'SYNTHETIC_PASS_NOT_ADMISSIBLE'; break;
            case 'missing-domain': unset($p['report']['domain_outcomes']['acceptance_matrix']); break;
            case 'extra-domain': $p['report']['domain_outcomes']['producer-says-pass'] = 'PASS'; break;
            case 'receipt': $p['report']['receipt_digest'] = str_repeat('a', 64); break;
            case 'source': $p['report']['source_commit'] = str_repeat('a', 40); break;
            case 'case-root': $p['candidate']['input_root'] = str_repeat('a', 64); break;
            case 'verifier': $p['report']['verifier_root'] = str_repeat('a', 64); break;
            case 'closure': $p['report']['qualification_removed'] = true; break;
            case 'private-field': $p['candidate']['private_key'] = 'synthetic-forbidden'; break;
        }
        foreach (['candidate', 'identity', 'report', 'attestation'] as $kind) { $p[$kind] = Records::seal($p[$kind]); }
        $this->expectExceptionMessage('REPROOF_V2_ADMISSION_REFUSED');
        (new Admission($p['anchor']))->admit($p['candidate'], $p['identity'], $p['report'], $p['attestation'], new \DateTimeImmutable($p['identity']['not_before']));
    }

    public function testCallerCannotReplaceTheOperatorProvisionedAnchor(): void
    {
        $p = $this->fixture(); $p['anchor']['public_key_digest'] = str_repeat('0', 64);
        $p['anchor'] = Records::seal($p['anchor']);
        $this->expectExceptionMessage('REPROOF_V2_UNTRUSTED_ADMISSION_ANCHOR');
        new Admission($p['anchor']);
    }

    public static function invalidTimes(): iterable
    {
        yield 'before-identity' => ['2026-09-02T18:52:53Z'];
        yield 'exact-expiry' => ['2026-09-03T18:52:54Z'];
        yield 'after-expiry' => ['2026-09-04T18:52:54Z'];
    }

    #[DataProvider('invalidTimes')]
    public function testAdmissionRequiresIdentityValidAtTrustedLocalTime(string $time): void
    {
        $p = $this->fixture(); $this->expectExceptionMessage('REPROOF_V2_ADMISSION_REFUSED');
        (new Admission($p['anchor']))->admit($p['candidate'], $p['identity'], $p['report'], $p['attestation'], new \DateTimeImmutable($time));
    }

    public function testAdmissionRemainsExcludedFromRuntimeServiceDiscovery(): void
    {
        $root = dirname(__DIR__, 3);
        self::assertStringContainsString("- '../src/IndependentVerification/ReproofV2*'", file_get_contents($root.'/config/services.yaml'));
        $source = file_get_contents($root.'/src/IndependentVerification/ReproofV2AdmissionConsumer.php');
        foreach (['sodium_crypto_sign_detached(', 'file_get_contents(', 'file_put_contents(', 'ReproofV2Verifier', 'new Runner', 'getenv('] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
    }

    public function testRetainedAdmissionBindsActualEvidenceAndHistoricalAdmissionTime(): void
    {
        $p = $this->fixture();
        $retained = json_decode(file_get_contents(dirname(__DIR__, 3).'/docs/evidence/atomic-transition-reproof-v2-proof-2-admission.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame('d2048a13c5b01ebf8d20ae85a885976b1487343778bbe3b6ec17f00771622dc1', $retained['record_digest']);
        $time = new \DateTimeImmutable($retained['admitted_at']);
        self::assertGreaterThanOrEqual(new \DateTimeImmutable($p['identity']['not_before']), $time);
        self::assertLessThan(new \DateTimeImmutable($p['identity']['expires_at']), $time);
        self::assertSame($retained, (new Admission($p['anchor']))->admit($p['candidate'], $p['identity'], $p['report'], $p['attestation'], $time));
        $handoff = file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/atomic-transition-reproof-v2-batch-7-complete.md');
        foreach (['One stage remains', 'merged Batch 7 main', 'qualification_removed=false', 'campaign_closed=false',
            'CAMPAIGN_CLOSURE_REQUALIFIED_WITH_MATERIAL_INDEPENDENT_VERIFICATION_DEFECT'] as $boundary) {
            self::assertStringContainsString($boundary, $handoff);
        }
    }

    private function fixture(): array
    {
        $result = [];
        foreach (['anchor' => 'trust-anchor', 'candidate' => 'candidate', 'identity' => 'identity', 'report' => 'report', 'attestation' => 'attestation'] as $name => $suffix) {
            $result[$name] = json_decode(file_get_contents(dirname(__DIR__, 3).'/docs/evidence/atomic-transition-reproof-v2-proof-2-'.$suffix.'.json'), true, flags: JSON_THROW_ON_ERROR);
        }
        return $result;
    }
}
