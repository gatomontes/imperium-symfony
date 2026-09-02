<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\ReproofV2\Records;
use PHPUnit\Framework\TestCase;

/** No private intake, key generation or signature operation occurs in these preparation checks. */
final class AtomicTransitionReproofV2Batch6ReadinessTest extends TestCase
{
    public function testRequestPinsTheProducedCandidateAndSeparateUnapprovedCustody(): void
    {
        $root = dirname(__DIR__, 3);
        $request = json_decode(file_get_contents($root.'/docs/atomic-transition-reproof-v2-verification-signing-request.json'), true, flags: JSON_THROW_ON_ERROR);
        $candidate = json_decode(file_get_contents($root.'/docs/evidence/atomic-transition-reproof-v2-proof-2-candidate.json'), true, flags: JSON_THROW_ON_ERROR);
        foreach (['proof_id', 'source_commit', 'source_manifest_root', 'receipt_digest'] as $field) { self::assertSame($candidate[$field], $request[$field]); }
        self::assertSame($candidate['record_digest'], $request['candidate_digest']);
        self::assertSame('REQUEST_NOT_AUTHORIZATION', $request['status']);
        self::assertSame(1, $request['maximum_signatures']); self::assertSame(1, $request['maximum_verifications']);
        self::assertSame('ALL_EIGHT_INDEPENDENT_DOMAINS_PASS', $request['signing_requires']);
        self::assertNotSame($request['receipt_directory'], $request['new_signing_directory']);
        foreach (['provider_authorized', 'network_authorized', 'mission_retry_authorized',
            'live_runtime_state_write_authorized', 'admission_authorized', 'closure_authorized'] as $field) { self::assertFalse($request[$field]); }
        $handoff = file_get_contents($root.'/docs/handoffs/atomic-transition-reproof-v2-batch-6-verification-signing-approval.md');
        self::assertStringContainsString(Records::hash($request), $handoff);
        self::assertStringContainsString(hash_file('sha256', $root.'/tools/verify-and-sign-atomic-transition-reproof-v2.php'), $handoff);
    }

    public function testControllerHasNoProducerEvaluatorOrExistingKeyInput(): void
    {
        $source = file_get_contents(dirname(__DIR__, 3).'/tools/verify-and-sign-atomic-transition-reproof-v2.php');
        self::assertStringContainsString("realpath(\$_SERVER['SCRIPT_FILENAME'] ?? '') !== __FILE__", $source);
        foreach (['vendor/autoload', 'new Runner', 'new Classifier', 'CaseProfile', 'getenv(', 'sodium_crypto_sign_seed_keypair'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source);
        }
        self::assertStringContainsString("fopen(\$path, 'xb')", $source);
        self::assertStringContainsString('sodium_memzero($secret)', $source);
        $identity = strpos($source, "\$write(\$signing.'/identity.json'");
        $trust = strpos($source, "\$write(\$signing.'/trust.json'");
        $intake = strpos($source, '$manifestBytes = $read(');
        $verification = strpos($source, 'ReproofV2Verifier())->verify(');
        $refusal = strpos($source, "throw new RuntimeException('REPROOF_NOT_SIGNABLE')");
        $signature = strpos($source, '$signature = sodium_crypto_sign_detached(');
        self::assertIsInt($identity); self::assertIsInt($trust); self::assertIsInt($intake);
        self::assertIsInt($verification); self::assertIsInt($refusal); self::assertIsInt($signature);
        self::assertLessThan($trust, $identity); self::assertLessThan($intake, $trust);
        self::assertLessThan($verification, $intake); self::assertLessThan($refusal, $verification);
        self::assertLessThan($signature, $refusal);
    }
}
