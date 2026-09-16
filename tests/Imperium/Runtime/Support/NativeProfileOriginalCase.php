<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationOwnerFrame, FreshInstitutionalProfileMapping};
use App\Imperium\Runtime\Onboarding\Assignment\AssignmentRule;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use App\Tests\Imperium\Runtime\Support\{FreshEstablishmentFixture as F, FreshDesignationFixture as D, NativeAssignmentProof as Proof};
use PHPUnit\Framework\TestCase;

abstract class NativeProfileOriginalCase extends TestCase
{
    protected function proveOriginalBoundary(string $seat): void
    {
        $f = new F(); $models = []; $observations = [];
        try {
            foreach ([$seat] as $seat) {
                $d = $models[] = new D($f, $seat); $m = $d->m;
                self::assertTrue(R::same($m->artifact, $m->assembly()['profile_artifact']));
                $candidate = $m->candidate;
                foreach (['consistency', 'governance', 'practice', 'security', 'security-block', 'qualification'] as $case) {
                    $state = $f->journal->read()['state']; $bad = $candidate;
                    $exam = $state['personnel_evidence'][$candidate['examination']]['payload'];
                    if (in_array($case, ['consistency', 'governance', 'practice', 'security'], true)) {
                        $finding = $state['personnel_evidence'][$exam['content']['findings'][$case]]['payload'];
                        $finding['content']['disposition'] = 'FAIL';
                        $exam['content']['findings'][$case] = Proof::record($f, 'senate-'.$case, $finding);
                        $exam['sources'] = [$candidate['profile'], ...array_values($exam['content']['findings'])];
                    } elseif ($case === 'security-block') { $exam['content']['security_block'] = true; }
                    $bad['examination'] = Proof::record($f, 'senate', $exam);
                    $bad['profile_approval'] = $f->sign('APPROVE_FORMATION_PROFILE', [
                        'profile' => $candidate['profile'], 'examination' => $bad['examination'], 'scope' => $f->store->citadel, 'seat' => $seat]);
                    $qualification = $state['personnel_evidence'][$candidate['qualification']]['payload'];
                    $qualification['sources'] = [$candidate['persona'], $candidate['suitability'], $candidate['profile'], $bad['examination']];
                    $qualification['content']['profile_approval_digest'] = J::digest($bad['profile_approval']);
                    if ($case === 'qualification') { $qualification['content']['criteria_results']['bounded_authority'] = false; }
                    $bad['qualification'] = Proof::record($f, 'conscription', $qualification);
                    $before = $f->head();
                    $code = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => Proof::refusal(fn() =>
                        $f->personnel->authorizedModelCandidateInOwner($owner, $bad, $f->store->citadel, $seat)));
                    $expected = match ($case) {
                        'security-block' => 'CMF043_CANDIDATE_CHAIN_INVALID',
                        'qualification' => 'CMF126_QUALIFICATION_CRITERIA_UNMET',
                        default => 'CMF125_COMPLETE_EXAMINATION_REQUIRED',
                    };
                    self::assertSame($expected, $code); self::assertSame($before, $f->head());
                    $observations[$seat]['substantive_originals'][$case] = ['candidate' => $bad, 'reached' => $code, 'before' => $before, 'after' => $f->head()];
                }
                // A valid candidate still passes after independent negative evidence is retained.
                self::assertTrue(R::same($m->artifact, $m->assembly()['profile_artifact']));
                $event = $d->service->publish($d->envelope(), $candidate);
                $holder = Proof::appoint($f, $d, 1);
                $mapping = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): array => (new FreshInstitutionalProfileMapping($f->root, $f->store))->prepareInOwner($owner, $seat));
                $d->successor();
                $successor = $d->service->publish($d->envelope($event), $m->candidate);
                $before = $f->head();
                $code = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => Proof::refusal(fn() => (new FreshInstitutionalProfileMapping($f->root, $f->store))->prepareInOwner($owner, $seat)));
                self::assertSame('PPC7_MAPPING_INDEPENDENT_HOLDER', $code); self::assertSame($before, $f->head());
                $nextHolder = Proof::appoint($f, $d, 2);
                $nextMapping = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): array => (new FreshInstitutionalProfileMapping($f->root, $f->store))->prepareInOwner($owner, $seat));
                self::assertNotSame(R::reference($mapping), R::reference($nextMapping));
                self::assertSame(1, $holder['generation']); self::assertSame(2, $nextHolder['generation']);
                self::assertNotSame($holder['profile_artifact']['content_digest'], $nextHolder['profile_artifact']['content_digest']);
                self::assertSame($event['envelope']['payload']['model_binding']['binding_ref'], $successor['envelope']['payload']['model_binding']['binding_ref']);
                $observations[$seat]['successor'] = ['initial_event' => $event, 'initial_holder' => $holder, 'initial_mapping' => $mapping,
                    'successor_event' => $successor, 'before_appointment_reached' => $code, 'successor_holder' => $nextHolder, 'successor_mapping' => $nextMapping,
                    'application_replacement_executed' => false];
            }
            self::assertEmpty($f->journal->read()['state']['onboarding']['applications']);
            Proof::exportCustody($f, 'native-profile-originals-'.$seat);
            Proof::export('native-profile-originals-'.$seat, ['seats' => $observations, 'frame' => $f->journal->read(),
                'profile_fits_defined' => false, 'assignment_evidence_positive' => false]);
        } finally { foreach ($models as $d) { $d->close(); } $f->close(); }
    }
}
