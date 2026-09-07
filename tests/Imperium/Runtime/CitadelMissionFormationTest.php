<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationPlan, SessionExposure, UnavailableFormationTransport};
use App\Tests\Imperium\Runtime\Support\CitadelFormationFixture;
use PHPUnit\Framework\TestCase;

final class CitadelMissionFormationTest extends TestCase
{
    private CitadelFormationFixture $f;
    protected function setUp(): void { $this->f = new CitadelFormationFixture(); }
    protected function tearDown(): void { $this->f->close(); }

    public function testSiblingProcessesPublishOneIntakeThroughTheProductionCommand(): void
    {
        $f = $this->f;
        file_put_contents($f->root.'/concurrent-request.txt', "Exact sibling request.\r\n");
        $processes = [];
        for ($i = 0; $i < 3; ++$i) {
            $process = proc_open([PHP_BINARY, dirname(__DIR__, 2).'/fixtures/citadel-intake-worker.php', $f->root],
                [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $processes[] = [$process, $pipes];
        }
        $ids = [];
        foreach ($processes as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]); $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $stderr.$stdout);
            $ids[] = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR)['intake_id'];
        }
        self::assertCount(1, array_unique($ids));
        self::assertCount(1, $f->journal->read()['state']['intakes']);
        self::assertSame([], $f->transport->calls);
    }

    public function testMissingExaminationAndSubstitutedInstitutionCannotAppoint(): void
    {
        $f = $this->f;
        $state = $f->journal->read()['state'];
        $candidate = $f->candidate($state['citadel_id'], 'citadel.castellan');
        unset($candidate['examination']);
        $terms = ['candidate' => $candidate, 'scope' => $state['citadel_id'], 'seat' => 'citadel.castellan', 'generation' => 1];
        try {
            $f->run('appoint-castellan', ['candidate' => $candidate, 'decision' => $f->sign('APPOINT_CASTELLAN', $terms)]);
            self::fail('Unexamined candidate appointed.');
        } catch (\RuntimeException $e) { self::assertStringContainsString('CMF043', $e->getMessage()); }
        $actor = $f->run('personnel-authority-source', ['role' => 'garrison']);
        $actor['manifestation_id'] = 'invented-officer';
        $pair = sodium_crypto_sign_keypair();
        $delegation = ['role' => 'garrison', 'public_key' => base64_encode(sodium_crypto_sign_publickey($pair)), 'scope' => $state['citadel_id'], 'expires_at' => $f->clock->at + 600, 'actor' => $actor];
        $this->expectExceptionMessage('CMF122_INSTITUTION_CHAIN_INVALID');
        $f->run('delegate-personnel-evidence', ['delegation' => $delegation, 'decision' => $f->sign('DELEGATE_PERSONNEL_EVIDENCE', $delegation)]);
    }

    public function testUnknownPricingStopsBeforeTransmission(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview'); $terms['pricing'] = ['unknown' => true];
        $session = $f->grant($id, 'interview', $terms);
        try {
            $f->run('call', ['sessionId' => $session, 'attemptId' => 'pricing-attempt-01']);
            self::fail('Unknown pricing transmitted.');
        } catch (\RuntimeException $e) { self::assertStringContainsString('UNSUPPORTED_LIMITS_OR_PRICING', $e->getMessage()); }
        self::assertSame([], $f->transport->calls);
        self::assertSame([], $f->journal->read()['state']['sessions'][$session]['attempts']);
    }

    public function testRevocationDuringProviderIoPreventsResponseAdmission(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $nonce = $f->journal->read()['state']['sessions'][$session]['decision']['payload']['nonce'];
        $f->transport->response = CitadelFormationFixture::understanding();
        $f->transport->duringCall = function () use ($f, $nonce): void {
            $f->run('revoke-decision', ['envelope' => $f->sign('REVOKE_DECISION', ['nonce' => $nonce]), 'nonce' => $nonce]);
        };
        try {
            $f->run('call', ['sessionId' => $session, 'attemptId' => 'revocation-attempt']);
            self::fail('Revoked response admitted.');
        } catch (\RuntimeException $e) { self::assertStringContainsString('CMF022', $e->getMessage()); }
        self::assertCount(1, $f->transport->calls);
        self::assertArrayNotHasKey('understanding', $f->journal->read()['state']['intakes'][$id]);
    }

    public function testReceivingGapPreservesHandoffWithoutStepOneOrExecution(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $f->draft($id);
        $review = $f->approve($f->present($id));
        $f->run('reserve-mission', ['reviewId' => $review['review_id']]);
        $handoff = $f->run('deliver-handoff', ['intakeId' => $id]);
        $session = $f->grant($id, 'acceptance');
        $f->transport->response = ['disposition' => 'GAP', 'rationale' => 'A required input has no owner.', 'gaps' => 'Name the custodian for the required input.', 'dissent' => 'Preserve the deadline objection.'];
        $gap = $f->run('call', ['sessionId' => $session, 'attemptId' => 'receiving-gap-0001']);
        self::assertSame('GAP', $gap['response']['disposition']);
        self::assertSame($handoff['packet'], $f->journal->read()['state']['handoffs'][$id]['packet']);
        self::assertFalse($gap['execution_authority']);
        $this->expectExceptionMessage('CMF099_RECEIVING_ACCEPTANCE_REQUIRED');
        $f->run('validate-step-one', ['intakeId' => $id]);
    }

    public function testRealCommandsThroughReceivingAcceptanceWithoutExecution(): void
    {
        $f = $this->f;
        $intake = $f->receive();
        $id = $intake['intake_id'];
        self::assertSame("  Synthetic mission request.\r\nPreserve exact bytes.\n", $intake['exchange'][0]['content']);
        self::assertSame($intake, $f->receive());
        self::assertSame([], $f->transport->calls);
        self::assertDirectoryDoesNotExist($f->root.'/var/imperium/citadel/children');
        $f->appoint();
        $understanding = $f->understand($id);
        self::assertSame('I disagree with the assumed deadline.', $understanding['response']['dissent']);
        self::assertArrayNotHasKey('dossiers', $f->journal->read()['state']);
        $draft = $f->draft($id);
        self::assertSame(1, $draft['version']);
        self::assertDirectoryDoesNotExist($f->root.'/var/imperium/citadel/children');
        $terms = $f->present($id);
        $review = $f->approve($terms);
        $reservation = $f->run('reserve-mission', ['reviewId' => $review['review_id']]);
        self::assertSame($reservation, $f->run('reserve-mission', ['reviewId' => $review['review_id']]));
        self::assertDirectoryDoesNotExist($f->root.'/var/imperium/citadel/children');
        $handoff = $f->run('deliver-handoff', ['intakeId' => $id]);
        self::assertSame($handoff, $f->run('deliver-handoff', ['intakeId' => $id]));
        self::assertSame($intake['exchange'][0], $handoff['packet']['intake']['exchange'][0]);
        foreach ([$understanding, $draft] as $record) {
            $claim = $record['claim'];
            self::assertArrayHasKey($claim['claim_id'], $handoff['packet']['cognition_lineage']);
            foreach ([$claim['holder'], $claim['derivation']['lease']['issuer']] as $holder) {
                foreach (['persona', 'suitability', 'profile', 'examination', 'qualification'] as $source) {
                    self::assertArrayHasKey($holder['candidate'][$source], $handoff['packet']['personnel_evidence']['evidence']);
                }
            }
        }

        $session = $f->grant($id, 'acceptance');
        $f->transport->response = ['disposition' => 'ACCEPTED', 'rationale' => 'The original exchange and exact mandate are understood.', 'gaps' => '', 'dissent' => 'Deadline concern remains.'];
        $acceptance = $f->run('call', ['sessionId' => $session, 'attemptId' => 'acceptance-00001']);
        self::assertSame('curia.seneschal', $acceptance['claim']['holder']['seat']);
        self::assertFalse($acceptance['execution_authority']);
        self::assertCount(3, $f->transport->calls);
        self::assertSame('acceptance', $f->transport->calls[2]['request']['phase']);
        self::assertSame($handoff['packet'], $f->transport->calls[2]['request']['handoff']);
        $route = $f->run('route-mission', ['missionId' => $handoff['mission_id'], 'decision' => $f->sign('READ_EXISTING_MISSION', ['mission_id' => $handoff['mission_id']])]);
        self::assertSame($handoff['curia_id'], $route['curia_id']);
        self::assertFileDoesNotExist($f->root.'/var/imperium/bootstrap-state.json');
        FormationPlan::validate($handoff['packet']['dossier']['response']);
        $boundary = $f->run('validate-step-one', ['intakeId' => $id]);
        self::assertSame('STEP_1_SCHEMA_AND_FOREIGN_REFERENCES_VALIDATED_NO_EXECUTION', $boundary['status']);
    }

    public function testMissingUnderstandingAndUnsignedDecisionCannotDraft(): void
    {
        $id = $this->f->receive()['intake_id'];
        $this->f->appoint();
        $this->refuses('CMF064', fn () => $this->f->grant($id, 'drafting'));
        $terms = $this->f->terms($id, 'interview');
        $decision = $this->f->sign('AUTHORIZE_INTERVIEW_SESSION', $terms);
        $terms['model'] = 'changed-model';
        $this->refuses('CMF022', fn () => $this->f->cognition->grant($id, 'interview', $terms, $decision));
        self::assertSame([], $this->f->transport->calls);
    }

    public function testSessionAllowsNextQuestionWithoutAnotherGrantAndRevocationStopsIt(): void
    {
        $f = $this->f;
        $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $f->transport->response = [...$f::understanding(), 'disposition' => 'QUESTION', 'question' => 'What is the synthetic limit?', 'ready_to_request_drafting' => false];
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'question-000001']);
        $intake = $f->intake->find($id);
        $terms = ['intake_id' => $id, 'head' => $intake['record_digest'], 'content' => 'One hour.', 'changed_intent' => false];
        $f->run('reply', ['intakeId' => $id, 'content' => 'One hour.', 'changedIntent' => false, 'decision' => $f->sign('REPLY_TO_CITADEL', $terms)]);
        $f->transport->response = $f::understanding();
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'question-000002']);
        self::assertCount(1, $f->journal->read()['state']['sessions']);
        $nonce = $f->journal->read()['state']['sessions'][$session]['decision']['payload']['nonce'];
        $f->run('revoke-decision', ['envelope' => $f->sign('REVOKE_DECISION', ['nonce' => $nonce]), 'nonce' => $nonce]);
        $this->refuses('CMF022', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'question-000003']));
        self::assertCount(2, $f->transport->calls);
    }

    public function testUnknownOutcomeRetainsMaximumAndDoesNotRetry(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview'); $terms['total']['calls'] = 1;
        $session = $f->grant($id, 'interview', $terms);
        $f->transport->unknown = true;
        $this->refuses('CMF059', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'unknown-000001']));
        $this->refuses('PST112', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'unknown-000001']));
        $this->refuses('CMF032', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'unknown-000002']));
        $attempt = $f->journal->read()['state']['sessions'][$session]['attempts']['unknown-000001'];
        self::assertNull($attempt['settled']);
        self::assertSame('STARTED_OUTCOME_UNCERTAIN', $attempt['status']);
        self::assertCount(1, $f->transport->calls);
    }

    public function testProposalInUnderstandingIsRejectedAfterSealingAndNotRetried(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $f->transport->response = [...$f::understanding(), 'mission_plan' => $f::plan()];
        $this->refuses('CMF061', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'smuggled-00001']));
        self::assertArrayNotHasKey('understanding', $f->intake->find($id));
        $this->refuses('CMF061', fn () => $f->run('recover-response', ['sessionId' => $session, 'attemptId' => 'smuggled-00001']));
        self::assertCount(1, $f->transport->calls);
    }

    public function testSuccessionAndChangedIntentFenceOldSession(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $f->appoint('successor');
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'successor-0001']));
        $session = $f->grant($id, 'interview');
        $terms = ['intake_id' => $id, 'head' => $f->intake->find($id)['record_digest'], 'content' => 'A materially different mission.', 'changed_intent' => true];
        $f->run('reply', ['intakeId' => $id, 'content' => $terms['content'], 'changedIntent' => true, 'decision' => $f->sign('REPLY_TO_CITADEL', $terms)]);
        $this->refuses('CMF067', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'changed-000001']));
        self::assertSame([], $f->transport->calls);
    }

    public function testAggregateLedgerRejectsContentionWithoutOverspend(): void
    {
        $session = ['per_call' => array_combine(SessionExposure::FIELDS, [1, 10, 10, 20, 10]), 'total' => array_combine(SessionExposure::FIELDS, [2, 15, 20, 40, 20]), 'attempts' => []];
        SessionExposure::reserve($session, 'first', $session['per_call'], 'first');
        $this->refuses('CMF032', function () use (&$session): void { SessionExposure::reserve($session, 'second', $session['per_call'], 'second'); });
        self::assertCount(1, $session['attempts']);
    }

    public function testStepOneFieldsAreRequiredBeforeApproval(): void
    {
        $plan = CitadelFormationFixture::plan();
        unset($plan['mission_plan']['custody_restoration_conditions']);
        $this->refuses('CUR495', fn () => FormationPlan::validate($plan));
    }

    public function testProductionTransportRefusesBeforeTransmission(): void
    {
        $this->refuses('CMF034', fn () => (new UnavailableFormationTransport())->inspect([], []));
    }

    public function testBudgetContentionDuringTransportDoesNotHoldRegistryLock(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $terms = $f->terms($id, 'interview'); $terms['total']['calls'] = 1;
        $session = $f->grant($id, 'interview', $terms);
        $f->transport->response = $f::understanding();
        $f->transport->duringCall = function () use ($f, $session): void {
            // Reentrant access would block here if the outer provider held the registry lock.
            self::assertSame('STARTED_OUTCOME_UNCERTAIN', $f->journal->read()['state']['sessions'][$session]['attempts']['outer-attempt1']['status']);
            $this->refuses('CMF032', fn () => $f->cognition->call($session, 'inner-attempt1'));
        };
        $f->run('call', ['sessionId' => $session, 'attemptId' => 'outer-attempt1']);
        self::assertCount(1, $f->transport->calls);
    }

    public function testSealedResponseRecoveryPreservesUnknownMaximumWithoutAnotherCall(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $f->transport->duringCall = function () use ($f): void {
            $call = $f->transport->calls[count($f->transport->calls) - 1];
            $f->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class)->seal($call['claim'], json_encode($f::understanding(), JSON_THROW_ON_ERROR), $f->clock->now());
        };
        $f->transport->unknown = true;
        $this->refuses('CMF059', fn () => $f->run('call', ['sessionId' => $session, 'attemptId' => 'sealed-crash-001']));
        $admitted = $f->run('recover-response', ['sessionId' => $session, 'attemptId' => 'sealed-crash-001']);
        self::assertSame('UNDERSTOOD', $admitted['response']['disposition']);
        self::assertNull($f->journal->read()['state']['sessions'][$session]['attempts']['sealed-crash-001']['settled']);
        self::assertCount(1, $f->transport->calls);
    }

    public function testDeferredExpiredAndRefusedSessionDoNotReviveResources(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint();
        $session = $f->grant($id, 'interview');
        $control = function (string $disposition) use ($f, $session): void {
            $f->run('control-session', ['sessionId' => $session, 'disposition' => $disposition,
                'decision' => $f->sign('CONTROL_FORMATION_SESSION', ['session_id' => $session, 'disposition' => $disposition])]);
        };
        $control('DEFERRED');
        $this->refuses('CMF067', fn () => $f->cognition->call($session, 'deferred-call1'));
        $control('OPEN');
        $control('REFUSED');
        $this->refuses('CMF055', fn () => $control('OPEN'));
        $other = $f->grant($id, 'interview');
        $f->clock->at += 601;
        $this->refuses('CMF067', fn () => $f->cognition->call($other, 'expired-call01'));
        self::assertSame([], $f->transport->calls);
    }

    public function testChangedRegistryRefusesReservationAndKeepsApprovedDossier(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $f->draft($id);
        $terms = $f->present($id); $review = $f->approve($terms);
        $f->receive('Another pending inquiry.', 'other-request-001');
        $this->refuses('CMF087', fn () => $f->formation->reserve($review['review_id']));
        self::assertArrayNotHasKey('reservations', $f->journal->read()['state']);
        self::assertDirectoryDoesNotExist($f->root.'/var/imperium/citadel/children');
        // Reassess changed registry with Castellan; unchanged approved terms need no new approval.
        $f->understand($id);
        $reservation = $f->formation->reserve($review['review_id']);
        self::assertSame($terms['mission_id'], $reservation['mission_id']);
        self::assertCount(1, $f->journal->read()['state']['reviews']);
    }

    public function testPartialConstitutionKeepsIdentityFenceAndRefusesUnprovenChildReceipt(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $f->draft($id);
        $review = $f->approve($f->present($id)); $f->formation->reserve($review['review_id']);
        $block = $f->root.'/var/imperium/citadel/children';
        file_put_contents($block, 'synthetic unavailable child storage');
        // Failure is induced at filesystem boundary, never by fixture authority.
        set_error_handler(static fn (): bool => true);
        try { $this->refuses('PST101', fn () => $f->formation->deliver($id)); }
        finally { restore_error_handler(); unlink($block); }
        $reservation = $f->journal->read()['state']['reservations'][$id];
        self::assertSame('EFFECT_UNCERTAIN_IDENTITY_FENCED', $reservation['status']);
        $prepared = $reservation['prepared'];
        $child = $block.'/'.$prepared['curia_id'];
        (new \App\Imperium\Runtime\Persistence\ImmutableRecordStore($child, new \App\Imperium\Runtime\Persistence\AtomicTransition($child)))
            ->put('var/imperium/curia/handoffs', $prepared['handoff_id'], $prepared);
        // Presence alone cannot prove a historical effect. Real publication/interruption
        // and recovery are now covered by CitadelFormationCorrectionTest, CF02.
        $this->refuses('CMF130', fn () => $f->formation->deliver($id));
        self::assertSame($prepared, $f->journal->read()['state']['reservations'][$id]['prepared']);
        self::assertCount(1, glob($block.'/*', GLOB_ONLYDIR));
        self::assertSame('EFFECT_UNCERTAIN_IDENTITY_FENCED', $f->journal->read()['state']['reservations'][$id]['status']);
    }

    public function testNumberedObjectionAndRevisionPreserveOriginalVersion(): void
    {
        $f = $this->f; $id = $f->receive()['intake_id']; $f->appoint(); $f->understand($id); $first = $f->draft($id);
        $terms = $f->present($id);
        $lines = [$terms['dossier']['lines'][0]['line_digest']];
        $object = ['terms' => $terms, 'line_digests' => $lines, 'rationale' => 'Clarify the report title within the same scope.'];
        $f->formation->review($terms, 'OBJECT', $lines, $object['rationale'], $f->sign('REVIEW_MISSION_OBJECT', $object));
        $sessions = $f->journal->read()['state']['sessions'];
        $draftSession = array_values(array_filter($sessions, static fn (array $s): bool => $s['phase'] === 'drafting'))[0]['session_id'];
        $f->transport->response = $f::plan();
        $f->transport->response['mission_plan']['objective'] = 'Prepare the clearly named bounded synthetic report.';
        $second = $f->run('call', ['sessionId' => $draftSession, 'attemptId' => 'revision-00001']);
        self::assertSame(2, $second['version']);
        self::assertSame($first, $f->journal->read()['state']['dossiers'][$id][0]);
        self::assertCount(1, $f->transport->calls[2]['request']['prior_reviews']);
        $this->refuses('CMF093', fn () => $f->formation->presentation($id, 1, $terms['appointments'], $f->clock->at + 600));
    }

    private function refuses(string $code, callable $operation): void
    {
        try { $operation(); self::fail('Expected refusal '.$code); }
        catch (\RuntimeException $error) { self::assertStringContainsString($code, $error->getMessage()); }
    }
}
