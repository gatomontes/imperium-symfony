<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FreshEstablishmentRevocations as V, FormationJournal as J, FormationInstitution};
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshEstablishmentProcesses as Processes};
use PHPUnit\Framework\{TestCase, Attributes\DataProvider};

final class FreshEstablishmentRevocationDurabilityTest extends TestCase
{
    public static function boundaries(): iterable {
        foreach (['unreserved', 'pending'] as $phase) { foreach (['before-journal-rename', 'after-journal-rename'] as $point) { yield $phase.'-'.$point => [$phase, $point]; } }
    }
    #[DataProvider('boundaries')]
    public function testRealCrashAndFreshProcessReplay(string $phase, string $point): void
    {
        $f = new F(false); $p = new Processes($f);
        try {
            if ($phase === 'pending') { $f->reserve(); }
            $before = $f->journal->read(); $e = FreshEstablishmentRevocationTest::revocation($f);
            $crash = $p->finish($p->start('native-revoke', ['native_revocation' => $e, 'crash' => $point]));
            self::assertSame(73, $crash['native_exit'], $crash['stderr']);
            $crashed = $f->journal->read();
            if ($point === 'before-journal-rename') { self::assertSame($before, $crashed); }
            else { self::assertCount(1, V::history($crashed['state'])); }
            $retry = $p->finish($p->start('native-revoke', ['native_revocation' => $e]));
            self::assertSame(0, $retry['native_exit'], $retry['stderr']); $after = $f->journal->read();
            self::assertCount(1, V::history($after['state']));
            self::assertSame($before['state']['fresh_institutions']['reservation'], $after['state']['fresh_institutions']['reservation']);
            $repeat = $p->finish($p->start('native-revoke', ['native_revocation' => $e]));
            self::assertSame(0, $repeat['native_exit']); self::assertSame($after, $f->journal->read());
            $blocked = $p->finish($p->start($phase === 'pending' ? 'complete' : 'reserve'));
            self::assertSame(1, $blocked['native_exit']); self::assertStringContainsString('PPC8_REVOCATION_TARGET_REVOKED', $blocked['stderr']);
            self::evidence($f, 'crash-'.$phase.'-'.$point, compact('before', 'crashed', 'after', 'e', 'crash', 'retry', 'repeat', 'blocked'));
        } finally { $p->close(); $f->close(); }
    }

