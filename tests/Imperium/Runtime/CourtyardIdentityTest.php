<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,FormationCustodyFixture,CourtyardApplication,FrozenCourtyardFixture};
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J,FormationPersonnel,FormationPreparation};
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;

final class CourtyardIdentityTest extends TestCase
{
    private array $fixtures=[];
    protected function tearDown(): void { foreach ($this->fixtures as $f) { $f->close(); } }
    private function fixture(): CitadelFormationFixture { return $this->fixtures[]=new CitadelFormationFixture(); }
    private function refuse(callable $call,string $code): void
    {
        try { $call(); self::fail('Expected refusal '.$code); }
        catch (\RuntimeException $e) { self::assertStringContainsString($code,$e->getMessage()); }
    }

    public function testCompleteCanonicalOfflineRouteRetainsSeparateGatesAndSeneschalMandate(): void
    {
        $f=$this->fixture(); $c=new FormationCustodyFixture($f->root,$f->clock); $app=CourtyardApplication::build($c,$f->clock);
        foreach (['intake','formation','prepare'] as $name) {
            self::assertSame($app->find('imperium:courtyard:'.$name),$app->find('imperium:citadel:'.$name));
        }
        $other=$f->receive('Similar report for independent review.','similar-intent-0001');
        $intake=$f->receive(); $id=$intake['intake_id']; $citadel=$intake['citadel_id'];
        self::assertNull($intake['curia_id']); self::assertEmpty(glob($f->root.'/var/imperium/citadel/children/*'));
        $holder=$f->appoint(); self::assertSame('courtyard.courtthane',$holder['seat']);
        self::assertSame('LEGATE',$holder['officer_class']); self::assertSame('officer',$holder['profile_artifact']['artifact_class']);
        self::assertSame('generic-officer',$holder['substrate']['kind']);
        self::assertSame($holder,$f->personnel->currentCourtthane($f->journal->read()['state']));
        $run=fn(string $op,array $args)=>CourtyardApplication::run($app,$f->root,'imperium:courtyard:formation',$op,$args);
        $grant=function(string $phase) use($f,$id,$run): string {
            $terms=$f->terms($id,$phase); $terms['transport']=FormationCustodyFixture::authorization();
            if ($phase==='acceptance') {
                $terms['per_call']['input_tokens']=10000000; $terms['per_call']['cost_microusd']=10100000;
                $terms['total']['input_tokens']=50000000; $terms['total']['cost_microusd']=50500000;
            }
            $effect=match($phase){'interview'=>'AUTHORIZE_INTERVIEW_SESSION','drafting'=>'AUTHORIZE_EXACT_DRAFTING','acceptance'=>'AUTHORIZE_RECEIVING_ASSESSMENT'};
            return $run('grant',['intakeId'=>$id,'phase'=>$phase,'terms'=>$terms,'decision'=>$f->sign($effect,$terms)]);
        };
        $sid=$grant('interview');
        $c->wire->response=CitadelFormationFixture::understanding();
        $c->wire->response['disposition']='QUESTION'; $c->wire->response['question']='Should the overlapping request remain independent?';
        $c->wire->response['ready_to_request_drafting']=false;
        $run('call',['sessionId'=>$sid,'attemptId'=>'courtyard-question-01']);
        $head=$f->journal->read()['state']['intakes'][$id]; $reply='Yes, preserve independent review and dissent.';
        $terms=['intake_id'=>$id,'head'=>$head['record_digest'],'content'=>$reply,'changed_intent'=>false];
        $run('reply',['intakeId'=>$id,'content'=>$reply,'changedIntent'=>false,'decision'=>$f->sign('REPLY_TO_CITADEL',$terms)]);
        $c->wire->response=CitadelFormationFixture::understanding();
        $c->wire->response['overlap']='The related intake '.$other['intake_id'].' remains deliberately independent.';
        $understood=$run('call',['sessionId'=>$sid,'attemptId'=>'courtyard-understood-01']);
        self::assertCount(2,$understood['claim']['prepared_operation'] ? $f->journal->read()['state']['sessions'][$sid]['attempts'] : []);
        self::assertEmpty($f->journal->read()['state']['dossiers'] ?? []);
        $this->refuse(fn()=>$run('call',['sessionId'=>$sid,'attemptId'=>'closed-interview-01']),'CMF067');
        $this->refuse(fn()=>$grant('drafting'),'CMF064');
        $charter=['scope'=>'Present material only.','questions'=>'Prepare exact plan.','inputs'=>'Original exchange and disclosed overlap.',
            'offices'=>[],'external_effects'=>[],'disclosure'=>'Only recording offline infrastructure.','expected_return'=>'Numbered proposal.',
            'stop_conditions'=>'Changed scope.','amendment_triggers'=>'New investigation.','retention'=>'Original evidence.', 'expires_at'=>$f->clock->at+600];
        $request=$run('drafting-request',['intakeId'=>$id,'charter'=>$charter]);
        self::assertSame('I understand. I am ready to draft a proposal. Do you approve?',$request['approval_question']);
        self::assertSame('imperium.citadel-drafting-request/v2',$request['schema']);
        self::assertEmpty($f->journal->read()['state']['dossiers'] ?? []);
        $draftSid=$grant('drafting'); $c->wire->response=CitadelFormationFixture::plan();
        $draft=$run('call',['sessionId'=>$draftSid,'attemptId'=>'courtyard-draft-01']);
        self::assertSame(1,$draft['version']); self::assertSame(1,$draft['lines'][0]['line_number']);
        self::assertEmpty(glob($f->root.'/var/imperium/citadel/children/*'));
        $review=$f->approve($f->present($id));
        $run('reserve-mission',['reviewId'=>$review['review_id']]);
        $handoff=$run('deliver-handoff',['intakeId'=>$id]);
        self::assertSame($citadel,$handoff['packet']['citadel_id']);
        self::assertFalse($handoff['constitution']['root_principal_created']);
        self::assertCount(3,$handoff['constitution']['occupants']);
        self::assertNotSame($holder['manifestation_id'],$handoff['constitution']['occupants']['curia.seneschal']['manifestation_id']);
        $acceptSid=$grant('acceptance'); $c->wire->response=['disposition'=>'ACCEPTED','rationale'=>'I accept this Curia mandate.','gaps'=>'','dissent'=>'Preserve the deadline objection.'];
        $accepted=$run('call',['sessionId'=>$acceptSid,'attemptId'=>'courtyard-accept-01']);
        self::assertSame('curia.seneschal',$accepted['claim']['holder']['seat']);
        self::assertFalse($accepted['execution_authority']);
        self::assertFalse($run('validate-step-one',['intakeId'=>$id])['execution_authority']);
        self::assertSame(['issue'=>4,'consume'=>4,'dispatch'=>4],$c->counts());
        self::assertSame($accepted,CourtyardApplication::run($app,$f->root,'imperium:citadel:formation','recover-response',['sessionId'=>$acceptSid,'attemptId'=>'courtyard-accept-01']));
        self::assertSame(['issue'=>4,'consume'=>4,'dispatch'=>4],$c->counts());
    }

