<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture, CitadelPublicationInterruption};
use App\Imperium\Runtime\Citadel\Formation\FormationJournal;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/Support/CitadelPublicationInterruption.php';

final class CitadelFormationCorrectionTest extends TestCase
{
    private CitadelFormationFixture $f;
    protected function setUp(): void { $this->f = new CitadelFormationFixture(); }
    protected function tearDown(): void
    {
        CitadelPublicationInterruption::$root = null; CitadelPublicationInterruption::$before = false;
        CitadelPublicationInterruption::$afterUnlockRoot = null; CitadelPublicationInterruption::$afterUnlock = null;
        $this->f->close();
    }

    public function testCF01IndirectReopeningMustRemainRefused(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $this->control($session, 'REFUSED');
        foreach (['DEFERRED', 'OPEN'] as $disposition) {
            try { $this->control($session, $disposition); }
            catch (\RuntimeException $e) { self::assertStringContainsString('CMF055', $e->getMessage()); }
        }
        self::assertSame('REFUSED', $f->journal->read()['state']['sessions'][$session]['status']);
        self::assertCount(0, $f->transport->calls);
    }

    public function testCF02RealPublishedChildMustReconcileAfterApprovalExpiry(): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        $receiptPath = $this->interrupt($id);
        $bytes = file_get_contents($receiptPath);
        $before = $f->journal->read()['state'];
        self::assertArrayNotHasKey($id, $before['handoffs'] ?? []);
        self::assertSame('EFFECT_UNCERTAIN_IDENTITY_FENCED', $before['reservations'][$id]['status']);
        $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
        $handoff = $f->run('deliver-handoff', ['intakeId' => $id]);
        self::assertSame($before['reservations'][$id]['prepared']['handoff_id'], $handoff['handoff_id']);
        self::assertSame($bytes, file_get_contents($receiptPath));
        self::assertSame($handoff, $f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertCount(2, $f->transport->calls);
    }

