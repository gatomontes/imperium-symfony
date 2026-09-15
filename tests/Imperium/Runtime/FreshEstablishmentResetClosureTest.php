<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\CanonicalJson as C;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationJournal as J, FormationInstitution};
use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission, AuthorityStore, Enrollment, Rules as R};
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\TestCase;

final class FreshEstablishmentResetClosureTest extends TestCase
{
    public static function symlinkPhases(): iterable
    {
        yield 'pending-revocation' => [false];
        yield 'completed-consumption' => [true];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('symlinkPhases')]
    public function testSymlinkRootSharesRevocationAndCannotResetConsumption(bool $completed): void
    {
        $f = new F($completed); $alias = $f->root.'-ppc8-alias';
        try {
            if (!@symlink($f->root, $alias)) { self::markTestSkipped('Host cannot create symlinks; PPC8 root-alias proof requires a suitable host'); }
            if (!$completed) { $f->reserve(); }
            self::assertSame((new OperatorRootOwnership($f->root))->identity(), (new OperatorRootOwnership($alias))->identity());
            $store = new AuthorityStore(new J($alias), $f->clock, $f->store->instance, $f->store->citadel, $f->store->operator, $f->store->sourceCommit);
            $service = new P($alias, $store, $f->fresh->base);
            $before = $f->journal->read();
            self::assertSame($before, $store->journal->read());
            $payload = $service->prepareRevocation(['terms' => $f->terms, 'operator' => $f->operator],
                $f->clock->at, $f->clock->at + 300, bin2hex(random_bytes(24)), 'ppc8-symlink', 'Exact withdrawal through the canonical root alias');
            $envelope = FreshEstablishmentRevocationTest::sign($f, $payload);
            $receipt = $service->revokeAuthorization($envelope); $after = $f->journal->read();
            self::assertSame($after, $store->journal->read());
            self::assertSame($receipt, $f->protocol->revokeAuthorization($envelope));
            if ($completed) {
                self::assertSame($f->completion, $service->reserve($f->terms, $f->operator, $f->formation));
                self::assertSame($f->completion, $service->complete(P::reference($f->reservation)));
                self::assertSame('laboratorium.alchemist', (new FormationInstitution($alias))->actor('laboratorium')['seat']);
            } else {
                foreach ([$service, $f->protocol] as $owner) {
                    FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $owner->complete(P::reference($f->reservation)), 'PPC8_REVOCATION_TARGET_REVOKED');
                }
            }
            $f->terms['expected_head'] = $f->head(); $f->terms['nonce'] = bin2hex(random_bytes(24)); $f->resign();
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $service->reserve($f->terms, $f->operator, $f->formation), 'PPC7_RESERVATION_CONFLICT');
            self::assertSame($after, $f->journal->read());
            self::assertCount(1, $after['state']['onboarding']['bindings']);
            FreshEstablishmentRevocationDurabilityTest::evidence($f, 'receiving-symlink-'.($completed ? 'completed' : 'pending'),
                compact('alias', 'before', 'after', 'envelope', 'receipt'));
        } finally {
            if (is_link($alias)) { unlink($alias); }
            $f->close();
        }
    }

    public function testO2NonceCollisionHasNoNativeRevocationCompetenceAndConsumptionCannotReset(): void
    {
        $f = new F(false);
        try {
            $a = $f->fresh->d->f; $before = $f->journal->read();
            $policy = $f->terms['founding']['policy']; $policy['id'] = 'second-policy-'.bin2hex(random_bytes(8)); unset($policy['record_digest']); $policy = R::seal($policy);
            [$policy, $act, $admission] = $a->admitPolicy($policy);
            $f->terms = $f->protocol->prepare($f->package, $f->clock->at, $f->clock->at + 600, $act['payload']['nonce'], 'ppc8-collision', 'An unrelated O2 authorization has the same nonce');
            $f->resign(); $f->reserve();
            $revocation = $a->revoke('act', $act['payload']['nonce']);
            self::assertArrayHasKey('act:'.$f->terms['nonce'], $f->journal->read()['state']['onboarding']['revocations']);
            $f->completion = $f->protocol->complete(P::reference($f->reservation));
            $settled = $f->journal->read(); self::assertCount(1, $settled['state']['onboarding']['bindings']);
            // Actual Fresh producer under its Formation owner checks root consumption before deriving any new policy terms.
            $producer = new \App\Imperium\Runtime\Onboarding\Augur\FreshProducer(new OperatorRootOwnership($f->root), $f->fresh->base, new \App\Imperium\Runtime\Onboarding\Augur\NativeConstitutionEvidence());
            $slot = array_values(array_filter($policy['body']['effect_slots'], static fn(array $s): bool => $s['effect'] === 'CONSTITUTE_FOUNDING_AUGUR'))[0];
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->journal->changeAtHead(function (array &$state, array $head) use ($f, $producer, $policy, $slot) {
                $s =& $state['onboarding']; $terms = $f->store->checkSource($s, $f->holder['body']['terms_ref']);
                return $producer->publish($f->store, $s, $policy, $slot, $terms, $f->holder['body']['command_ref'], $head);
            }), 'O2_ROOT_ALREADY_CONSUMED');
            $aliases = [$f->root.'/.']; if (PHP_OS_FAMILY === 'Windows') { $aliases[] = strtoupper($f->root); }
            foreach ($aliases as $alias) {
                self::assertSame((new OperatorRootOwnership($f->root))->identity(), (new OperatorRootOwnership($alias))->identity());
                $store = new AuthorityStore(new J($alias), $f->clock, $f->store->instance, $f->store->citadel, $f->store->operator, $f->store->sourceCommit);
                $service = new P($alias, $store, $f->fresh->base);
                self::assertSame($f->completion, $service->reserve($f->terms, $f->operator, $f->formation));
                self::assertSame($f->completion, $service->complete(P::reference($f->reservation)));
            }
            $original = $f->terms; $observations = [];
            foreach (['nonce', 'package'] as $mutation) {
                $f->terms = $original; $f->terms['expected_head'] = $f->head(); $f->terms['nonce'] = bin2hex(random_bytes(24));
                if ($mutation === 'package') { $f->terms['package']['personnel'][0]['persona']['id'] = 'second-public-persona'; }
                $f->resign(); FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->reserve(), 'PPC7_RESERVATION_CONFLICT');
                $observations[] = ['kind' => $mutation, 'terms' => $f->terms, 'operator' => $f->operator, 'formation' => $f->formation, 'refusal' => 'PPC7_RESERVATION_CONFLICT'];
            }
            $f->terms = $original; $f->resign();
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $a->enroll(), 'O2_ALREADY_ENROLLED');
            $trust = $settled['state']['trust'];
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->signatures->enrollPublicTrust(array_intersect_key($trust, array_flip(['public_key', 'not_before', 'expires_at'])), $trust['fingerprint']), 'CMF021_TRUST_ALREADY_ENROLLED');
            $different = new AuthorityStore($f->journal, $f->clock, 'different-instance', $f->store->citadel, $f->store->operator, $f->store->sourceCommit);
            $receipt = $different->make('imperium.bootstrap-enrollment/v1', 'second-instance-enrollment', $a->enrollment()['body']);
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => (new Enrollment($different))->enroll(C::encode($receipt), 'sha256:'.hash('sha256', $a->public), $f->head()), 'O2_ALREADY_ENROLLED');
            $pair = sodium_crypto_sign_keypair(); $public = sodium_crypto_sign_publickey($pair);
            $replacement = $a->enrollment()['body']; $replacement['public_key'] = base64_encode($public); $replacement['fingerprint'] = 'sha256:'.hash('sha256', $public);
            $replacementReceipt = $f->store->make('imperium.bootstrap-enrollment/v1', 'replacement-public-trust', $replacement);
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => (new Enrollment($f->store))->enroll(C::encode($replacementReceipt), $replacement['fingerprint'], $f->head()), 'O2_ALREADY_ENROLLED');
            FreshEstablishmentRevocationDurabilityTest::refuses(fn() => $f->signatures->enrollPublicTrust(['public_key' => base64_encode($public), 'not_before' => $f->clock->at, 'expires_at' => $f->clock->at + 900], hash('sha256', $public)), 'CMF021_TRUST_ALREADY_ENROLLED');
            self::assertSame($settled, $f->journal->read());
            foreach (FormationInstitution::SEATS as $role => $seat) { self::assertSame($seat, (new FormationInstitution($f->root))->actor($role)['seat']); }
            FreshEstablishmentRevocationDurabilityTest::evidence($f, 'actual-reset-and-domain-collision', compact('before', 'policy', 'act', 'admission', 'revocation', 'settled', 'aliases', 'observations', 'receipt'));
        } finally { $f->close(); }
    }
}
