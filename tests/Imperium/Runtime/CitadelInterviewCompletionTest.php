<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CitadelInterviewCompletionTest extends TestCase
{
    private CitadelFormationFixture $f;
    protected function setUp(): void { $this->f = new CitadelFormationFixture(); }
    protected function tearDown(): void { $this->f->close(); }

    public function testCompletionFencesCallsControlsAndReplacementGrantsWithoutExposure(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview'); $sid = $f->grant($id, 'interview', $terms);
        $other = $f->grant($id, 'interview', $terms);
        $controls = [];
        foreach (['OPEN', 'DEFERRED', 'REFUSED'] as $d) {
            $controls[$d] = $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $sid, 'disposition' => $d]);
        }
        $replacement = $f->sign('AUTHORIZE_INTERVIEW_SESSION', $terms);
        $f->transport->response = $f::understanding();
        $admitted = $f->run('call', ['sessionId' => $sid, 'attemptId' => 'understood-0001']);
        $before = $f->journal->read();
        foreach ([$sid, $other] as $session) {
            $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'after-complete-001']));
            self::assertSame('COMPLETED', $before['state']['sessions'][$session]['status']);
        }
        foreach ($controls as $d => $decision) {
            $this->refuses('CMF055', fn () => $f->run('control-session', ['sessionId' => $sid, 'disposition' => $d, 'decision' => $decision]));
        }
        $this->refuses('CMF067', fn () => $f->run('grant', ['intakeId' => $id, 'phase' => 'interview', 'terms' => $terms, 'decision' => $replacement]));
        $this->refuses('CMF067', fn () => $f->grant($id, 'interview'));
        foreach (['call', 'recover-response'] as $op) {
            self::assertSame($admitted, $f->run($op, ['sessionId' => $sid, 'attemptId' => 'understood-0001']));
        }
        self::assertSame($before, $f->journal->read());
        self::assertCount(1, $f->transport->calls);
        $this->refuses('CMF064', fn () => $f->grant($id, 'drafting'));
        self::assertArrayHasKey('dossier_id', $f->draft($id));
        self::assertCount(2, $f->transport->calls);
    }

    public function testConcurrentReturnCannotOverwriteFirstAdmittedUnderstanding(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $sid = $f->grant($id, 'interview'); $other = $f->grant($id, 'interview'); $winner = null;
        $f->transport->response = $f::understanding();
        $f->transport->duringCall = function () use ($f, $other, &$winner): void {
            $f->transport->duringCall = null;
            $winner = $f->run('call', ['sessionId' => $other, 'attemptId' => 'concurrent-winner-001']);
        };
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $sid, 'attemptId' => 'stale-return-001']));
        $before = $f->journal->read();
        self::assertSame($winner, $before['state']['intakes'][$id]['understanding']);
        self::assertCount(2, $before['state']['intakes'][$id]['exchange']);
        self::assertSame('SEALED_PENDING_ADMISSION', $before['state']['sessions'][$sid]['attempts']['stale-return-001']['status']);
        self::assertNotNull($before['state']['sessions'][$sid]['attempts']['stale-return-001']['settled']);
        $this->refuses('CMF067', fn () => $f->run('recover-response', ['sessionId' => $sid, 'attemptId' => 'stale-return-001']));
        self::assertSame($before, $f->journal->read()); self::assertCount(2, $f->transport->calls);
    }

    public function testUnknownSealedWorkRetainsMaximumAfterAnotherAttemptCompletes(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $sid = $f->grant($id, 'interview');
        $f->transport->unknown = true;
        $f->transport->duringCall = function () use ($f): void {
            $call = $f->transport->calls[0];
            $f->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class)
                ->seal($call['claim'], json_encode($f::understanding(), JSON_THROW_ON_ERROR), $f->clock->now());
        };
        $this->refuses('CMF059', fn () => $f->run('call', ['sessionId' => $sid, 'attemptId' => 'unknown-sealed-001']));
        $unknown = $f->journal->read()['state']['sessions'][$sid]['attempts']['unknown-sealed-001'];
        $f->transport->unknown = false; $f->transport->duringCall = null; $f->transport->response = $f::understanding();
        $f->run('call', ['sessionId' => $sid, 'attemptId' => 'understood-later-001']);
        $before = $f->journal->read();
        $this->refuses('CMF067', fn () => $f->run('recover-response', ['sessionId' => $sid, 'attemptId' => 'unknown-sealed-001']));
        self::assertSame($unknown, $before['state']['sessions'][$sid]['attempts']['unknown-sealed-001']);
        self::assertNull($unknown['settled']); self::assertSame($before, $f->journal->read());
        self::assertCount(2, $f->transport->calls);
    }

    #[DataProvider('replyKinds')]
    public function testSignedReplyAllowsFreshAuthorityButNeverRevivesCompletedGrants(bool $changed): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $sid = $f->grant($id, 'interview');
        $f->transport->response = $f::understanding();
        $original = $f->run('call', ['sessionId' => $sid, 'attemptId' => 'before-reply-001']);
        $intake = $f->journal->read()['state']['intakes'][$id];
        $terms = ['intake_id' => $id, 'head' => $intake['record_digest'], 'content' => 'Clarified scope.', 'changed_intent' => $changed];
        $f->run('reply', ['intakeId' => $id, 'content' => $terms['content'], 'changedIntent' => $changed, 'decision' => $f->sign('REPLY_TO_CITADEL', $terms)]);
        // Even an old projection of OPEN cannot erase the durable completion fence.
        $f->journal->change(static function (array &$state) use ($sid): void { $state['sessions'][$sid]['status'] = 'OPEN'; });
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $sid, 'attemptId' => 'old-after-reply-001']));
        $this->refuses('CMF055', fn () => $f->run('control-session', ['sessionId' => $sid, 'disposition' => 'OPEN', 'decision' => $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $sid, 'disposition' => 'OPEN'])]));
        self::assertSame($original, $f->run('recover-response', ['sessionId' => $sid, 'attemptId' => 'before-reply-001']));
        $fresh = $f->grant($id, 'interview'); self::assertNotSame($sid, $fresh);
        $f->run('call', ['sessionId' => $fresh, 'attemptId' => 'fresh-after-reply-001']);
        self::assertCount(2, $f->transport->calls);
        self::assertSame($intake['intent_version'] + (int) $changed, $f->journal->read()['state']['intakes'][$id]['intent_version']);
    }

    public static function replyKinds(): array { return [[false], [true]]; }
    private function refuses(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Expected refusal '.$code); }
        catch (\RuntimeException $e) { self::assertStringContainsString($code, $e->getMessage()); }
    }
}