    public function testCF01DirectAndStaleControlsPreserveAccountingAndLegitimateResume(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview'); $terms['total']['calls'] = 2;
        $session = $f->grant($id, 'interview', $terms);
        $stale = [];
        foreach (['DEFERRED', 'OPEN'] as $s) { $stale[$s] = $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $session, 'disposition' => $s]); }
        $f->transport->response = $f::understanding();
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'before-defer-001']);
        $before = $f->journal->read()['state']['sessions'][$session]['attempts'];
        $this->control($session, 'DEFERRED', $stale['DEFERRED']);
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'while-defer-001']));
        $this->control($session, 'OPEN', $stale['OPEN']);
        self::assertSame($before, $f->journal->read()['state']['sessions'][$session]['attempts']);
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'after-resume-001']);
        $this->refuses('CMF032', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'no-budget-0001']));
        $this->control($session, 'REFUSED');
        $closed = $f->journal->read();
        foreach (['OPEN', 'DEFERRED', 'REFUSED'] as $s) {
            $this->refuses('CMF055', fn () => $this->control($session, $s));
        }
        foreach ($stale as $s => $decision) { $this->refuses('CMF055', fn () => $this->control($session, $s, $decision)); }
        self::assertSame($closed, $f->journal->read());
        self::assertCount(2, $f->transport->calls);
        $new = $f->grant($id, 'interview');
        self::assertNotSame($session, $new);
        $f->run('call', ['sessionId' => $new, 'attemptId' => 'new-grant-0001']);
        self::assertSame($closed['state']['sessions'][$session], $f->journal->read()['state']['sessions'][$session]);
        self::assertCount(3, $f->transport->calls);
    }

    public function testCF01RetainedRefusalFencesOldReopenedProjectionWithRemainingBudget(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $this->control($session, 'REFUSED');
        // Compatibility fixture: project the status that C0 demonstrated older code wrote.
        // The authentic refusal remains in the journal; no new authority is fabricated.
        $f->journal->change(static function (array &$state) use ($session): void { $state['sessions'][$session]['status'] = 'OPEN'; });
        $before = $f->journal->read();
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'legacy-reopen1']));
        $this->refuses('CMF055', fn () => $this->control($session, 'DEFERRED'));
        self::assertSame($before, $f->journal->read());
        self::assertCount(0, $f->transport->calls);
        self::assertSame([], $before['state']['sessions'][$session]['attempts']);
    }

    public function testCF01RefusalFencesSealedAdmissionAndRetainsUnknownExposure(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $session = $f->grant($id, 'interview');
        $f->transport->duringCall = function () use ($f): void {
            $call = $f->transport->calls[0];
            $f->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class)
                ->seal($call['claim'], json_encode($f::understanding(), JSON_THROW_ON_ERROR), $f->clock->now());
        };
        $f->transport->unknown = true;
        $this->refuses('CMF059', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'unknown-refusal-001']));
        $this->control($session, 'REFUSED'); $before = $f->journal->read();
        $this->refuses('CMF067', fn () => $f->run('recover-response', ['sessionId' => $session, 'attemptId' => 'unknown-refusal-001']));
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'after-refusal-002']));
        self::assertSame($before, $f->journal->read());
        self::assertNull($before['state']['sessions'][$session]['attempts']['unknown-refusal-001']['settled']);
        self::assertCount(1, $f->transport->calls);
    }

    public function testCF02LaterRevocationSuccessionAndTrustExpiryDoNotEraseCompletedEffect(): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        $path = $this->interrupt($id); $bytes = file_get_contents($path);
        // Same timestamp, later journal generation: order, not guessed wall time, controls.
        $nonce = $review['decision']['payload']['nonce'];
        $f->run('revoke-decision', ['nonce' => $nonce, 'envelope' => $f->sign('REVOKE_DECISION', ['nonce' => $nonce])]);
        $f->appoint('post-effect-successor');
        $f->clock->at += 86401;
        $handoff = $f->run('deliver-handoff', ['intakeId' => $id]);
        self::assertSame($bytes, file_get_contents($path));
        self::assertSame([], $handoff['acceptances']);
        self::assertFalse($handoff['execution_authority']);
        $after = $f->journal->read();
        self::assertSame('RECOGNIZED_COMPLETED_EFFECT', $after['state']['reservations'][$id]['reconciliation']['kind']);
        self::assertFalse($after['state']['reservations'][$id]['reconciliation']['new_authority']);
        self::assertArrayHasKey($nonce, $after['state']['revoked_decisions']);
        $this->refuses('CMF043', fn () => $f->grant($id, 'acceptance'));
        self::assertSame($after, $f->journal->read());
        self::assertCount(2, $f->transport->calls);
    }

    public function testCF02ExpiryWithoutAnyEffectCannotCreateChild(): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
        $before = $f->journal->read();
        $this->refuses('CMF094', fn () => $f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertSame($before, $f->journal->read());
        self::assertDirectoryDoesNotExist($f->root.'/var/imperium/citadel/children');
        self::assertCount(2, $f->transport->calls);
    }

    #[DataProvider('beforeEffectChanges')]
    public function testCF02BeforeEffectChangesCannotCreateOrReleaseUncertainChild(string $change, string $code): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        CitadelPublicationInterruption::$root = $f->root;
        CitadelPublicationInterruption::$before = true;
        $this->refuses('SYNTHETIC_INTERRUPTION_BEFORE_CHILD_RENAME', fn () => $f->run('deliver-handoff', ['intakeId' => $id]));
        CitadelPublicationInterruption::$before = false;
        self::assertFileDoesNotExist(CitadelPublicationInterruption::$publishedPath);
        if ($change === 'revocation') {
            $nonce = $review['decision']['payload']['nonce'];
            $f->run('revoke-decision', ['nonce' => $nonce, 'envelope' => $f->sign('REVOKE_DECISION', ['nonce' => $nonce])]);
        } else { $f->clock->at = $review['decision']['payload']['expires_at'] + 1; }
        $before = $f->journal->read();
        $this->refuses($code, fn () => $f->run('deliver-handoff', ['intakeId' => $id]));
        $publisher = new \App\Imperium\Runtime\MasterMason\ChildCuriaFormationService($f->root, $f->journal, $f->signatures, $f->personnel, $f->clock);
        $this->refuses($code, fn () => $publisher->publish($id));
        $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
        $this->refuses('CMF090', fn () => $f->run('expire-unused-reservation', ['intakeId' => $id]));
        self::assertSame($before, $f->journal->read());
        self::assertFileDoesNotExist(CitadelPublicationInterruption::$publishedPath);
        self::assertCount(2, $f->transport->calls);
    }

    public static function beforeEffectChanges(): array { return [['expiry', 'CMF094'], ['revocation', 'CMF022']]; }

    #[DataProvider('invalidReceipts')]
    public function testCF02UnverifiableReceiptsStayFenced(string $mutation): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        $path = $this->interrupt($id); $bytes = file_get_contents($path);
        $receipt = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
        switch ($mutation) {
            case 'absent': unlink($path); break;
            case 'wrong-identity': $receipt['curia_id'] = 'curia-'.str_repeat('0', 32); break;
            case 'mismatched-bytes': $receipt['packet']['intake']['exchange'][0]['content'] .= ' substituted'; break;
            case 'corrupt': $receipt['record_digest'] = str_repeat('0', 64); break;
            case 'missing-authority': unset($receipt['publication']); break;
            case 'wrong-frame': $receipt['publication']['authority_frame_digest'] = str_repeat('0', 64); break;
            case 'wrong-reservation': $receipt['publication']['reservation_digest'] = str_repeat('0', 64); break;
            case 'invalid-time': $receipt['publication']['authorized_at'] = $review['decision']['payload']['expires_at'] + 1; break;
            case 'corrupt-native-proof': $receipt['publication']['institutions']['garrison']['occupancy']['status'] = 'INACTIVE'; break;
        }
        if ($mutation !== 'absent') {
            if ($mutation !== 'corrupt') { unset($receipt['record_digest']); $receipt['record_digest'] = FormationJournal::digest($receipt); }
            file_put_contents($path, json_encode($receipt, JSON_THROW_ON_ERROR));
        }
        $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
        $before = $f->journal->read();
        $this->refuses($mutation === 'absent' ? 'CMF094' : 'CMF130', fn () => $f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertSame($before, $f->journal->read());
        self::assertSame('EFFECT_UNCERTAIN_IDENTITY_FENCED', $before['state']['reservations'][$id]['status']);
        self::assertCount(2, $f->transport->calls);
        self::assertCount(1, glob($f->root.'/var/imperium/citadel/children/*', GLOB_ONLYDIR));
    }

    public static function invalidReceipts(): array
    {
        return array_map(static fn (string $s): array => [$s], ['absent', 'wrong-identity', 'mismatched-bytes', 'corrupt', 'missing-authority', 'wrong-frame', 'wrong-reservation', 'invalid-time', 'corrupt-native-proof']);
    }

    public function testCF02ConcurrentCommandRecoveryPublishesExactlyOneParentTransition(): void
    {
        $f = $this->f; [$id, $review] = $this->reserve();
        $path = $this->interrupt($id); $bytes = file_get_contents($path);
        $f->clock->at = $review['decision']['payload']['expires_at'] + 1;
        $before = $f->journal->read();
        file_put_contents($f->root.'/recovery-request.json', json_encode(['operation' => 'deliver-handoff', 'arguments' => ['intakeId' => $id]], JSON_THROW_ON_ERROR));
        $workers = [];
        for ($i = 0; $i < 3; ++$i) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/fixtures/citadel-recovery-worker.php', $f->root, (string) $f->clock->at],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process); fclose($pipes[0]); $workers[] = [$process, $pipes];
        }
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $output = stream_get_contents($pipes[1]); $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $errors.$output);
            $results[] = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        }
        self::assertSame($results[0], $results[1]); self::assertSame($results[0], $results[2]);
        $after = $f->journal->read();
        self::assertSame($before['generation'] + 1, $after['generation']);
        self::assertSame($before['state']['registry_generation'] + 1, $after['state']['registry_generation']);
        self::assertCount(1, $after['state']['handoffs']); self::assertCount(1, $after['state']['child_roots']);
        self::assertCount(count($before['state']['occupied_manifestations']) + 3, $after['state']['occupied_manifestations']);
        self::assertCount(1, glob($f->root.'/var/imperium/citadel/children/*', GLOB_ONLYDIR));
        self::assertSame($bytes, file_get_contents($path));
        self::assertSame($after['state']['handoffs'][$id], $f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertSame($after, $f->journal->read());
        self::assertSame($before['state']['sessions'], $after['state']['sessions']);
    }

    public function testCF02ConcurrentCompletionBetweenSnapshotAndRecognitionIsIdempotent(): void
    {
        $f = $this->f; [$id] = $this->reserve(); $other = null;
        CitadelPublicationInterruption::$afterUnlockRoot = $f->root;
        // Real unlock, then another delivery completes before the stale reader resumes.
        CitadelPublicationInterruption::$afterUnlock = function () use ($f, $id, &$other): void {
            $other = $f->run('deliver-handoff', ['intakeId' => $id]);
        };
        set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
        try { $handoff = $f->run('deliver-handoff', ['intakeId' => $id]); }
        finally { restore_error_handler(); }
        self::assertSame($other, $handoff);
        self::assertCount(1, $f->journal->read()['state']['handoffs']);
        self::assertCount(2, $f->transport->calls);
    }

    private function reserve(): array
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $f->draft($id);
        $review = $f->approve($f->present($id));
        $f->run('reserve-mission', ['reviewId' => $review['review_id']]);
        return [$id, $review];
    }

    private function interrupt(string $id): string
    {
        CitadelPublicationInterruption::$root = $this->f->root;
        $this->refuses('SYNTHETIC_INTERRUPTION_AFTER_REAL_CHILD_RENAME', fn () => $this->f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertNotNull(CitadelPublicationInterruption::$publishedPath);
        self::assertFileExists(CitadelPublicationInterruption::$publishedPath);
        return CitadelPublicationInterruption::$publishedPath;
    }

    private function control(string $session, string $disposition, ?array $decision = null): void
    {
        $f = $this->f;
        $f->run('control-session', ['sessionId' => $session, 'disposition' => $disposition,
            'decision' => $decision ?? $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $session, 'disposition' => $disposition])]);
    }

    private function refuses(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Expected refusal '.$code); }
        catch (\RuntimeException $error) { self::assertStringContainsString($code, $error->getMessage()); }
    }
}
