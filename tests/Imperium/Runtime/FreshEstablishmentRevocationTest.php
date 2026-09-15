<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationInstitution, FreshEstablishmentRevocations as V};
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FreshEstablishmentRevocationTest extends TestCase
{
    public function testExactPendingReservationReplayRefusesWithdrawnAuthorization(): void
    {
        $f = new F(false);
        try {
            $reservation = $f->reserve(); $before = $f->journal->read();
            self::assertSame($reservation, $f->reserve());
            self::assertSame($before, $f->journal->read());
            $envelope = self::revocation($f); $receipt = $f->protocol->revokeAuthorization($envelope);
            $after = $f->journal->read();
            self::refuses(fn() => $f->reserve(), 'PPC8_REVOCATION_TARGET_REVOKED');
            self::assertSame($after, $f->journal->read());
            self::assertSame($reservation, $after['state']['fresh_institutions']['reservation']);
            self::assertNull($after['state']['fresh_institutions']['completion']);
            self::evidence($f, 'receiving-revoked-pending-replay', compact('before', 'after', 'envelope', 'receipt'));
        } finally { $f->close(); }
    }

    public static function phases(): iterable { foreach (['unreserved', 'pending', 'completed'] as $p) { yield $p => [$p]; } }

    #[DataProvider('phases')]
    public function testExactNativeRevocationAndHistoricalReplay(string $phase): void
    {
        $f = new F(false);
        try {
            if ($phase !== 'unreserved') { $f->reserve(); }
            if ($phase === 'completed') { $f->completion = $f->protocol->complete(P::reference($f->reservation)); }
            $before = $f->journal->read(); $envelope = self::revocation($f);
            $receipt = $f->protocol->revokeAuthorization($envelope); $after = $f->journal->read();
            self::assertSame(V::STATE, $after['state']['fresh_institutions']['schema']);
            foreach (['initialization', 'reservation', 'completion', 'preparations'] as $key) {
                self::assertSame(CanonicalJson::encode($before['state']['fresh_institutions'][$key]), CanonicalJson::encode($after['state']['fresh_institutions'][$key]));
            }
            self::assertSame($receipt, $f->protocol->revokeAuthorization($envelope)); self::assertSame($after, $f->journal->read());
            if ($phase === 'unreserved') { self::refuses(fn() => $f->reserve(), 'PPC8_REVOCATION_TARGET_REVOKED'); }
            elseif ($phase === 'pending') { self::refuses(fn() => $f->protocol->complete(P::reference($f->reservation)), 'PPC8_REVOCATION_TARGET_REVOKED'); }
            else {
                self::assertSame($f->completion, $f->protocol->complete(P::reference($f->reservation)));
                foreach (FormationInstitution::SEATS as $role => $seat) { self::assertSame($seat, (new FormationInstitution($f->root))->actor($role)['seat']); }
                $processes = new \App\Tests\Imperium\Runtime\Support\FreshEstablishmentProcesses($f);
                try { $readback = $processes->finish($processes->start('actors')); }
                finally { $processes->close(); }
                self::assertSame(0, $readback['native_exit'], $readback['stderr']);
                self::assertCount(9, json_decode($readback['output'], true, 64, JSON_THROW_ON_ERROR)['result']);
                $layout = \App\Imperium\Runtime\Citadel\Formation\FreshInstitutionPackage::layout($f->root, $f->reservation);
                $removed = array_key_first($layout['files']); $removedBytes = file_get_contents($f->root.'/'.$removed); unlink($f->root.'/'.$removed);
                self::assertSame($receipt, $f->protocol->revokeAuthorization($envelope));
                self::assertSame($f->completion, $f->protocol->complete(P::reference($f->reservation)));
                self::assertFalse(file_exists($f->root.'/'.$removed));
                self::refuses(fn() => (new FormationInstitution($f->root))->actor('laboratorium'), 'PPC7_NATIVE_ORIGINAL');
            }
            $changed = $envelope['payload']; $changed['reason'] = 'Changed signed bytes';
            self::refuses(fn() => $f->protocol->revokeAuthorization(self::sign($f, $changed)), 'PPC7_REVOCATION_NONCE_CONFLICT');
            $f->setTime($envelope['payload']['expires_at']);
            self::assertSame($receipt, $f->protocol->revokeAuthorization($envelope)); self::assertSame($after, $f->journal->read());
            self::evidence($f, $phase, ['before' => $before, 'after' => $after, 'envelope' => $envelope, 'receipt' => $receipt,
                'fresh_v2_reader' => $readback ?? null, 'removed_fixture_path' => $removed ?? null, 'removed_fixture_original_base64' => isset($removedBytes) ? base64_encode($removedBytes) : null]);
        } finally { $f->close(); }
    }

    public function testFreshlySignedRevocationMetadataAndBackdatingRefuseBeforePublication(): void
    {
        $f = new F(false);
        try {
            $before = $f->journal->read(); $valid = self::revocation($f); $observed = [];
            foreach ([
                'purpose' => ['effect', 'REVOKE_BOOTSTRAP', 'PPC8_REVOCATION_PURPOSE'],
                'root' => ['root_identity', 'sha256:'.str_repeat('f', 64), 'PPC8_REVOCATION_IDENTITY'],
                'version' => ['adapter_from', 'unknown', 'PPC8_REVOCATION_ADAPTER'],
                'backdated' => ['issued_at', $before['state']['onboarding']['trust']['body']['not_before'] - 1, 'PPC8_REVOCATION_INTERVAL'],
                'expired' => ['expires_at', $f->clock->at, 'PPC8_REVOCATION_INTERVAL'],
                'nonce' => ['nonce', 'unqualified-o2-nonce', 'PPC8_REVOCATION_NONCE'],
            ] as $name => [$field, $value, $error]) {
                $p = $valid['payload']; $p[$field] = $value; $e = self::sign($f, $p);
                self::refuses(fn() => $f->protocol->revokeAuthorization($e), $error);
                self::assertSame($before, $f->journal->read()); $observed[] = ['case' => $name, 'envelope' => $e, 'refusal' => $error];
            }
            $wrong = $valid; $wrong['signature'] = base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($valid['payload']), sodium_crypto_sign_secretkey(sodium_crypto_sign_keypair())));
            self::refuses(fn() => $f->protocol->revokeAuthorization($wrong), 'PPC8_REVOCATION_SIGNATURE');
            self::assertSame($before, $f->journal->read());
            self::evidence($f, 'signed-refusals', ['before' => $before, 'observations' => $observed, 'wrong_signer' => $wrong]);
            $f->protocol->revokeAuthorization($valid); self::assertCount(1, V::history($f->journal->read()['state']));
        } finally { $f->close(); }
    }

    public static function revocation(F $f): array
    {
        return self::sign($f, $f->protocol->prepareRevocation(['terms' => $f->terms, 'operator' => $f->operator],
            $f->clock->at, $f->clock->at + 300, bin2hex(random_bytes(24)), 'ppc8-offline', 'Withdraw only this exact native establishment authorization'));
    }
    public static function unavailableIssuers(): iterable { yield 'expired-trust' => [false]; yield 'revoked-issuer' => [true]; }
    #[DataProvider('unavailableIssuers')]
    public function testNewRevocationRequiresActualCurrentOperatorCompetence(bool $revoked): void
    {
        $f = new F(false);
        try {
            $f->reserve(); $e = self::revocation($f); $original = $f->journal->read();
            if ($revoked) {
                $issuerRevocation = $f->fresh->d->f->revoke('issuer', $f->store->operator);
                // Re-sign the now-current head so a stale-head check cannot mask issuer refusal.
                $p = $e['payload']; $p['expected_head'] = $f->head(); $e = self::sign($f, $p); $expected = 'O2_ISSUER_REVOKED';
            } else { $f->setTime($original['state']['onboarding']['trust']['body']['expires_at']); $expected = 'O2_TRUST_TIME'; }
            $before = $f->journal->read(); self::refuses(fn() => $f->protocol->revokeAuthorization($e), $expected);
            self::assertSame($before, $f->journal->read());
            self::evidence($f, $revoked ? 'current-issuer-revoked' : 'current-issuer-expired', ['original' => $original, 'before' => $before,
                'after' => $f->journal->read(), 'envelope' => $e, 'clock' => $f->clock->at, 'refusal' => $expected, 'issuer_revocation' => $issuerRevocation ?? null]);
        } finally { $f->close(); }
    }
    public static function sign(F $f, array $payload): array
    {
        return ['payload' => $payload, 'signature' => base64_encode(sodium_crypto_sign_detached(CanonicalJson::encode($payload), $f->fresh->d->f->secret))];
    }
    private static function refuses(\Closure $operation, string $expected): void
    {
        $message = null;
        try { $operation(); } catch (\RuntimeException $e) {
            if ($e instanceof \PHPUnit\Framework\Exception) { throw $e; } $message = $e->getMessage();
        }
        self::assertSame($expected, $message);
    }
    private static function evidence(F $f, string $name, array $record): void
    {
        $base = getenv('PPC7_PUBLIC_EVIDENCE'); if (!is_string($base) || $base === '') { return; }
        $directory = $base.'/closure-revocations'; if (!is_dir($directory)) { mkdir($directory, 0700, true); }
        file_put_contents($directory.'/'.hash('sha256', $f->root.'|'.$name).'.json', json_encode(['name' => $name,
            'root' => $f->root, 'observed_utc' => gmdate('c'), ...$record], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
