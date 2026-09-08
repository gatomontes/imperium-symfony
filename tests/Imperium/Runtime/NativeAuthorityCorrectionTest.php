<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Citadel\NativeAuthority\NativeJournal;
use App\Imperium\Runtime\Garrison\{SubordinatePersonaCanonicalAdmissionService, GarrisonInventoryResponseService};
use App\Tests\Imperium\Runtime\Support\{NativeAuthorityFixture as F, CitadelAuthorityFixture as Old};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** NA-IR01/02: generated roots and real signatures; no installed-state inputs. */
final class NativeAuthorityCorrectionTest extends TestCase
{
    public static function legacyDeliveries(): iterable
    {
        yield 'same delivery' => [F::DELIVERY];
        yield 'second delivery identity' => ['guildhall-garrison-persona-admission-delivery-eeeeeeeeeeeeeeeeeeee'];
    }

    #[DataProvider('legacyDeliveries')]
    public function testRetainedLegacyPersonaCannotAcquireAnotherNativeEffect(string $deliveryId): void
    {
        $f = $this->legacyFixture();
        try {
            $beforeBytes = $this->legacyBytes($f);
            $f->write('var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions/'.$deliveryId.'.json', F::delivery());
            $f->ready(); $p = $f->protocol(); $before = $this->frame($f);
            $consumer = new SubordinatePersonaCanonicalAdmissionService($f->root, $p);
            try { $consumer->admit($deliveryId, $f->occupancy['binding_id']); self::fail('Duplicate retained legacy Persona admitted as a new native effect'); }
            catch (\RuntimeException $e) { self::assertSame('NAT037_CANDIDATE_ALREADY_ADMITTED', $e->getMessage()); }
            self::assertSame($before, $this->frame($f));
            self::assertSame([], $this->frame($f)['state']['admissions']);
            self::assertSame($beforeBytes, $this->legacyBytes($f));
            $inventory = (new GarrisonInventoryResponseService($f->root, $p))->respond(F::INQUIRY);
            self::assertCount(1, $inventory['inventory_records']);
            self::assertSame('synthetic-persona', $inventory['inventory_records'][0]['persona_id']);
        } finally { $f->close(); }
    }

    public function testDifferentNativePersonaCoexistsWithExactLegacyCustody(): void
    {
        $f = $this->legacyFixture();
        try {
            $beforeBytes = $this->legacyBytes($f); $f->ready(); $p = $f->protocol();
            $id = 'guildhall-garrison-persona-admission-delivery-eeeeeeeeeeeeeeeeeeee';
            $d = F::delivery(); unset($d['record_digest']);
            $d['candidate_id'] = $d['persona']['id'] = 'synthetic-second-persona';
            $d['candidate_digest'] = A::digest($d['persona']);
            $f->write('var/imperium/offices/garrison/inbox/canonical-subordinate-persona-admissions/'.$id.'.json', A::seal($d));
            $before = $this->frame($f)['generation'];
            $admitted = (new SubordinatePersonaCanonicalAdmissionService($f->root, $p))->admit($id, $f->occupancy['binding_id']);
            self::assertSame('ADMITTED', $admitted['disposition']);
            self::assertSame($before + 1, $this->frame($f)['generation']);
            self::assertCount(1, $this->frame($f)['state']['admissions']);
            $inventory = (new GarrisonInventoryResponseService($f->root, $p))->respond(F::INQUIRY);
            self::assertSame(['synthetic-persona', 'synthetic-second-persona'], array_column($inventory['inventory_records'], 'persona_id'));
            $legacyPath = glob($f->root.'/var/imperium/offices/garrison/custody/*.json')[0];
            self::assertSame(A::read($legacyPath), $inventory['inventory_records'][0]);
            self::assertSame($beforeBytes, $this->legacyBytes($f));
            self::assertSame($f->occupancy['record_digest'], $admitted['constable']['binding_digest']);
        } finally { $f->close(); }
    }

    public static function actBoundaries(): iterable
    {
        foreach (['decision', 'trust'] as $boundary) foreach ([false, true] as $expired) {
            yield $boundary.($expired ? '/already expired' : '/advances after acceptance') => [$boundary, $expired];
        }
    }

