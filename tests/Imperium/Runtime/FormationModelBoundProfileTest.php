<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J, FormationProfileContract, FormationModelBoundProfileContract};
use App\Tests\Imperium\Runtime\Support\ModelBoundFormationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FormationModelBoundProfileTest extends TestCase
{
    public static function seats(): iterable { yield ['courtyard.courtthane']; yield ['clavium.locksmith']; }

    #[DataProvider('seats')]
    public function testActualNewVersionApprovalQualificationAndSeparateAppointment(string $seat): void
    {
        $x = new ModelBoundFormationFixture($seat);
        try {
            $assembly = $x->assembly();
            self::assertSame($x->artifact, $assembly['profile_artifact']);
            self::assertSame(['candidate', 'under_examination', 'approved'], array_column(array_column($assembly['profile_lifecycle'], 'transition'), 'to'));
            self::assertFalse($assembly['activation_performed']);
            $state = $x->f->journal->read()['state'];
            self::assertArrayNotHasKey('profile_designations', $state);
            $terms = ['candidate' => $x->candidate, 'scope' => $state['citadel_id'], 'seat' => $seat, 'generation' => 1];
            $decision = $x->f->sign($seat === 'courtyard.courtthane' ? 'APPOINT_COURTTHANE' : 'APPOINT_FORMATION_LOCKSMITH', $terms);
            $holder = $seat === 'courtyard.courtthane' ? $x->f->personnel->appointCourtthane($x->candidate, $decision) : $x->f->personnel->appointLocksmith($x->candidate, $decision);
            self::assertSame($x->artifact, $holder['profile_artifact']);
            self::assertSame(1, $holder['generation']);
            self::assertFalse($holder['execution_authority']);
        } finally { $x->close(); }
    }

    public function testOldIngressAndValidatorRetainTheirRejections(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $before = $x->f->journal->read();
            try { $x->f->personnel->record($x->envelope); self::fail('Old ingress accepted new envelope'); }
            catch (\RuntimeException $e) { self::assertSame('CMF041_PERSONNEL_EVIDENCE_INVALID', $e->getMessage()); }
            self::assertSame($before, $x->f->journal->read());
            $this->expectExceptionMessage('CMF124_FORMATION_PROFILE_INVALID');
            FormationProfileContract::validate($x->artifact, $x->artifact['source_persona'], $x->seat);
        } finally { $x->close(); }
    }

    public static function corruptions(): iterable
    {
        foreach (['profile', 'examination', 'qualification', 'finding', 'old-approval', 'signature', 'delegation-revocation', 'expiry'] as $case) { yield $case => [$case]; }
    }

    #[DataProvider('corruptions')]
    public function testCurrentVersionCannotBorrowAuthorityOrCorruptOriginals(string $case): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $before = $x->f->journal->read();
            if ($case === 'old-approval') { $x->candidate['profile_approval'] = $x->oldCandidate['profile_approval']; }
            elseif ($case === 'expiry') { $x->f->clock->at += 3600; }
            elseif ($case === 'delegation-revocation') {
                $d = $before['state']['personnel_delegations'][$x->envelope['payload']['delegation']];
                $nonce = $d['decision']['payload']['nonce'];
                $x->f->signatures->revoke($x->f->sign('REVOKE_DECISION', ['nonce' => $nonce]), $nonce);
                $before = $x->f->journal->read();
            } else {
                // Corruption of a supplied frame is an adverse unit input, never
                // a producer or current-state positive fixture.
                $state = $before['state'];
                $id = $case === 'finding' ? array_values($state['personnel_evidence'][$x->candidate['examination']]['payload']['content']['findings'])[0]
                    : $x->candidate[$case === 'signature' ? 'profile' : $case];
                if ($case === 'signature') { $state['personnel_evidence'][$id]['signature'] = base64_encode(str_repeat('x', 64)); }
                else { $state['personnel_evidence'][$id]['payload']['content']['tampered'] = true; }
                try { $x->f->personnel->candidate($state, $x->candidate, $state['citadel_id'], $x->seat); self::fail('Corrupt original accepted'); }
                catch (\RuntimeException $e) { self::assertStringStartsWith('PPC202_', $e->getMessage()); }
                self::assertSame($before, $x->f->journal->read());
                return;
            }
            try { $x->assembly(); self::fail('Stale authority accepted'); }
            catch (\RuntimeException $e) { self::assertNotSame('', $e->getMessage()); }
            self::assertSame($before, $x->f->journal->read());
        } finally { $x->close(); }
    }

    public function testForeignTargetAndChangedConfigurationRefuse(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            foreach (['target', 'configuration'] as $case) {
                $profile = $x->artifact;
                if ($case === 'target') { $profile['target']['id'] = 'oracle.augur'; }
                else { $profile['model_binding']['configuration']['temperature'] = 1; }
                try { FormationModelBoundProfileContract::validate($profile, $profile['source_persona'], $x->seat); self::fail('Changed bytes accepted'); }
                catch (\RuntimeException $e) { self::assertSame('PPC201_MODEL_BOUND_PROFILE_INVALID', $e->getMessage()); }
            }
        } finally { $x->close(); }
    }

    public function testFreshProcessReconstructsOriginalsAndObservesDelegationRevocation(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $path = $x->f->root.'/public-worker-input.json';
            file_put_contents($path, json_encode(['operation' => 'candidate', 'at' => $x->f->clock->at, 'candidate' => $x->candidate, 'seat' => $x->seat], JSON_THROW_ON_ERROR));
            $run = static function () use ($x, $path): array {
                $process = proc_open([PHP_BINARY, __DIR__.'/Support/model-bound-profile-worker.php', $x->f->root, $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
                self::assertIsResource($process);
                $output = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]);
                fclose($pipes[1]); fclose($pipes[2]);
                return [proc_close($process), $output];
            };
            self::assertSame([0, "VERIFIED_COMPONENT_ONLY\n"], $run());
            $state = $x->f->journal->read()['state'];
            $nonce = $state['personnel_delegations'][$x->envelope['payload']['delegation']]['decision']['payload']['nonce'];
            $x->f->signatures->revoke($x->f->sign('REVOKE_DECISION', ['nonce' => $nonce]), $nonce);
            $before = $x->f->journal->read();
            [$status, $output] = $run();
            self::assertSame(1, $status);
            self::assertStringContainsString('CMF022_AUTHENTIC_EXACT_DECISION_REQUIRED', $output);
            self::assertSame($before, $x->f->journal->read());
        } finally { $x->close(); }
    }

    public function testCorruptedVersionedOriginalCannotDowngradeToHistoricalValidation(): void
    {
        $x = new ModelBoundFormationFixture();
        try {
            $state = $x->f->journal->read()['state'];
            $id = $x->candidate['profile'];
            unset($state['personnel_evidence'][$id]['payload']['schema']);
            $artifact = &$state['personnel_evidence'][$id]['payload']['content']['artifact'];
            unset($artifact['model_binding'], $artifact['content_digest']);
            $artifact['content_digest'] = 'sha256:'.J::digest($artifact);
            unset($artifact);
            $this->expectExceptionMessage('PPC202_ORIGINAL_EVIDENCE_INVALID');
            $x->f->personnel->candidate($state, $x->candidate, $state['citadel_id'], $x->seat);
        } finally { $x->close(); }
    }
}
