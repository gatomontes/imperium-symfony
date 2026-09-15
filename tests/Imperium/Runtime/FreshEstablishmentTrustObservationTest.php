<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshEstablishmentProcesses as Processes};
use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution, FormationFreshEstablishment as P, FreshInstitutionPackage};
use PHPUnit\Framework\{TestCase, Attributes\DataProvider};

final class FreshEstablishmentTrustObservationTest extends TestCase
{
    public static function clocks(): iterable { foreach (['operator', 'formation'] as $role) { foreach ([false, true] as $completeFirst) {
        yield $role.'-'.($completeFirst ? 'completed-before-expiry' : 'expiry-before-completion') => [$role, $completeFirst];
    } } }
    #[DataProvider('clocks')]
    public function testIndependentTrustIntervalsAndExactPublicationClock(string $role, bool $completeFirst): void
    {
        $f = new F(false, 1800, true, $role === 'formation' ? 400 : 20000); $p = null;
        try {
            $frame = $f->journal->read(); $expiry = $role === 'operator'
                ? $frame['state']['onboarding']['trust']['body']['expires_at'] : $frame['state']['trust']['expires_at'];
            $f->terms['expires_at'] = min($f->terms['expires_at'], $expiry); $f->resign(); $f->reserve();
            $before = $f->journal->read(); $p = new Processes($f);
            self::assertNotEmpty($f->store->currentTrust($f->store->state($before['state'])));
            self::assertNotEmpty($f->signatures->verify($before['state'], $f->formation, P::EFFECT, $f->terms));
            $options = $completeFirst ? [] : ['advance_at' => 'after-package-completion', 'advance_to' => $expiry];
            $child = $p->start('complete', $options); $result = $p->finish($child);
            self::assertSame($completeFirst ? 0 : 1, $result['native_exit'], $result['stderr']);
            $expected = $role === 'operator' ? 'O2_TRUST_TIME' : 'PPC7_OPERATOR_CURRENT';
            if (!$completeFirst) {
                self::assertStringContainsString($expected, $result['stderr']);
                self::assertSame($before, $f->journal->read());
                self::assertSame($f->reservation['record_digest'], FreshInstitutionPackage::verify($f->root, $f->reservation)['completion']['reservation_ref']['digest']);
            }
            $f->setTime($expiry); $late = $f->journal->read(); $trustError = null;
            try {
                if ($role === 'operator') { $f->store->currentTrust($f->store->state($late['state'])); }
                else {
                    self::assertNotEmpty($f->store->currentTrust($f->store->state($late['state'])));
                    $f->signatures->verify($late['state'], $f->formation, P::EFFECT, $f->terms);
                }
            } catch (\RuntimeException $e) { if ($e instanceof \PHPUnit\Framework\Exception) { throw $e; } $trustError = $e->getMessage(); }
            self::assertSame($role === 'operator' ? 'O2_TRUST_TIME' : 'CMF022_AUTHENTIC_EXACT_DECISION_REQUIRED', $trustError);
            $restart = $p->start('complete'); $replay = $p->finish($restart);
            self::assertSame($completeFirst ? 0 : 1, $replay['native_exit'], $replay['stderr']); self::assertSame($late, $f->journal->read());
            foreach (FormationInstitution::SEATS as $name => $seat) {
                $actor = null; $error = null;
                try { $actor = (new FormationInstitution($f->root))->actor($name); } catch (\RuntimeException $e) { $error = $e->getMessage(); }
                if ($completeFirst) { self::assertSame($seat, $actor['seat'] ?? null, $error ?? ''); }
                else { self::assertSame('PPC7_COMPLETE_REQUIRED', $error); }
            }
            $base = getenv('PPC7_PUBLIC_EVIDENCE');
            if (is_string($base) && $base !== '') {
                $dir = $base.'/closure-trust'; if (!is_dir($dir)) { mkdir($dir, 0700, true); }
                file_put_contents($dir.'/'.hash('sha256', $f->root).'.json', json_encode(['role' => $role, 'complete_first' => $completeFirst,
                    'before' => $before, 'after' => $late, 'expiry' => $expiry, 'trust_refusal' => $trustError, 'process' => $result, 'restart' => $replay,
                    'predicate_order' => 'Operator trust is checked before grant expiry. Formation trust expiry entails expiry of its bounded decision and the installation grant; completion refuses at the earlier grant check. The independent Formation owner observation also refuses; no exclusive later-predicate credit is claimed.'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
        } finally { $p?->close(); $f->close(); }
    }
}
