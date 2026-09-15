<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshEstablishmentProcesses as Processes};
use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution, FormationFreshEstablishment as P};
use App\Imperium\Runtime\Citadel\NativeAuthority\NativeTrust;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class FreshEstablishmentOrderingTest extends TestCase
{
    public function testTwoProcessesAtOneHeadPublishOnlyOneReservation(): void
    {
        $f = new F(false); $p = new Processes($f);
        try {
            $first = [$f->terms, $f->operator, $f->formation];
            $f->terms['reason'] .= '-competing'; $f->resign();
            $second = ['terms' => $f->terms, 'operator' => $f->operator, 'formation' => $f->formation];
            [$f->terms, $f->operator, $f->formation] = $first;
            $a = $p->start('reserve', ['pause' => 'before-journal-rename']); $p->ready($a);
            $b = $p->start('reserve', $second); $this->blocked($p, $b); $p->release($a);
            self::assertSame(0, $p->finish($a)['native_exit']); self::assertSame(1, $p->finish($b)['native_exit']);
            $state = $f->journal->read()['state']; self::assertNotNull($state['fresh_institutions']['reservation']); self::assertNull($state['fresh_institutions']['completion']);
        } finally { $p->close(); $f->close(); }
    }
    public static function orders(): iterable {
        foreach (['revoke', 'native-enroll', 'generic', 'seal'] as $competitor) { foreach ([true, false] as $first) { yield $competitor.'-'.($first ? 'establishment-first' : 'competitor-first') => [$competitor, $first]; } }
    }
    #[DataProvider('orders')]
    public function testRealOwnerCompetitionBothOrders(string $competitor, bool $establishmentFirst): void
    {
        $f = new F(false); $p = new Processes($f);
        try {
            $f->reserve(); $nonce = $f->formation['payload']['nonce'];
            $options = ['nonce' => $nonce, 'revocation' => $f->sign('REVOKE_DECISION', ['nonce' => $nonce])];
            if ($competitor === 'native-enroll') {
                $pair = sodium_crypto_sign_keypair(); $public = sodium_crypto_sign_publickey($pair);
                $options += ['native_policy' => ['schema' => 'imperium.native-authority-enrollment/v1', 'domain' => NativeTrust::DOMAIN,
                    'instance_id' => $f->store->instance, 'public_key' => base64_encode($public), 'issuer_role' => NativeTrust::ROLE,
                    'effects' => NativeTrust::EFFECTS, 'not_before' => $f->clock->at, 'expires_at' => $f->clock->at + 600,
                    'writer_boundary' => NativeTrust::BOUNDARY], 'fingerprint' => hash('sha256', $public)];
            }
            $competitorPause = match ($competitor) { 'revoke' => 'before-journal-rename', 'native-enroll' => 'native-before-commit', default => 'native-owner-acquired' };
            if ($establishmentFirst) {
                $a = $p->start('complete', ['pause' => 'before-package-completion']); $p->ready($a);
                $b = $p->start($competitor, $options); $this->blocked($p, $b); $p->release($a);
                $complete = $p->finish($a); $other = $p->finish($b);
            } else {
                $a = $p->start($competitor, [...$options, 'pause' => $competitorPause]); $p->ready($a);
                $b = $p->start('complete'); $this->blocked($p, $b); $p->release($a);
                $other = $p->finish($a); $complete = $p->finish($b);
            }
            $refusingCompetitor = in_array($competitor, ['generic', 'seal'], true);
            self::assertSame($refusingCompetitor ? 1 : 0, $other['native_exit'], $other['stderr']);
            self::assertSame($establishmentFirst || $refusingCompetitor ? 0 : 1, $complete['native_exit'], $complete['stderr']);
            if ($competitor === 'native-enroll' || (!$establishmentFirst && !$refusingCompetitor)) {
                $refused = false;
                try { (new FormationInstitution($f->root))->actor('laboratorium'); }
                catch (\RuntimeException $e) { $refused = true; self::assertNotSame('', $e->getMessage()); }
                self::assertTrue($refused, 'Invalidated authority');
            } else { self::assertSame('laboratorium.alchemist', (new FormationInstitution($f->root))->actor('laboratorium')['seat']); }
            self::assertCount(1, $f->journal->read()['state']['onboarding']['bindings']);
        } finally { $p->close(); $f->close(); }
    }
    private function blocked(Processes $p, int $id): void
    {
        $child = $p->children[$id]; $deadline = microtime(true) + 10;
        while (!is_file($child['base'].'.events')) { if (microtime(true) > $deadline) { self::fail('Contender did not start'); } usleep(1000); }
        // Prove the exact real Formation flock is held, not just that the child is slow.
        $lock = $child['input']['root'].'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock';
        $handle = fopen($lock, 'rb'); self::assertFalse(flock($handle, LOCK_EX | LOCK_NB)); fclose($handle);
        self::assertTrue(proc_get_status($child['process'])['running']);
        self::assertSame('', file_get_contents($child['base'].'.out'));
    }
}
