<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\Formation\{FormationOwnerFrame, FreshInstitutionalProfileMapping};
use App\Imperium\Runtime\Onboarding\Assignment\{NativeAssignmentEvidence, AssignmentRule};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Act, Admission, AuthorityStore, Policy, Rules as R};
use App\Tests\Imperium\Runtime\Support\{OnboardingAuthorityFixture as A, FreshEstablishmentFixture as F, FreshDesignationFixture as D, NativeAssignmentProof};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** Reachability proofs, never an applied assignment fixture. */
final class NativeAssignmentChronologyTest extends TestCase
{
    public static function modes(): iterable { yield 'A' => ['A']; yield 'B' => ['B']; }

    #[DataProvider('modes')]
    public function testSignedPolicyCannotAdmitAnUnresolvedTargetOriginal(string $mode): void
    {
        $a = new A();
        try {
            $a->enroll(); $p = $a->policy();
            $p['body']['application_mode'] = $mode;
            foreach ($p['body']['effect_slots'] as &$slot) {
                if ($slot['effect'] === 'APPLY_BOOTSTRAP_ASSIGNMENTS') { $slot['authority_mode'] = $mode === 'A' ? 'policy_effect' : 'signed_act'; }
            } unset($slot);
            // A purported forward reference is adversarial input, not a produced mapping.
            $missing = ['schema' => 'imperium.bootstrap-source/v1', 'id' => 'unproduced-native-mapping', 'digest' => 'sha256:'.str_repeat('f', 64)];
            $p['body']['targets'][0]['profile_ref'] = $missing;
            unset($p['record_digest']); $p = R::seal($p); $e = $a->sign($p, 'AUTHORIZE_BOOTSTRAP_POLICY');
            Act::verify($a->store, $a->store->state($a->store->journal->read()['state']), $e, $p);
            $before = $a->head();
            $code = NativeAssignmentProof::refusal(fn() => (new Admission($a->store))->retain(A::json($e), A::json($p), array_map(A::json(...), array_values($a->sources))));
            self::assertSame('O2_SOURCE_MISSING', $code); self::assertSame($before, $a->head());
            NativeAssignmentProof::export('forward-'.$mode, ['mode' => $mode, 'policy' => $p, 'envelope' => $e,
                'public_key' => base64_encode($a->public), 'supporting' => array_values($a->sources), 'reached' => $code,
                'before' => $before, 'after' => $a->head(), 'signature_verified_before_admission' => true]);
        } finally { $a->close(); }
    }