    public function testActualBoundAndAdversarialHistoryValidation(): void
    {
        $f = new F();
        try {
            $before = $f->journal->read(); $first = null;
            for ($i = 1; $i <= V::LIMIT; ++$i) {
                $e = FreshEstablishmentRevocationTest::revocation($f); $first ??= $e;
                self::assertSame($i, $f->protocol->revokeAuthorization($e)['sequence']);
            }
            $after = $f->journal->read(); self::assertCount(V::LIMIT, V::history($after['state']));
            self::refuses(fn() => $f->protocol->revokeAuthorization(FreshEstablishmentRevocationTest::revocation($f)), 'PPC7_REVOCATION_HISTORY_FULL');
            self::assertSame($after, $f->journal->read());
            self::assertSame(1, $f->protocol->revokeAuthorization($first)['sequence']); self::assertSame($after, $f->journal->read());
            $nonce = $first['payload']['nonce']; $observations = [];
            // Detached malicious copies are rejection inputs only; never retained as accepted state.
            foreach (['digest', 'sequence', 'recorded-time', 'signature', 'map-nonce', 'adapter', 'target-ref', 'founding', 'same-head-winners', 'empty', 'overflow'] as $kind) {
                $s = $after['state']; $r =& $s['fresh_institutions']['revocations'][$nonce];
                switch ($kind) {
                    case 'digest': $r['record_digest'] = str_repeat('0', 64); $expected = 'RECEIPT'; break;
                    case 'sequence': $r['sequence'] = 0; $expected = 'SEQUENCE'; break;
                    case 'recorded-time': $r['recorded_at'] = $first['payload']['expires_at']; $expected = 'RECORDED_TIME'; break;
                    case 'signature': $r['envelope']['signature'] = base64_encode(str_repeat('x', 64)); $expected = 'SIGNATURE'; break;
                    case 'adapter': $q = $r['envelope']['payload']; $q['adapter_from'] = V::STATE; $r['envelope'] = FreshEstablishmentRevocationTest::sign($f, $q); $expected = 'ADAPTER_HISTORY'; break;
                    case 'target-ref': $q = $r['envelope']['payload']; $q['target_ref']['digest'] = str_repeat('f', 64); $r['envelope'] = FreshEstablishmentRevocationTest::sign($f, $q); $expected = 'TARGET_REFERENCE'; break;
                    case 'founding':
                        $q = $r['envelope']['payload']; $q['target']['terms']['founding']['constitution']['id'] = 'counterfeit-constitution';
                        $q['target']['operator'] = FreshEstablishmentRevocationTest::sign($f, P::operatorPayload($q['target']['terms']));
                        $q['target_ref'] = V::target($q['target'], $s['onboarding']['trust']['body']);
                        $r['envelope'] = FreshEstablishmentRevocationTest::sign($f, $q); $expected = 'HISTORY_FOUNDING_ORIGINALS'; break;
                    case 'same-head-winners':
                        $q = $r['envelope']['payload']; $q['expected_head'] = $f->reservation['terms']['expected_head'];
                        $r['envelope'] = FreshEstablishmentRevocationTest::sign($f, $q); $expected = 'HISTORY_OWNER_ORDER'; break;
                    default: $expected = $kind === 'map-nonce' ? 'SEQUENCE' : 'HISTORY_BOUND';
                }
                if ($kind !== 'digest') { $r['id'] = 'fresh-revocation-'.J::digest($r['envelope']); $body = $r; unset($body['record_digest']); $r['record_digest'] = J::digest($body); }
                unset($r);
                if ($kind === 'map-nonce') { $s['fresh_institutions']['revocations']['wrong'] = $s['fresh_institutions']['revocations'][$nonce]; unset($s['fresh_institutions']['revocations'][$nonce]); }
                if ($kind === 'empty') { $s['fresh_institutions']['revocations'] = []; }
                if ($kind === 'overflow') { $s['fresh_institutions']['revocations']['extra'] = $s['fresh_institutions']['revocations'][$nonce]; }
                self::refuses(fn() => V::history($s), 'PPC8_REVOCATION_'.$expected);
                $observations[] = ['kind' => $kind, 'rejection_input_sha256' => J::digest($s), 'expected' => 'PPC8_REVOCATION_'.$expected,
                    'rejection_receipt' => $s['fresh_institutions']['revocations'][$nonce] ?? null, 'rejection_map_keys' => array_keys($s['fresh_institutions']['revocations'])];
            }
            self::assertSame($after, $f->journal->read());
            foreach (FormationInstitution::SEATS as $role => $seat) { self::assertSame($seat, (new FormationInstitution($f->root))->actor($role)['seat']); }
            self::evidence($f, 'actual-bound', compact('before', 'after', 'observations'));
        } finally { $f->close(); }
    }
    public static function refuses(\Closure $call, string $expected): void {
        $message = null; try { $call(); } catch (\RuntimeException $e) { if ($e instanceof \PHPUnit\Framework\Exception) { throw $e; } $message = $e->getMessage(); }
        self::assertSame($expected, $message);
    }
    public static function evidence(F $f, string $name, array $record): void {
        $base = getenv('PPC7_PUBLIC_EVIDENCE'); if (!is_string($base) || $base === '') { return; }
        $dir = $base.'/closure-durability'; if (!is_dir($dir)) { mkdir($dir, 0700, true); }
        file_put_contents($dir.'/'.hash('sha256', $f->root.'|'.$name).'.json', json_encode(['name' => $name, 'observed_utc' => gmdate('c'), ...$record], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
