<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshEstablishmentProcesses as Processes};
use App\Imperium\Runtime\Citadel\Formation\{FormationInstitution, FormationFreshEstablishment as P};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Admission;
use App\Bootstrap\CanonicalJson;
use PHPUnit\Framework\{TestCase, Attributes\DataProvider};

final class FreshEstablishmentRevocationOrderingTest extends TestCase
{
    public static function orders(): iterable {
        foreach (['native', 'issuer', 'policy'] as $kind) { foreach ([false, true] as $completeFirst) {
            yield $kind.'-'.($completeFirst ? 'complete-first' : 'revocation-first') => [$kind, $completeFirst];
        } }
    }
    #[DataProvider('orders')]
    public function testAuthenticRevocationOwnerCompetesWithCompletion(string $kind, bool $completeFirst): void
    {
        $f = new F(false); $p = new Processes($f);
        try {
            $f->reserve(); $before = $f->journal->read(); $options = self::options($f, $kind);
            $operation = $kind === 'native' ? 'native-revoke' : 'bootstrap-revoke';
            if ($completeFirst) {
                $a = $p->start('complete', ['pause' => 'before-package-completion']); $p->ready($a);
                $b = $p->start($operation, $options); self::blocked($p, $b); $p->release($a);
                $complete = $p->finish($a); $revoked = $p->finish($b);
                self::assertSame(0, $complete['native_exit'], $complete['stderr']);
                // Expected-head integrity cannot be relaxed merely to make both writers win.
                self::assertSame(1, $revoked['native_exit']); self::assertStringContainsString('HEAD', $revoked['stderr']);
                $fresh = self::options($f, $kind);
                $receipt = $kind === 'native' ? $f->protocol->revokeAuthorization($fresh['native_revocation'])
                    : (new Admission($f->store))->retain(CanonicalJson::encode($fresh['bootstrap_revocation']), CanonicalJson::encode($fresh['revocation_object']));
                self::assertNotEmpty($receipt);
                $historical = $f->protocol->complete(P::reference($f->reservation)); self::assertSame(P::COMPLETE, $historical['schema']);
                foreach (FormationInstitution::SEATS as $role => $seat) { self::assertSame($seat, (new FormationInstitution($f->root))->actor($role)['seat']); }
            } else {
                $a = $p->start($operation, [...$options, 'pause' => 'before-journal-rename']); $p->ready($a);
                $b = $p->start('complete'); self::blocked($p, $b); $p->release($a);
                $revoked = $p->finish($a); $complete = $p->finish($b);
                self::assertSame(0, $revoked['native_exit'], $revoked['stderr']); self::assertSame(1, $complete['native_exit']);
                self::assertStringContainsString(match ($kind) { 'native' => 'TARGET_REVOKED', 'issuer' => 'ISSUER_REVOKED', default => 'POLICY_REVOKED' }, $complete['stderr']);
                self::assertNull($f->journal->read()['state']['fresh_institutions']['completion']);
            }
            $after = $f->journal->read(); self::assertCount(1, $after['state']['onboarding']['bindings']);
            $base = getenv('PPC7_PUBLIC_EVIDENCE');
            if (is_string($base) && $base !== '') {
                $dir = $base.'/closure-orders'; if (!is_dir($dir)) { mkdir($dir, 0700, true); }
                file_put_contents($dir.'/'.hash('sha256', $f->root).'.json', json_encode(['kind' => $kind, 'complete_first' => $completeFirst,
                    'before' => $before, 'after' => $after, 'first_signed_options' => $options, 'retry_options' => $fresh ?? null,
                    'completion_process' => $complete, 'revocation_process' => $revoked], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
        } finally { $p->close(); $f->close(); }
    }
    private static function options(F $f, string $kind): array
    {
        if ($kind === 'native') { return ['native_revocation' => FreshEstablishmentRevocationTest::revocation($f)]; }
        $a = $f->fresh->d->f;
        $h = $a->store->make('imperium.bootstrap-revocation/v1', 'ppc8-revocation-'.bin2hex(random_bytes(8)),
            ['target_kind' => $kind, 'target_id' => $kind === 'issuer' ? $f->store->operator : $f->terms['founding']['policy']['id'], 'expected_head' => $f->head()]);
        return ['revocation_object' => $h, 'bootstrap_revocation' => $a->sign($h, 'REVOKE_BOOTSTRAP')];
    }
    private static function blocked(Processes $p, int $id): void
    {
        $child = $p->children[$id]; $deadline = microtime(true) + 10;
        while (!is_file($child['base'].'.events')) { if (microtime(true) > $deadline) { self::fail('Contender did not start'); } usleep(1000); }
        $lock = $child['input']['root'].'/var/imperium/runtime/transition-locks/'.hash('sha256', 'citadel-formation').'.lock';
        $handle = fopen($lock, 'rb'); self::assertFalse(flock($handle, LOCK_EX | LOCK_NB)); fclose($handle);
        self::assertTrue(proc_get_status($child['process'])['running']); self::assertSame('', file_get_contents($child['base'].'.out'));
    }
}