    public function testRealNativeMappingAndDirectVerifierReachTheProtectedChronologyBoundary(): void
    {
        $f = new F(); $models = []; $observations = [];
        try {
            $a = $f->fresh->d->f; $state = $f->journal->read()['state'];
            $policy = array_values($state['onboarding']['policies'])[0]['record'];
            $policyBytes = A::json($policy); $rows = [];
            foreach (AssignmentRule::ROLES as $i => $seat) {
                $d = $models[] = new D($f, $seat);
                $event = $d->service->publish($d->envelope(), $d->m->candidate);
                $holder = NativeAssignmentProof::appoint($f, $d, 1);
                $mapping = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): array => (new FreshInstitutionalProfileMapping($f->root, $f->store))->prepareInOwner($owner, $seat));
                self::assertTrue(R::same($holder['profile_artifact'], Policy::content($mapping, 'formation-profile-mapping')['profile_artifact']));
                $code = NativeAssignmentProof::refusal(fn() => $f->store->checkSource($f->store->state($f->journal->read()['state']), R::reference($mapping)));
                self::assertSame('O2_SOURCE_MISSING', $code);
                self::assertNotSame($policy['body']['targets'][$i]['profile_ref'], R::reference($mapping));
                $b = $policy['body']['candidate_bindings'][$i];
                $rows[] = ['role' => $seat, 'provider' => $b['provider'], 'model_id' => $b['model_id'], 'model_version' => $b['model_version'],
                    'binding_ref' => $b['binding_ref'], 'configuration_ref' => $b['configuration_ref'],
                    'profile_ref' => $policy['body']['targets'][$i]['profile_ref'], 'profile_generation' => 1, 'binding_generation' => 1];
                $observations[$seat] = ['event' => $event, 'holder' => $holder, 'mapping' => $mapping, 'mapping_admission_reached' => $code];
            }
            $native = new NativeAssignmentEvidence($f->store, $models[0]->service, $f->personnel, $models[0]->m->preparation);
            $s = $f->store->state($f->journal->read()['state']); $originals = array_map(static fn(array $v): array => $v['record'], $s['evidence']);
            $before = $f->head();
            $direct = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => NativeAssignmentProof::refusal(fn() => $native->verifyInOwner($f->store, $owner, $policy, $rows, $originals, [])));
            self::assertSame('O2_SOURCE_KIND', $direct); // Original target is not a native mapping; later predicates are NOT reached.
            $nativeRows = $rows;
            foreach (AssignmentRule::ROLES as $i => $seat) { $nativeRows[$i]['profile_ref'] = R::reference($observations[$seat]['mapping']); }
            $mismatch = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => NativeAssignmentProof::refusal(fn() => $native->verifyInOwner($f->store, $owner, $policy, $nativeRows, $originals, [])));
            self::assertSame('O2_ASSIGNMENT_PROFILE', $mismatch);
            $detached = NativeAssignmentProof::refusal(fn() => $native->verify($policy, $rows, $originals, []));
            self::assertSame('PPC6_LIVE_ASSIGNMENT_OWNER_REQUIRED', $detached);
            $other = new AuthorityStore($f->journal, $f->clock, $f->store->instance, $f->store->citadel, $f->store->operator, $f->store->sourceCommit);
            $wrongStore = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): string => NativeAssignmentProof::refusal(fn() => $native->verifyInOwner($other, $owner, $policy, $rows, $originals, [])));
            self::assertSame('O2_PPC6_FIXED_ASSIGNMENT_STORE', $wrongStore);
            $expiredOwner = $f->journal->inspect(fn(array $frame, FormationOwnerFrame $owner): FormationOwnerFrame => $owner);
            $expired = NativeAssignmentProof::refusal(fn() => $native->verifyInOwner($f->store, $expiredOwner, $policy, $rows, $originals, []));
            self::assertSame('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED', $expired);
            // Existing admission competence cannot accept the late mapping as an arbitrary evidence act.
            $mapping = $observations[AssignmentRule::ROLES[0]]['mapping'];
            $terms = $f->store->make('imperium.bootstrap-proposed-terms/v1', 'late-mapping-admission',
                ['effect' => 'ADMIT_BOOTSTRAP_EVIDENCE', 'terms' => R::reference($mapping), 'required_completed_refs' => []], [R::reference($mapping)]);
            $envelope = $a->sign($terms, 'ADMIT_BOOTSTRAP_EVIDENCE', R::reference($policy));
            $admission = NativeAssignmentProof::refusal(fn() => (new Admission($f->store))->retain(A::json($envelope), A::json($terms), [A::json($mapping)]));
            self::assertSame('O2_SIGNED_TERMS_OUTSIDE_POLICY', $admission);
            self::assertSame($before, $f->head());
            self::assertSame($policyBytes, A::json(array_values($f->journal->read()['state']['onboarding']['policies'])[0]['record']));
            self::assertEmpty($f->journal->read()['state']['onboarding']['applications']);
            $input = $f->root.'/ppc9-reader-input.json'; $output = $f->root.'/ppc9-reader-output.json'; $errors = $f->root.'/ppc9-reader-errors.txt';
            file_put_contents($input, A::json(['at' => $f->clock->at, 'instance' => $f->store->instance, 'citadel' => $f->store->citadel,
                'operator' => $f->store->operator, 'source_commit' => $f->store->sourceCommit, 'rows' => $rows]));
            $readerStart = microtime(true); $readerUtc = gmdate('c');
            $process = proc_open([PHP_BINARY, __DIR__.'/Support/native-assignment-reader.php', $f->root, $input],
                [1 => ['file', $output, 'w'], 2 => ['file', $errors, 'w']], $pipes);
            self::assertIsResource($process); $deadline = $readerStart + 300;
            do { $status = proc_get_status($process); if (!$status['running']) { break; } usleep(1000); } while (microtime(true) < $deadline);
            $timedOut = $status['running']; if ($timedOut) { proc_terminate($process); }
            $closed = proc_close($process); $exit = $status['exitcode'] < 0 ? $closed : $status['exitcode'];
            NativeAssignmentProof::export('fresh-reader-attempt', ['start_utc' => $readerUtc, 'end_utc' => gmdate('c'),
                'seconds' => microtime(true) - $readerStart, 'bound_seconds' => 300, 'timed_out' => $timedOut,
                'status_exit' => $status['exitcode'], 'close_exit' => $closed, 'native_exit' => $exit,
                'stdout' => (string) file_get_contents($output), 'stderr' => (string) file_get_contents($errors)]);
            self::assertFalse($timedOut, 'PPC9 fresh reconstruction exceeded 300 seconds');
            self::assertSame(0, $exit, (string) file_get_contents($errors));
            $fresh = json_decode((string) file_get_contents($output), true, 32, JSON_THROW_ON_ERROR);
            self::assertSame('O2_SOURCE_KIND', $fresh['direct_native_reached']); self::assertSame($fresh['before'], $fresh['after']);
            foreach ($fresh['settings_roles'] as $code) { self::assertSame('O2_MODEL_SETTINGS_ABSENT', $code); }
            NativeAssignmentProof::exportCustody($f, 'native-chronology');
            NativeAssignmentProof::export('native-chronology', ['policy' => $policy, 'seats' => $observations,
                'direct_original_target' => $direct, 'direct_native_target' => $mismatch, 'detached' => $detached,
                'wrong_store' => $wrongStore, 'expired_owner' => $expired, 'late_admission' => ['envelope' => $envelope, 'object' => $terms, 'reached' => $admission],
                'before' => $before, 'after' => $f->head(), 'frame' => $f->journal->read(), 'fresh_process' => $fresh, 'fresh_process_exit' => $exit,
                'application_applied' => false, 'substantive_native_assignment_check_reached' => false]);
        } finally { foreach ($models as $d) { $d->close(); } $f->close(); }
    }
}
