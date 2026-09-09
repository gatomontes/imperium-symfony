<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Tests\Imperium\Runtime\Support\{CitadelFormationFixture,FormationCustodyFixture};
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal as J,FormationSessionAuthority,UnavailableFormationTransport,UnavailableFormationWireAdapter};
use App\Command\CitadelFormationCommand;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class FormationClaimCustodyTest extends TestCase
{
    private CitadelFormationFixture $f;
    private FormationCustodyFixture $c;
    private string $id;
    private string $sid;
    protected function setUp(): void
    {
        $this->f=$f=new CitadelFormationFixture(); $f->appoint(); $this->id=$f->receive()['intake_id'];
        $this->c=new FormationCustodyFixture($f->root,$f->clock);
        $terms=$f->terms($this->id,'interview'); $terms['transport']=FormationCustodyFixture::authorization();
        $this->sid=$f->grant($this->id,'interview',$terms);
    }
    protected function tearDown(): void { $this->f->close(); }
    private function retainedAttempt(): array { return $this->f->journal->read()['state']['sessions'][$this->sid]['attempts']['custody-attempt-0001']; }
    private function refusal(callable $call): \RuntimeException
    {
        try { $call(); } catch (\RuntimeException $e) { self::assertNull($e->getPrevious()); return $e; }
        self::fail('Expected custody refusal');
    }

    public function testActualCommandAndDISealSettleAdmitAndRecognizeWithoutReissue(): void
    {
        $f=$this->f; $command=new CitadelFormationCommand($this->c->cognition,$f->personnel,$f->signatures,$f->formation);
        $path=$f->root.'/custody-command.json';
        file_put_contents($path,json_encode(['operation'=>'call','arguments'=>['sessionId'=>$this->sid,'attemptId'=>'custody-attempt-0001']]));
        $test=new CommandTester($command); self::assertSame(0,$test->execute(['request-file'=>$path]),$test->getDisplay());
        $record=json_decode($test->getDisplay(),true)['result']; $attempt=$this->retainedAttempt();
        self::assertSame('imperium.citadel-session-call-claim/v2',$record['claim']['schema']);
        self::assertSame('RESPONSE_RETAINED',$attempt['custody']['status']);
        self::assertSame($attempt['settled'],$attempt['custody']['usage']);
        self::assertSame($record['envelope']['record_digest'],$attempt['custody']['response_digest']);
        self::assertSame(J::digest($record['claim']['prepared_operation']),$record['claim']['derivation']['lease']['scope']['prepared_operation_digest']);
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
        $f->clock->at+=7200;
        self::assertSame($record,$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
        self::assertFalse($record['execution_authority']); self::assertStringNotContainsString('generated-only-auth-context',$test->getDisplay());
    }

    public function testPureInspectionAndDefaultAdapterLeaveNoEffectsOrMutation(): void
    {
        $state=$this->f->journal->read()['state']; $s=$state['sessions'][$this->sid];
        $authority=$this->c->container->get(FormationSessionAuthority::class);
        $request=$authority->request($state,$s,$authority->validateSession($state,$s));
        $before=$this->files(); $max=$this->c->transport->inspect($request,$s['terms']);
        self::assertSame($max,$this->c->transport->prepareOperation($request,$s['terms'])['maximum']);
        self::assertSame($before,$this->files()); self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
        foreach ([fn()=>(new UnavailableFormationTransport())->inspect($request,$s['terms']),
            fn()=>(new UnavailableFormationWireAdapter())->prepare($request,$s['terms'])] as $call) { $this->refusal($call); }
        self::assertSame($before,$this->files());
    }

    #[DataProvider('substitutions')]
    public function testForgedAndResealedClaimsNeverEnterCredentials(string $field): void
    {
        [$claim,$request,$terms]=$this->c->pending($this->f,$this->sid);
        switch ($field) {
            case 'session': $claim['session_id']='session-'.str_repeat('a',64); break;
            case 'attempt': $claim['attempt_id']='other-attempt-0001'; break;
            case 'source': $claim['source_decision_digest']=str_repeat('a',64); break;
            case 'holder': $claim['holder']['generation']++; break;
            case 'issuer': $claim['derivation']['lease']['issuer']['generation']++; break;
            case 'lease': $claim['derivation']['lease']['consumed']=false; break;
            case 'authority': $claim['derivation']['authority']['consumed']=false; break;
            case 'consumed': $claim['derived_authority_consumed']=false; break;
            case 'lease-consumed': $claim['lease_consumed']=false; break;
            case 'phase': $request['phase']='drafting'; break;
            case 'body': $request['exchange'][0]['content'].=' substituted'; break;
            case 'wire': $claim['prepared_operation']['wire_bytes_base64']=base64_encode('other wire'); break;
            case 'destination': $terms['destination']='alternate:no-network'; break;
            case 'model': $terms['model']='alternate'; break;
            case 'provider': $terms['provider']='alternate'; break;
            case 'reference': $terms['transport']['credential_reference']='different-reference'; break;
            case 'operation': $terms['transport']['operation']='different-operation'; break;
            case 'adapter': $terms['transport']['adapter']='different-adapter'; break;
            case 'limits': $claim['maximum']['cost_microusd']++; break;
            case 'aggregate': $terms['total']['calls']++; break;
            case 'expiry': $claim['expires_at']++; break;
            case 'v1': $claim['schema']='imperium.citadel-session-call-claim/v1'; unset($claim['prepared_operation']); break;
            case 'digest': $claim['record_digest']=str_repeat('b',64); break;
        }
        if ($field !== 'digest') { unset($claim['record_digest']); $claim['record_digest']=J::digest($claim); }
        $this->refusal(fn()=>$this->c->broker->invoke($claim,$request,$terms));
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
        self::assertArrayNotHasKey('custody',$this->retainedAttempt()); self::assertNull($this->retainedAttempt()['settled']);
    }
    public static function substitutions(): iterable
    {
        foreach (['session','attempt','source','holder','issuer','lease','authority','consumed','lease-consumed','phase','body','wire','destination','model','provider','reference','operation','adapter','limits','aggregate','expiry','v1','digest'] as $f) { yield $f=>[$f]; }
    }

    #[DataProvider('currentness')]
    public function testCurrentAuthorityRequiredAtCustody(string $change): void
    {
        $call=$this->c->pending($this->f,$this->sid); $f=$this->f;
        if ($change==='expired') { $f->clock->at+=2; }
        elseif ($change==='refused' || $change==='deferred') { $this->control(strtoupper($change)); }
        elseif ($change==='superseded') { $f->appoint('replacement'); }
        elseif ($change==='understood') { $f->understand($this->id); }
        elseif ($change==='revoked') {
            $nonce=$f->journal->read()['state']['sessions'][$this->sid]['decision']['payload']['nonce'];
            $f->signatures->revoke($f->sign('REVOKE_DECISION',['nonce'=>$nonce]),$nonce);
        } elseif ($change==='locksmith') {
            $state=$f->journal->read()['state']; $candidate=$f->candidate($state['citadel_id'],'clavium.locksmith','second');
            $terms=['candidate'=>$candidate,'scope'=>$state['citadel_id'],'seat'=>'clavium.locksmith','generation'=>2];
            $f->personnel->appointLocksmith($candidate,$f->sign('APPOINT_FORMATION_LOCKSMITH',$terms));
        }
        $this->refusal(fn()=>$this->c->broker->invoke(...$call));
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
        self::assertNull($this->retainedAttempt()['settled']);
    }
    public static function currentness(): iterable { foreach (['expired','refused','deferred','superseded','understood','revoked','locksmith'] as $f) { yield $f=>[$f]; } }

    public function testCrossRootAuthenticClaimHasNoAuthority(): void
    {
        $call=$this->c->pending($this->f,$this->sid); $other=new CitadelFormationFixture();
        try {
            $c=new FormationCustodyFixture($other->root,$other->clock); $this->refusal(fn()=>$c->broker->invoke(...$call));
            self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$c->counts());
        } finally { $other->close(); }
    }

    public function testSelfSealedResponseBeforeCustodyCannotCreateAdmission(): void
    {
        [$claim]=$this->c->pending($this->f,$this->sid);
        $this->c->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class)
            ->seal($claim,json_encode(CitadelFormationFixture::understanding()),$this->f->clock->now());
        $e=$this->refusal(fn()=>$this->c->cognition->recover($this->sid,'custody-attempt-0001'));
        self::assertSame('FC016_RETAINED_CUSTODY_REQUIRED',$e->getMessage());
        self::assertArrayNotHasKey('admitted',$this->retainedAttempt());
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
    }

    public function testDispatchFenceAloneCannotAuthenticateAUnvalidatedResponse(): void
    {
        $this->c->wire->afterDispatch=static fn()=>throw new \RuntimeException('Synthetic missing actual result');
        $this->refusal(fn()=>$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        $attempt=$this->retainedAttempt(); self::assertSame('DISPATCH_COMMITTED_OUTCOME_UNCERTAIN',$attempt['custody']['status']);
        $this->c->container->get(\App\Imperium\Runtime\Clavium\ProviderResponseEnvelopeService::class)
            ->seal($attempt['claim'],json_encode(CitadelFormationFixture::understanding()),$this->f->clock->now());
        $e=$this->refusal(fn()=>$this->c->cognition->recover($this->sid,'custody-attempt-0001'));
        self::assertSame('FC016_RETAINED_CUSTODY_REQUIRED',$e->getMessage());
        self::assertArrayNotHasKey('admitted',$this->retainedAttempt()); self::assertNull($this->retainedAttempt()['settled']);
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }

    public function testChangedWireBetweenInspectionAndCustodyRefusesBeforeIssue(): void
    {
        $call=$this->c->pending($this->f,$this->sid); $this->c->wire->suffix='alternate';
        $this->refusal(fn()=>$this->c->broker->invoke(...$call));
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
    }

    public function testActualServiceResourceKeepsDefaultAliasesAndCredentialsDormant(): void
    {
        $container=new \Symfony\Component\DependencyInjection\ContainerBuilder();
        $loader=new \Symfony\Component\DependencyInjection\Loader\YamlFileLoader($container,
            new \Symfony\Component\Config\FileLocator(dirname(__DIR__,3).'/config'));
        $loader->load('services.yaml');
        $boundary=\App\Imperium\Runtime\Citadel\Formation\BoundedFormationTransport::class;
        $wire=\App\Imperium\Runtime\Citadel\Formation\FormationWireAdapter::class;
        self::assertSame(UnavailableFormationTransport::class,(string)$container->getAlias($boundary));
        self::assertSame(UnavailableFormationWireAdapter::class,(string)$container->getAlias($wire));
        $before=$this->files();
        $this->refusal(fn()=>$container->get($boundary)->inspect([],[]));
        $this->refusal(fn()=>$container->get($wire)->prepare([],[]));
        $this->refusal(fn()=>$container->get($wire)->dispatch([],null));
        self::assertSame($before,$this->files()); self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
        self::assertSame([],$this->f->journal->read()['state']['sessions'][$this->sid]['attempts']);
    }

    public function testPublicPreparationBindsTransportWithoutGrantingCustody(): void
    {
        $f=$this->f; $state=$f->journal->read()['state']; $s=$state['sessions'][$this->sid]; $before=$this->files();
        $packet=(new \App\Imperium\Runtime\Citadel\Formation\FormationPreparation($f->clock))->prepare([
            'schema'=>'imperium.citadel-preparation-request/v1','citadel_id'=>$state['citadel_id'],
            'trust_fingerprint'=>$state['trust']['fingerprint'],'effect'=>'AUTHORIZE_INTERVIEW_SESSION','object'=>$s['terms'],
            'expires_at'=>$f->clock->at+600,'source_identity'=>['commit'=>str_repeat('a',40),'tree'=>str_repeat('b',40),'public_export_digest'=>J::digest($state)]]);
        self::assertSame(FormationCustodyFixture::authorization(),$packet['object']['transport']);
        self::assertFalse($packet['activation']); self::assertSame($before,$this->files());
        self::assertSame(['issue'=>0,'consume'=>0,'dispatch'=>0],$this->c->counts());
        $decision=$f->signPrepared($packet);
        $sid=$f->cognition->grant($this->id,'interview',$packet['object'],$decision);
        self::assertNotSame($sid,$this->sid);
    }

    #[DataProvider('lateChanges')]
    public function testLateChangeStopsConsumeOrDispatchWithoutUnlockingReplay(string $point,string $change): void
    {
        $call=$this->c->pending($this->f,$this->sid);
        $hook=function() use($change): void { if ($change==='wire') { $this->c->wire->suffix='changed'; } else { $this->control('REFUSED'); } };
        if ($point==='issue') { $this->c->credentials->onIssue=$hook; } else { $this->c->credentials->onConsume=$hook; }
        $this->refusal(fn()=>$this->c->broker->invoke(...$call));
        $this->refusal(fn()=>$this->c->broker->invoke(...$call));
        self::assertSame(['issue'=>1,'consume'=>$point==='consume'?1:0,'dispatch'=>0],$this->c->counts());
        self::assertNull($this->retainedAttempt()['settled']);
    }
    public static function lateChanges(): iterable { yield ['issue','refusal']; yield ['consume','refusal']; yield ['consume','wire']; }

    #[DataProvider('badResults')]
    public function testUntrustedResultRetainsFullExposureAndNeverRetries(string $bad): void
    {
        $this->c->wire->changeResult=static function(array $result) use($bad): array {
            if ($bad==='absent') { unset($result['usage']); }
            elseif ($bad==='excess') { $result['usage']['cost_microusd']=1000000000; }
            elseif ($bad==='float') { $result['usage']['milliseconds']=1.0; }
            elseif ($bad==='order') { $result['usage']=array_reverse($result['usage'],true); }
            elseif ($bad==='calls') { $result['usage']['calls']=0; }
            elseif ($bad==='identity') { $result['operation_digest']=str_repeat('a',64); }
            elseif ($bad==='provenance') { $result['provenance']='unverified-provider'; }
            elseif ($bad==='secret') { $result['response']='generated-only-auth-context'; }
            return $result;
        };
        $this->refusal(fn()=>$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        self::assertNull($this->retainedAttempt()['settled']);
        $this->refusal(fn()=>$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }
    public static function badResults(): iterable { foreach (['absent','excess','float','order','calls','identity','provenance','secret'] as $f) { yield $f=>[$f]; } }

    public function testReplayedInfrastructureCallbackCannotDispatchTwiceAndRetainedEnvelopeRecovers(): void
    {
        $this->c->credentials->repeatCallback=true;
        $this->refusal(fn()=>$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        self::assertNull($this->retainedAttempt()['settled']);
        $result=$this->c->cognition->recover($this->sid,'custody-attempt-0001');
        self::assertSame('UNDERSTOOD',$result['response']['disposition']);
        self::assertNull($this->retainedAttempt()['settled']); // absent settlement remains full reservation
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }

    public function testSecretBearingExceptionIsNotChainedOrReturned(): void
    {
        $this->c->wire->afterDispatch=static fn()=>throw new \RuntimeException('generated-only-auth-context');
        $e=$this->refusal(fn()=>$this->c->cognition->call($this->sid,'custody-attempt-0001'));
        self::assertSame('CMF059_OUTCOME_UNKNOWN_NO_RETRY',$e->getMessage());
        self::assertStringNotContainsString('generated-only-auth-context',json_encode($this->f->journal->read()));
    }

    public function testSeparateDraftingAndReceivingAuthoritiesUseCustodyWithoutExecution(): void
    {
        $f=$this->f; $c=$this->c;
        $c->cognition->call($this->sid,'custody-attempt-0001');
        $c->cognition->draftingRequest($this->id,['scope'=>'Synthetic present-material draft','questions'=>'One bounded plan',
            'inputs'=>'Synthetic interview','offices'=>[],'external_effects'=>[],'disclosure'=>'Offline fixture only',
            'expected_return'=>'FormationPlan','stop_conditions'=>'Scope changes','amendment_triggers'=>'New scope',
            'retention'=>'Retain exact versions','expires_at'=>$f->clock->at+600]);
        $terms=$f->terms($this->id,'drafting'); $terms['transport']=FormationCustodyFixture::authorization();
        $draft=$f->grant($this->id,'drafting',$terms); $c->wire->response=$f::plan();
        $record=$c->cognition->call($draft,'custody-drafting-0001');
        self::assertTrue($record['claim']['derivation']['authority']['planning_only']);
        self::assertNotNull($record['claim']['derivation']['authority']['planning_authorization']);
        $review=$f->approve($f->present($this->id)); $f->run('reserve-mission',['reviewId'=>$review['review_id']]);
        $f->run('deliver-handoff',['intakeId'=>$this->id]);
        $terms=$f->terms($this->id,'acceptance'); $terms['transport']=FormationCustodyFixture::authorization();
        // Full handoff includes prior byte-bound claims; disclose a larger synthetic
        // input ceiling before signing instead of weakening operation validation.
        $terms['per_call']['input_tokens']=8000000; $terms['per_call']['cost_microusd']=8100000;
        $terms['total']['input_tokens']=40000000; $terms['total']['cost_microusd']=40500000;
        $accept=$f->grant($this->id,'acceptance',$terms);
        $c->wire->response=['disposition'=>'ACCEPTED','rationale'=>'Synthetic exact handoff acknowledged','gaps'=>'','dissent'=>'No execution authorization'];
        $record=$c->cognition->call($accept,'custody-acceptance-0001');
        self::assertSame('curia.seneschal',$record['claim']['holder']['seat']); self::assertFalse($record['execution_authority']);
        self::assertSame(['issue'=>3,'consume'=>3,'dispatch'=>3],$c->counts());
        self::assertSame($record,$c->cognition->recover($accept,'custody-acceptance-0001'));
        self::assertSame(['issue'=>3,'consume'=>3,'dispatch'=>3],$c->counts());
    }

    public function testUnknownOutcomeDoesNotRefundAggregateBudgetForAnotherAttempt(): void
    {
        $terms=$this->f->terms($this->id,'interview'); $terms['transport']=FormationCustodyFixture::authorization(); $terms['total']=$terms['per_call'];
        $sid=$this->f->grant($this->id,'interview',$terms);
        $this->c->wire->afterDispatch=static fn()=>throw new \RuntimeException('Synthetic unknown effect');
        $this->refusal(fn()=>$this->c->cognition->call($sid,'unknown-custody-0001'));
        $e=$this->refusal(fn()=>$this->c->cognition->call($sid,'unknown-custody-0002'));
        self::assertSame('CMF032_SESSION_EXHAUSTED',$e->getMessage());
        self::assertCount(1,$this->f->journal->read()['state']['sessions'][$sid]['attempts']);
        self::assertSame(['issue'=>1,'consume'=>1,'dispatch'=>1],$this->c->counts());
    }

    private function control(string $disposition): void
    {
        $this->f->cognition->control($this->sid,$disposition,$this->f->sign('CONTROL_FORMATION_SESSION',['session_id'=>$this->sid,'disposition'=>$disposition]));
    }
    private function files(): array
    {
        $files=[]; foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->f->root,\FilesystemIterator::SKIP_DOTS)) as $file) {
            if($file->isFile()) { $files[$file->getPathname()]=hash_file('sha256',$file->getPathname()); }
        } ksort($files); return $files;
    }
}