    #[DataProvider('actBoundaries')]
    public function testActHasOneAcceptanceInstantAndAuthenticRecovery(string $boundary, bool $expired): void
    {
        $f = new F();
        try {
            $t = $f->base->now;
            if ($boundary === 'trust') $f->policy['expires_at'] = $t + 1;
            self::assertSame(0, $f->enroll()[0]);
            $signed = $f->sign('REVOKE_DECISION', ['expected_head' => null, 'target' => str_repeat('a', 48)]);
            $signed['decision']['payload']['expires_at'] = $t + 1;
            $signed['decision']['signature'] = $f->signature($signed['decision']['payload']);
            $clock = $this->advancingClock($expired ? $t + 1 : $t, $t + 1, 1);
            $p = $f->protocol(clock: $clock); $before = $this->frame($f);
            if ($expired) {
                try { $p->apply($signed); self::fail('Expired authority committed'); }
                catch (\RuntimeException $e) { self::assertSame('NAT013_EXACT_NATIVE_DECISION_REQUIRED', $e->getMessage()); }
                self::assertSame($before, $this->frame($f)); return;
            }
            $result = $p->apply($signed); $completed = $this->frame($f);
            // A separate later process/clock must recognize the original evidence.
            $f->base->now = $t + 8000;
            self::assertSame($result, $f->protocol()->apply($signed));
            self::assertSame($t, $result['accepted_at']);
            self::assertSame($t, $completed['state']['acts'][$signed['decision']['payload']['nonce']]['accepted_at']);
            self::assertSame($before['generation'] + 1, $completed['generation']);
            self::assertSame($completed, $this->frame($f));
        } finally { $f->close(); }
    }

    public static function admissionBoundaries(): iterable
    {
        foreach (['trust', 'roster', 'revision'] as $boundary) foreach ([false, true] as $expired) {
            yield $boundary.($expired ? '/already expired' : '/advances after validation') => [$boundary, $expired, 6];
        }
        foreach (['trust', 'roster', 'revision'] as $boundary) yield $boundary.'/advances between checks' => [$boundary, false, 1];
    }

    #[DataProvider('admissionBoundaries')]
    public function testAdmissionHasOneAcceptanceInstantAndAuthenticRecovery(string $boundary, bool $expired, int $initialReads): void
    {
        $f = new F();
        try {
            $t = $f->base->now + 100;
            if ($boundary === 'trust') $f->policy['expires_at'] = $t + 1;
            self::assertSame(0, $f->enroll()[0]);
            $adoption = $f->adoption('garrison.constable');
            if ($boundary === 'roster') $adoption['expires_at'] = $t + 1;
            $f->protocol()->apply($f->sign('ADOPT_ROSTER', $adoption));
            $revision = $f->revision();
            if ($boundary === 'revision') {
                $input = $f->base->requestInput(); $input['expires_at'] = $t + 1;
                $revision['request'] = $f->base->garrison()->prepare($input);
            }
            $signed = $f->sign('REVISE_GARRISON', $revision); $f->protocol()->apply($signed);
            // Reviewed code reads six times for current/interval checks and then once for acceptance.
            $clock = $this->advancingClock($expired ? $t + 1 : $t, $t + 1, $initialReads);
            $consumer = new SubordinatePersonaCanonicalAdmissionService($f->root, $f->protocol(clock: $clock));
            $before = $this->frame($f);
            if ($expired) {
                try { $consumer->admit(F::DELIVERY, $f->occupancy['binding_id']); self::fail('Expired admission authority committed'); }
                catch (\RuntimeException $e) { self::assertSame($boundary === 'revision' ? 'NAT034_EFFECT_INTERVAL_INVALID' : 'NAT032_CURRENT_ROSTER_REQUIRED', $e->getMessage()); }
                self::assertSame($before, $this->frame($f)); return;
            }
            $result = $consumer->admit(F::DELIVERY, $f->occupancy['binding_id']);
            $completed = $this->frame($f);
            // Revoke while valid, then advance beyond all intervals. Recovery grants no new effect.
            $f->base->now = $t;
            $f->protocol()->apply($f->sign('REVOKE_DECISION', ['expected_head' => $completed['state']['head'], 'target' => $signed['decision']['payload']['nonce']]));
            $retained = $this->frame($f); $f->base->now = $t + 8000;
            $recovery = new SubordinatePersonaCanonicalAdmissionService($f->root, $f->protocol());
            self::assertSame($result, $recovery->admit(F::DELIVERY, $f->occupancy['binding_id']));
            self::assertSame($t, $completed['state']['admissions'][F::DELIVERY]['accepted_at']);
            self::assertSame($completed['state']['admissions'][F::DELIVERY]['disposition'], $result);
            self::assertSame($before['generation'] + 1, $completed['generation']);
            self::assertSame($retained, $this->frame($f));
        } finally { $f->close(); }
    }

