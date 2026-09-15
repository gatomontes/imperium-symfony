<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Citadel\Formation\{FormationFreshEstablishment as P, FormationInstitution, FormationOwnerFrame, FormationJournal, FreshInstitutionPackage};
use App\Tests\Imperium\Runtime\Support\FreshEstablishmentFixture as F;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
final class FreshEstablishmentRefusalTest extends TestCase
{
    private function refuses(callable $operation): string {
        try { $operation(); } catch (\RuntimeException|\TypeError $e) {
            if ($e instanceof \PHPUnit\Framework\Exception) { throw $e; }
            self::assertNotSame('', $e->getMessage()); return $e->getMessage();
        }
        self::fail('Required refusal');
    }
    public function testRefusalHarnessRejectsSuccessfulOperation(): void
    {
        $this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
        $this->refuses(static fn() => null);
    }
    public function testExactJointCompetenceAndAllMalformedPackageClassesBeforeAnyPlacement(): void
    {
        $f = new F(false);
        try {
            $terms = $f->terms; $operator = $f->operator; $formation = $f->formation; $head = $f->head();
            $publicInitial = $f->journal->read(); $observations = [];
            $cases = [
                'operator-missing' => static function (&$t, &$o, &$d) { $o = []; },
                'formation-missing' => static function (&$t, &$o, &$d) { $d = []; },
                'operator-key' => static function (&$t, &$o, &$d) { $o['signature'] = base64_encode(str_repeat('x', 64)); },
                'formation-key' => static function (&$t, &$o, &$d) { $d['signature'] = base64_encode(str_repeat('x', 64)); },
                'operator-role' => static function (&$t, &$o, &$d) { $o['payload']['issuer']['kind'] = 'formation'; },
                'purpose' => static function (&$t, &$o, &$d) { $t['purpose'] = 'CONSTITUTE_FOUNDING_AUGUR'; },
                'foreign-root' => static function (&$t, &$o, &$d) { $t['root_identity'] = 'sha256:'.str_repeat('0', 64); },
                'foreign-instance' => static function (&$t, &$o, &$d) { $t['instance_id'] = $t['package']['instance_id'] = 'foreign-instance'; },
                'foreign-trust' => static function (&$t, &$o, &$d) { $t['operator_trust_fingerprint'] = 'sha256:'.str_repeat('0', 64); },
                'foreign-formation-trust' => static function (&$t, &$o, &$d) { $t['formation_trust_fingerprint'] = str_repeat('0', 64); },
                'old-head' => static function (&$t, &$o, &$d) { --$t['expected_head']['generation']; },
                'missing-completion' => static function (&$t, &$o, &$d) { $t['founding']['completion'] = null; },
                'changed-holder' => static function (&$t, &$o, &$d) { $t['founding']['holder']['id'] = 'foreign-holder'; },
                'changed-metadata' => static function (&$t, &$o, &$d) { $t['reason'] .= '-changed'; },
                'changed-digest' => static function (&$t, &$o, &$d) { $t['founding']['policy']['record_digest'] = 'sha256:'.str_repeat('0', 64); },
                'extra-seat' => static function (&$t, &$o, &$d) { $t['package']['personnel'][] = $t['package']['personnel'][0]; },
                'duplicate-seat' => static function (&$t, &$o, &$d) { $t['package']['personnel'][1] = $t['package']['personnel'][0]; },
                'operative' => static function (&$t, &$o, &$d) { $t['package']['personnel'][0]['personnel_type'] = 'OPERATIVE'; },
                'placeholder' => static function (&$t, &$o, &$d) { $t['package']['personnel'][0]['founding_class'] = 'GENERIC_V0_PLACEHOLDER'; },
                'malformed-artifact' => static function (&$t, &$o, &$d) { $t['package']['personnel'][0]['profile'] = ['version' => '1']; },
                'extra-package-field' => static function (&$t, &$o, &$d) { $t['package']['bypass'] = true; },
                'unbounded-reason' => static function (&$t, &$o, &$d) { $t['reason'] = str_repeat('x', 1025); },
                'unbounded-lifetime' => static function (&$t, &$o, &$d) { $t['expires_at'] = $t['not_before'] + 3601; },
                'unknown-schema' => static function (&$t, &$o, &$d) { $t['schema'] .= '-unknown'; },
            ];
            foreach ($cases as $name => $change) {
                $t = $terms; $o = $operator; $d = $formation; $change($t, $o, $d);
                $error = $this->refuses(fn() => $f->protocol->reserve($t, $o, $d));
                self::assertSame($head, $f->head(), $name); FreshInstitutionPackage::scan($f->root, null, false);
                $observations[] = ['case' => $name, 'terms' => $t, 'operator' => $o, 'formation' => $d, 'refusal' => $error, 'unchanged_head' => $head];
            }
            // Even signatures freshly issued over malformed native artifacts do not cure them.
            $f->terms['package']['personnel'][0]['profile'] = ['version' => '1']; $f->resign();
            $this->refuses(fn() => $f->reserve()); self::assertSame($head, $f->head());
            $f->terms = $terms; $f->operator = $operator; $f->formation = $formation; $f->reserve();
            $reserved = $f->head(); $f->terms['nonce'] = bin2hex(random_bytes(24)); $f->resign();
            $this->refuses(fn() => $f->reserve()); self::assertSame($reserved, $f->head());
            $f->terms = $terms; $f->terms['reason'] .= '-same-nonce-changed-bytes'; $f->resign();
            $this->refuses(fn() => $f->reserve()); self::assertSame($reserved, $f->head());
        } finally {
            if (isset($publicInitial)) { $f->publicEvidence('exact-competence-refusals', ['initial_public_frame' => $publicInitial, 'observations' => $observations]); }
            $f->close();
        }
    }
    public static function currentness(): iterable { foreach (['expiry', 'operator-revocation', 'formation-revocation', 'holder-policy-revocation', 'native-successor', 'bootstrap-successor', 'unknown-partial', 'corrupt-pending'] as $case) { yield $case => [$case]; } }
    #[DataProvider('currentness')]
    public function testPendingRevalidationRefusesAndRetainsOriginals(string $case): void
    {
        $f = new F(false);
        try {
            $f->reserve(); $reservation = $f->reservation;
            if ($case === 'expiry') { $f->setTime($f->terms['expires_at']); }
            elseif ($case === 'operator-revocation') { $f->fresh->d->f->revoke('issuer', $f->store->operator); }
            elseif ($case === 'formation-revocation') { $nonce = $f->formation['payload']['nonce']; $f->signatures->revoke($f->sign('REVOKE_DECISION', ['nonce' => $nonce]), $nonce); }
            elseif ($case === 'holder-policy-revocation') { $f->fresh->d->f->revoke('policy', $f->terms['founding']['policy']['id']); }
            elseif ($case === 'native-successor') { mkdir($f->root.'/var/imperium/native-authority'); }
            elseif ($case === 'bootstrap-successor') { file_put_contents($f->root.'/var/imperium/bootstrap-state.json', '{}'); }
            else {
                $layout = FreshInstitutionPackage::layout($f->root, $reservation);
                $path = $f->root.'/'.($case === 'corrupt-pending' ? $layout['completion_path'].'.pending' : 'var/imperium/operator-root/unknown.json');
                mkdir(dirname($path), 0700, true); file_put_contents($path, 'unauthenticated-public-corruption');
            }
            $head = $f->head(); $this->refuses(fn() => $f->protocol->complete(P::reference($reservation)));
            self::assertSame($head, $f->head()); self::assertSame($reservation, $f->journal->read()['state']['fresh_institutions']['reservation']);
            self::assertNull($f->journal->read()['state']['fresh_institutions']['completion']);
            if (isset($path)) { self::assertSame('unauthenticated-public-corruption', file_get_contents($path)); }
            $this->refuses(fn() => (new FormationInstitution($f->root))->actor('laboratorium'));
        } finally { $f->close(); }
    }
    public function testCompletedReplayCannotRepairAndForeignDetachedOwnersCannotWrite(): void
    {
        $f = new F();
        try {
            $layout = FreshInstitutionPackage::layout($f->root, $f->reservation); $path = $f->root.'/'.array_key_first($layout['files']);
            unlink($path); $head = $f->head(); self::assertSame($f->completion, $f->protocol->complete(P::reference($f->reservation)));
            self::assertFalse(file_exists($path)); self::assertSame($head, $f->head());
            $this->refuses(fn() => (new FormationInstitution($f->root))->actor('laboratorium'));
            $detached = $f->journal->inspect(static fn(array $frame, FormationOwnerFrame $owner) => $owner);
            $this->refuses(fn() => $f->protocol->authorizedPendingInOwner($detached, $f->root));
            $foreign = $f->root.'/foreign-owner'; mkdir($foreign);
            (new FormationJournal($foreign))->inspect(function (array $frame, FormationOwnerFrame $owner) use ($f): void {
                $this->refuses(fn() => $f->protocol->authorizedPendingInOwner($owner, $f->root));
            });
        } finally { $f->close(); }
    }
    public static function publicationExpiry(): iterable { yield 'establishment' => [false]; yield 'founding-holder' => [true]; }
    #[DataProvider('publicationExpiry')]
    public function testExpiryDuringNativePublicationRetainsValidPendingCustody(bool $holder): void
    {
        $f = new F(false, constitutionLifetime: $holder ? 30 : 1800);
        try {
            $f->reserve(); $head = $f->head();
            $service = $f->service(static function (string $point) use ($f, $holder): void {
                if ($point === 'after-package-completion') { $f->setTime($holder ? $f->clock->at + 31 : $f->terms['expires_at']); }
            });
            $error = $this->refuses(fn() => $service->complete(P::reference($f->reservation)));
            self::assertSame($head, $f->head());
            $state = $f->journal->read()['state']; self::assertNull(P::history($state)['completion']);
            self::assertSame($f->reservation, $state['fresh_institutions']['reservation']);
            self::assertCount(18, FreshInstitutionPackage::verify($f->root, $f->reservation)['files']);
            $this->refuses(fn() => (new FormationInstitution($f->root))->actor('laboratorium'));
            $this->refuses(fn() => $f->protocol->complete(P::reference($f->reservation)));
            self::assertSame($head, $f->head());
            $evidence = getenv('PPC7_PUBLIC_EVIDENCE');
            if (is_string($evidence) && $evidence !== '') {
                $directory = $evidence.'/publication-expiry'; if (!is_dir($directory)) { mkdir($directory, 0700, true); }
                file_put_contents($directory.'/'.($holder ? 'holder' : 'establishment').'.json', json_encode([
                    'public_frame' => $f->journal->read(), 'reservation' => $f->reservation,
                    'native_completion' => FreshInstitutionPackage::verify($f->root, $f->reservation)['completion'],
                    'clock_after_publication' => $f->clock->at, 'refusal' => $error, 'head_before' => $head, 'head_after' => $f->head()], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
        } finally { $f->close(); }
    }
    public function testLegacyReadersAndGenericV4WriterKeepRefusing(): void
    {
        $f = new F();
        try {
            foreach (FormationInstitution::SEATS as $role => $seat) { $this->refuses(fn() => (new FormationInstitution($f->root))->witness($role)); }
            (new \App\Imperium\Runtime\Onboarding\Assignment\AssignmentMigration($f->store))->migrate($f->head());
            $this->refuses(fn() => (new \App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService($f->root))->install($f->package));
            $this->refuses(fn() => (new \App\Imperium\Runtime\Bootstrap\OperatorRootOperationalizationService($f->root))->seal($f->store->instance));
            $this->refuses(fn() => (new \App\Imperium\Runtime\Bootstrap\OperatorRootOwnership($f->root))->vacant($f->store));
            $state = $f->journal->read()['state']; unset($state['fresh_institutions']); $this->refuses(fn() => P::history($state));
            $state = $f->journal->read()['state']; $state['fresh_institutions']['schema'] .= '-unknown'; $this->refuses(fn() => P::history($state));
        } finally { $f->close(); }
    }
}