    #[DataProvider('wrongAppointments')]
    public function testExactAppointmentRejectsWrongSeatScopeEffectGenerationAndEvidence(string $case): void
    {
        $f=$this->fixture(); $state=$f->journal->read()['state']; $scope=$state['citadel_id'];
        $candidate=$f->candidate($scope,$case==='old-profile'?'citadel.castellan':($case==='seneschal-profile'?'curia.seneschal':'courtyard.courtthane'));
        $terms=['candidate'=>$candidate,'scope'=>$scope,'seat'=>'courtyard.courtthane','generation'=>1];
        if ($case==='wrong-scope') { $terms['scope']='citadel-'.str_repeat('a',32); }
        if ($case==='wrong-seat') { $terms['seat']='citadel.castellan'; }
        if ($case==='wrong-generation') { $terms['generation']=2; }
        $decision=$f->sign($case==='old-effect'?'APPOINT_CASTELLAN':($case==='oversight-effect'?'APPOINT_CASTELLAN_OVERSIGHT':'APPOINT_COURTTHANE'),$terms);
        if ($case==='wrong-version') { $decision['payload']['schema']='imperium.citadel-owner-decision/v2'; }
        $before=$f->journal->read();
        $this->refuse(fn()=>$f->run('appoint-courtthane',['candidate'=>$candidate,'decision'=>$decision]),'CMF022');
        self::assertSame($before,$f->journal->read());
    }
    public static function wrongAppointments(): iterable
    {
        foreach (['old-profile','seneschal-profile','wrong-scope','wrong-seat','wrong-generation','old-effect','oversight-effect','wrong-version'] as $case) { yield $case=>[$case]; }
    }

