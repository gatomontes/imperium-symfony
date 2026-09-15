<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationInstitution as I, FormationOwnerFrame as O, FreshInstitutionPackage as Package};
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshEstablishmentProcesses as Processes};
use PHPUnit\Framework\{TestCase, Attributes\DataProvider};
final class FreshEstablishmentConsumerPendingTest extends TestCase
{
    public static function phases(): iterable { foreach (['pending', 'partial', 'native-complete'] as $phase) { yield $phase => [$phase]; } }
    #[DataProvider('phases')]
    public function testRealPendingOwnerCannotIssueCurrentAuthority(string $phase): void
    {
        $f = new F(false); $p = new Processes($f);
        try {
            $f->reserve(); $layout = Package::layout($f->root, $f->reservation); $process = null;
            if ($phase !== 'pending') {
                $point = $phase === 'partial' ? 'after-placement:'.array_key_first($layout['files']) : 'after-package-completion';
                $process = $p->finish($p->start('complete', ['crash' => $point])); self::assertSame(73, $process['native_exit'], $process['stderr']);
            }
            $before = $f->journal->read(); $rows = []; $i = new I($f->root);
            foreach (I::SEATS as $role => $seat) {
                // Request contains only proposed public values. No accepted delegation is seeded.
                $pair = sodium_crypto_sign_keypair(); $t = ['role' => $role, 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)),
                    'scope' => $f->store->citadel, 'expires_at' => $f->clock->at + 300, 'actor' => []];
                $d = $f->sign('DELEGATE_PERSONNEL_EVIDENCE', $t);
                foreach (['actor' => fn() => $i->actor($role),
                    'actorInOwner' => fn() => $f->journal->inspect(fn(array $frame, O $owner) => $i->actorInOwner($owner, $role)),
                    'authoritySource' => fn() => $f->personnel->authoritySource($role),
                    'delegate' => fn() => $f->personnel->delegate($t, $d)] as $entry => $call) {
                    FreshEstablishmentRevocationDurabilityTest::refuses($call, 'PPC7_COMPLETE_REQUIRED');
                    $rows[] = ['entry' => $entry, 'role' => $role, 'refusal' => 'PPC7_COMPLETE_REQUIRED'];
                }
            }
            self::assertSame($before, $f->journal->read());
            $e = FreshEstablishmentRevocationTest::revocation($f); $f->protocol->revokeAuthorization($e);
            $after = $f->journal->read();
            $retry = $p->finish($p->start('complete')); self::assertSame(1, $retry['native_exit']); self::assertStringContainsString('PPC8_REVOCATION_TARGET_REVOKED', $retry['stderr']);
            self::assertSame($after, $f->journal->read()); self::assertNull($after['state']['fresh_institutions']['completion']);
            FreshEstablishmentRevocationDurabilityTest::evidence($f, 'consumer-'.$phase, compact('before', 'after', 'process', 'rows', 'e', 'retry'));
        } finally { $p->close(); $f->close(); }
    }
}
