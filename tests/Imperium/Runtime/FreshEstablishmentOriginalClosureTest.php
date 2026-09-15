<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FreshEstablishmentRevocations as V, FreshInstitutionPackage};
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\TestCase;

final class FreshEstablishmentOriginalClosureTest extends TestCase
{
    public function testFreshSignaturesCannotAuthorizeMalformedOriginals(): void
    {
        $f = new F(false);
        try {
            $before = $f->journal->read(); $original = $f->terms; $rows = [];
            $cases = [
                'holder-original' => [fn(&$t) => $t['founding']['holder']['id'] = 'foreign-holder', 'PPC7_FOUNDING_ORIGINALS'],
                'policy-original' => [fn(&$t) => $t['founding']['policy']['body']['expires_at']--, 'PPC7_FOUNDING_ORIGINALS'],
                'constitution-original' => [fn(&$t) => $t['founding']['constitution']['id'] = 'foreign-constitution', 'PPC7_FOUNDING_ORIGINALS'],
                'command-original' => [fn(&$t) => $t['founding']['command']['id'] = 'foreign-command', 'PPC7_FOUNDING_ORIGINALS'],
                'completion-original' => [fn(&$t) => $t['founding']['completion'] = null, 'PPC7_FOUNDING_ORIGINALS'],
                'root' => [fn(&$t) => $t['root_identity'] = 'sha256:'.str_repeat('f', 64), 'PPC7_IDENTITY'],
                'citadel' => [fn(&$t) => $t['citadel_id'] = 'foreign-citadel', 'PPC7_IDENTITY'],
                'formation-trust' => [fn(&$t) => $t['formation_trust_fingerprint'] = str_repeat('f', 64), 'PPC7_TRUST_IDENTITY'],
                'nine-only' => [fn(&$t) => $t['package']['personnel'][] = $t['package']['personnel'][0], 'PPC7_EXACT_PACKAGE'],
                'unique-seat' => [fn(&$t) => $t['package']['personnel'][1] = $t['package']['personnel'][0], 'PPC7_EXACT_PACKAGE'],
                'officer-only' => [fn(&$t) => $t['package']['personnel'][0]['personnel_type'] = 'OPERATIVE', 'PPC7_EXACT_PACKAGE'],
                'closed-member' => [fn(&$t) => $t['package']['personnel'][0]['founding_class'] = 'GENERIC_V0_PLACEHOLDER', 'PPC7_EXACT_PACKAGE'],
                'persona-original' => [fn(&$t) => $t['package']['personnel'][0]['persona'] = ['version' => '1'], 'B202_OPERATOR_ROOT_ARTIFACT_INVALID'],
                'profile-original' => [fn(&$t) => $t['package']['personnel'][0]['profile']['version'] = '', 'B202_OPERATOR_ROOT_ARTIFACT_INVALID'],
                'officer-original' => [fn(&$t) => $t['package']['personnel'][0]['officer']['id'] = '', 'B202_OPERATOR_ROOT_ARTIFACT_INVALID'],
            ];
            foreach ($cases as $name => [$mutate, $expected]) {
                $f->terms = $original; $mutate($f->terms); $f->resign();
                // Independently prove both outer signatures, regardless of the earlier production predicate.
                self::assertTrue(sodium_crypto_sign_verify_detached(base64_decode($f->operator['signature']), CanonicalJson::encode($f->operator['payload']), $f->fresh->d->f->public));
                self::assertSame($f->formation['payload'], $f->signatures->verify($before['state'], $f->formation, P::EFFECT, $f->terms));
                FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->reserve(), $expected);
                self::assertSame($before, $f->journal->read()); FreshInstitutionPackage::scan($f->root, null, false);
                $target = ['terms' => $f->terms, 'operator' => $f->operator];
                $payload = ['schema' => V::ACT, 'domain' => V::DOMAIN, 'effect' => V::EFFECT, 'target' => $target,
                    'target_ref' => V::target($target, $before['state']['onboarding']['trust']['body']),
                    'root_identity' => $f->terms['root_identity'], 'instance_id' => $f->terms['instance_id'], 'citadel_id' => $f->terms['citadel_id'],
                    'operator_id' => $f->terms['operator_id'], 'trust_fingerprint' => $f->terms['operator_trust_fingerprint'],
                    'expected_head' => $f->head(), 'adapter_from' => P::STATE, 'issued_at' => $f->clock->at, 'expires_at' => $f->clock->at + 300,
                    'nonce' => bin2hex(random_bytes(24)), 'correlation' => 'ppc8-target-originals', 'reason' => 'Invalid public target rejection experiment'];
                $revocation = FreshEstablishmentRevocationTest::sign($f, $payload);
                $revocationExpected = match ($expected) { 'PPC7_FOUNDING_ORIGINALS' => 'PPC7_REVOCATION_FOUNDING_ORIGINALS', 'PPC7_TRUST_IDENTITY' => 'PPC7_REVOCATION_FORMATION_IDENTITY', default => $expected };
                FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization($revocation), $revocationExpected);
                self::assertSame($before, $f->journal->read());
                $rows[] = ['case' => $name, 'terms' => $f->terms, 'operator' => $f->operator, 'formation' => $f->formation, 'refusal' => $expected,
                    'revocation' => $revocation, 'revocation_refusal' => $revocationExpected,
                    'predicate' => str_contains($name, 'original') && str_starts_with($expected, 'PPC7') ? 'Exact comparison with actual owner reconstructed founding originals; not validation of altered inner record' : $expected];
            }
            $f->terms = $original; $f->resign(); $f->reserve(); $valid = FreshEstablishmentRevocationTest::revocation($f);
            $changed = $f->terms; $f->terms['reason'] .= '-different-authorization'; $f->resign();
            $q = $valid['payload']; $q['target'] = ['terms' => $f->terms, 'operator' => $f->operator];
            $q['target_ref'] = V::target($q['target'], $before['state']['onboarding']['trust']['body']);
            $e = FreshEstablishmentRevocationTest::sign($f, $q);
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization($e), 'PPC7_REVOCATION_TARGET_ORIGINAL');
            $q = $valid['payload']; $q['expires_at'] = $f->clock->at + 1; $expired = FreshEstablishmentRevocationTest::sign($f, $q); $f->setTime($q['expires_at']);
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization($expired), 'PPC7_REVOCATION_CURRENT');
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization([]), 'PPC8_REVOCATION_SHAPE');
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization(['oversized' => str_repeat('x', 4194304)]), 'PPC7_BYTE_BOUND');
            $deep = []; for ($i = 0; $i < 34; ++$i) { $deep = [$deep]; }
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization($deep), 'PPC7_STRUCTURE_BOUND');
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->protocol->revokeAuthorization(array_fill(0, 100000, null)), 'PPC7_STRUCTURE_BOUND');
            FreshEstablishmentRevocationDurabilityTest::evidence($f, 'freshly-signed-originals', ['before' => $before, 'observations' => $rows, 'mismatched_target' => $e, 'expired' => $expired, 'after' => $f->journal->read()]);
        } finally { $f->close(); }
    }
}