    #[DataProvider('holderMutations')]
    public function testProductionCurrentnessRejectsChangedBindingBeforeCognitionOrCustody(string $field): void
    {
        $f=$this->fixture(); $f->appoint(); $id=$f->receive()['intake_id'];
        $c=new FormationCustodyFixture($f->root,$f->clock); $terms=$f->terms($id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
        $sid=$f->grant($id,'interview',$terms); $call=$c->pending($f,$sid);
        $f->journal->change(function(array &$state) use($field): void {
            if ($field==='seat') { $state['courtthane']['seat']='citadel.castellan'; }
            elseif ($field==='holder') { $state['courtthane']['manifestation_id']='forged-holder'; }
            elseif ($field==='profile') { $state['courtthane']['profile_artifact']['profile_version']='2.0'; }
            elseif ($field==='cognition') { $state['courtthane']['cognitive_artifact']='Altered cognition'; }
            elseif ($field==='class') { $state['courtthane']['officer_class']='DELEGATE'; }
            else { $state['courtthane']=$state['locksmith']; }
        });
        $before=$f->journal->read();
        $this->refuse(fn()=>$f->cognition->authorizationSource($id,'interview'),'CY003');
        $this->refuse(fn()=>$c->broker->invoke(...$call),'FC099');
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$c->counts()); self::assertSame($before,$f->journal->read());
    }
    public static function holderMutations(): iterable { foreach (['seat','holder','profile','cognition','class','locksmith'] as $f) { yield $f=>[$f]; } }

    #[DataProvider('appointmentChanges')]
    public function testAppointmentCurrentnessCannotRenewAConsumedOldClaim(string $change): void
    {
        $f=$this->fixture(); $holder=$f->appoint(); $id=$f->receive()['intake_id'];
        $c=new FormationCustodyFixture($f->root,$f->clock); $terms=$f->terms($id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
        $sid=$f->grant($id,'interview',$terms); $call=$c->pending($f,$sid); $attempt=$f->journal->read()['state']['sessions'][$sid]['attempts'];
        if ($change==='replaced') { $new=$f->appoint('successor'); self::assertNotSame($holder['manifestation_id'],$new['manifestation_id']); }
        elseif ($change==='expired') { $f->clock->at+=3600; }
        else {
            $nonce=$holder['decision']['payload']['nonce'];
            $f->run('revoke-decision',['envelope'=>$f->sign('REVOKE_DECISION',['nonce'=>$nonce]),'nonce'=>$nonce]);
        }
        $before=$f->journal->read();
        if ($change!=='replaced') { $this->refuse(fn()=>$f->personnel->currentCourtthane($before['state']),'CMF022'); }
        $this->refuse(fn()=>$c->broker->invoke(...$call),'FC099');
        self::assertSame($before,$f->journal->read()); self::assertSame($attempt,$before['state']['sessions'][$sid]['attempts']);
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$c->counts());
    }
    public static function appointmentChanges(): iterable { yield ['replaced']; yield ['expired']; yield ['revoked']; }

    public function testOldChildFenceWithoutReceiptCannotPublishUnderCourtthaneCode(): void
    {
        $f=$this->fixtures[]=new FrozenCourtyardFixture('child-publication'); $c=new FormationCustodyFixture($f->root,$f->clock);
        $app=CourtyardApplication::build($c,$f->clock); $m=$f->data['metadata'];
        // Adverse copy only: model interruption before effect by withholding the
        // receipt in this disposable restored root. Frozen input stays immutable.
        unlink($f->root.'/'.$m['receipt_path']); $before=$c->journal->read();
        $this->refuse(fn()=>CourtyardApplication::run($app,$f->root,'imperium:courtyard:formation','deliver-handoff',['intakeId'=>$m['intake_id']]),'CY001');
        self::assertSame($before,$c->journal->read()); self::assertFileDoesNotExist($f->root.'/'.$m['receipt_path']);
    }