    public static function timedEffects(): iterable
    {
        foreach (['ADOPT_ROSTER', 'SUPERSEDE_RECRUITER', 'REVISE_GARRISON'] as $effect) yield $effect => [$effect];
    }

    #[DataProvider('timedEffects')]
    public function testActIntervalsAndCurrentnessUseTheSignedAcceptanceInstant(string $effect): void
    {
        $f = new F();
        try {
            $t = $f->base->now;
            if ($effect === 'ADOPT_ROSTER') {
                self::assertSame(0, $f->enroll()[0]); $o = $f->adoption('garrison.constable');
                $o['expires_at'] = $t + 1;
            } else {
                $f->ready();
                if ($effect === 'REVISE_GARRISON') {
                    $o = $f->revision(); $request = $o['request']; unset($request['record_digest']);
                    $request['terms']['expires_at'] = $t + 1;
                    $request['request_id'] = 'garrison-authority-request-'.A::digest($request['terms']);
                    $o['request'] = A::seal($request);
                } else {
                    $o = $f->adoption('conscription.recruiter');
                    $o['prior_roster'] = $f->protocol()->resolve('conscription.recruiter')['roster']['record_digest'];
                    $o['actor'] = 'synthetic-successor'; $o['occupancy_generation'] = 3;
                    $o['evidence'] = ['source_id' => 'synthetic-succession', 'source_digest' => str_repeat('f', 64)];
                    $o['expires_at'] = $t + 1;
                }
            }
            $signed = $f->sign($effect, $o); $signed['decision']['payload']['expires_at'] = $t + 1;
            $signed['decision']['signature'] = $f->signature($signed['decision']['payload']);
            $before = $this->frame($f);
            $result = $f->protocol(clock: $this->advancingClock($t, $t + 1, 1))->apply($signed);
            self::assertSame($t, $result['accepted_at']);
            $completed = $this->frame($f);
            self::assertSame($t, $completed['state']['acts'][$signed['decision']['payload']['nonce']]['accepted_at']);
            $f->base->now += 8000;
            self::assertSame($result, $f->protocol()->apply($signed));
            self::assertSame($before['generation'] + 1, $completed['generation']);
            self::assertSame($completed, $this->frame($f));
        } finally { $f->close(); }
    }

    private function legacyFixture(): F
    {
        $o = Old::occupancy(); unset($o['record_digest']);
        $o['persona_admission_disposition_authority'] = $o['custody_registration_authority'] = true;
        $f = new F(A::seal($o));
        try {
            $legacy = (new SubordinatePersonaCanonicalAdmissionService($f->root))->admit(F::DELIVERY, $f->occupancy['binding_id']);
            self::assertSame('ADMITTED', $legacy['disposition']);
            self::assertCount(1, glob($f->root.'/var/imperium/offices/garrison/custody/*.json'));
            self::assertSame($f->occupancy['record_digest'], $legacy['constable']['binding_digest']);
            return $f;
        } catch (\Throwable $e) { $f->close(); throw $e; }
    }

    private function legacyBytes(F $f): array
    {
        $bytes = [];
        foreach (['occupancy', 'custody', 'subordinate-persona-admission-dispositions'] as $dir) {
            foreach (glob($f->root.'/var/imperium/offices/garrison/'.$dir.'/*.json') as $path) $bytes[$path] = file_get_contents($path);
        }
        return $bytes;
    }

    private function frame(F $f): array { return (new NativeJournal($f->root))->inspect(fn (array $frame) => $frame); }

    private function advancingClock(int $first, int $later, int $initialReads): Clock
    {
        return new class($first, $later, $initialReads) implements Clock {
            private int $reads = 0;
            public function __construct(private int $first, private int $later, private int $initialReads) {}
            public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.(++$this->reads <= $this->initialReads ? $this->first : $this->later)); }
        };
    }
}