    public function testLegacyMutationAndPreparationRefuseWithoutOversightOrSeneschalAuthority(): void
    {
        $f=$this->fixture(); $before=$f->journal->read();
        $this->refuse(fn()=>$f->run('appoint-castellan',['candidate'=>[],'decision'=>[]]),'CY001');
        $this->refuse(fn()=>$f->personnel->currentCastellan($before['state']),'CY001');
        $this->refuse(fn()=>$f->run('appoint-castellan-oversight',['candidate'=>[],'decision'=>[]]),'CMF101');
        self::assertSame($before,$f->journal->read());
        $candidate=$f->candidate($before['state']['citadel_id'],'courtyard.courtthane');
        $terms=['candidate'=>$candidate,'scope'=>$before['state']['citadel_id'],'seat'=>'courtyard.courtthane','generation'=>1];
        $request=['schema'=>'imperium.citadel-preparation-request/v1','citadel_id'=>$terms['scope'],'trust_fingerprint'=>$before['state']['trust']['fingerprint'],
            'effect'=>'APPOINT_COURTTHANE','object'=>$terms,'expires_at'=>$f->clock->at+600,
            'source_identity'=>['commit'=>str_repeat('a',40),'tree'=>str_repeat('b',40),'public_export_digest'=>str_repeat('c',64)]];
        $c=new FormationCustodyFixture($f->root,$f->clock); $app=CourtyardApplication::build($c,$f->clock);
        $path=$f->root.'/prepare.json'; file_put_contents($path,json_encode($request));
        $tester=new CommandTester($app->find('imperium:courtyard:prepare')); $before=$f->journal->read();
        self::assertSame(0,$tester->execute(['mode'=>'decision','public-file'=>$path]),$tester->getDisplay());
        $packet=json_decode($tester->getDisplay(),true,512,JSON_THROW_ON_ERROR);
        self::assertSame($before,$f->journal->read()); self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$c->counts());
        self::assertFalse($packet['activation']);
        $binding=$f->run('appoint-courtthane',['candidate'=>$candidate,'decision'=>$f->signPrepared($packet)]);
        self::assertSame('courtyard.courtthane',$binding['profile_artifact']['target']['id']);
        $request['effect']='APPOINT_CASTELLAN';
        $this->refuse(fn()=>(new FormationPreparation($f->clock))->prepare($request),'CRP004');
    }

    #[DataProvider('frozenCases')]
    public function testFrozenOldBytesRecoverWithOriginalAttributionButNoFreshCourtthaneAuthority(string $case): void
    {
        $f=$this->fixtures[]=new FrozenCourtyardFixture($case); $c=new FormationCustodyFixture($f->root,$f->clock);
        $app=CourtyardApplication::build($c,$f->clock); $m=$f->data['metadata'];
        self::assertSame($f->data['frame'],$c->journal->read());
        $run=fn(string $name,string $op,array $args)=>CourtyardApplication::run($app,$f->root,$name,$op,$args);
        $before=$c->journal->read();
        foreach (['imperium:courtyard:formation','imperium:citadel:formation'] as $name) {
            self::assertSame($m['admitted'],$run($name,'recover-response',['sessionId'=>$m['session_id'],'attemptId'=>$m['attempt_id']]));
            $this->refuse(fn()=>$run($name,'authorization-source',['intakeId'=>$m['intake_id'],'phase'=>'interview']),'CY001');
            $binding=$before['state']['castellan'];
            $this->refuse(fn()=>$run($name,'appoint-courtthane',['candidate'=>$binding['candidate'],'decision'=>$binding['decision']]),'CMF022');
        }
        self::assertSame($before,$c->journal->read());
        if ($case==='child-publication') {
            $f->clock->at+=7200;
            $handoff=$run('imperium:courtyard:formation','deliver-handoff',['intakeId'=>$m['intake_id']]);
            $generation=$c->journal->read()['generation'];
            self::assertSame($handoff,$run('imperium:citadel:formation','deliver-handoff',['intakeId'=>$m['intake_id']]));
            self::assertSame($generation,$c->journal->read()['generation']);
            self::assertCount(1,glob($f->root.'/var/imperium/citadel/children/*'));
            self::assertEmpty($handoff['acceptances']); self::assertFalse($handoff['execution_authority']);
            self::assertSame('RECOGNIZED_COMPLETED_EFFECT',$c->journal->read()['state']['reservations'][$m['intake_id']]['reconciliation']['kind']);
        } else {
            $old=$before['state']['sessions'][$m['pending_session']];
            $this->refuse(fn()=>$run('imperium:courtyard:formation','call',['sessionId'=>$m['pending_session'],'attemptId'=>'fresh-old-attempt-01']),'CY001');
            $this->refuse(fn()=>$run('imperium:citadel:formation','call',['sessionId'=>$m['pending_session'],'attemptId'=>$m['unknown_attempt']]),'PST112');
            self::assertSame($old,$c->journal->read()['state']['sessions'][$m['pending_session']]);
            self::assertNull($old['attempts'][$m['unknown_attempt']]['settled']);
        }
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$c->counts());
        $f->assertOriginalBytes();
    }
    public static function frozenCases(): iterable { yield ['child-publication']; yield ['custody']; }
}
