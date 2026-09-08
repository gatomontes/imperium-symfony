<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Citadel\NativeAuthority\{NativeJournal, NativeTrust};
use App\Imperium\Runtime\Garrison\{SubordinatePersonaCanonicalAdmissionService, GarrisonInventoryResponseService, SubordinatePersonaAdmissionIntakeService, ConstableSeatBindingService};
use App\Tests\Imperium\Runtime\Support\{NativeAuthorityFixture as F, CitadelAuthorityFixture as Old, CitadelAuthorityProcess as Process};
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NativeAuthorityProtocolTest extends TestCase
{
    private F $f;
    protected function setUp(): void { $this->f = new F(); }
    protected function tearDown(): void { $this->f->close(); }
    public function testPositiveCommandDiConsumerAndRetainedReplayAfterExpiryAndRevocation(): void
    {
        self::assertSame(1, $this->f->command('resolve', ['seat' => 'garrison.constable'])[0]);
        $private = file_get_contents($this->f->root.'/var/imperium/bootstrap-state.json');
        $original = file_get_contents($this->occupancyPath()); $signed = $this->f->ready();
        $p = $this->f->protocol(); $resolved = $p->resolve('conscription.recruiter');
        self::assertFalse($resolved['roster']['historical_producer_authenticated']); self::assertTrue($resolved['current_at_locked_observation_only']);
        $request = $this->f->revision();
        [$exit, $unsigned] = $this->f->command('prepare', ['effect' => 'REVISE_GARRISON', 'object' => $request,
            'issued_at' => $this->f->base->now, 'expires_at' => $this->f->base->now+50, 'nonce' => str_repeat('a',48)]);
        self::assertSame(2,$exit); self::assertNull($unsigned['signature']); self::assertArrayNotHasKey('object',$unsigned);
        [$exit,$assembled] = $this->f->command('assemble',['object'=>$request,'payload'=>$unsigned['payload'],'signature'=>$this->f->signature($unsigned['payload'])]);
        self::assertSame(2,$exit); self::assertSame(A::digest($request),$assembled['object_digest']);
        $admission = (new SubordinatePersonaCanonicalAdmissionService($this->f->root,$p))->admit(F::DELIVERY,Old::occupancy()['binding_id']);
        self::assertSame('ADMITTED',$admission['disposition']); self::assertTrue($admission['custody_created']);
        self::assertSame(Old::occupancy()['record_digest'],$admission['constable']['binding_digest']);
        $inventory = (new GarrisonInventoryResponseService($this->f->root,$p))->respond(F::INQUIRY);
        self::assertTrue($inventory['authoritative_inventory_response']); self::assertCount(1,$inventory['inventory_records']);
        self::assertFalse($inventory['execution_authority']); self::assertSame($admission['custody_digest'],$inventory['inventory_records'][0]['record_digest']);
        $revoke=$this->f->sign('REVOKE_DECISION',['expected_head'=>$p->snapshot()['registry_head'],'target'=>$signed['decision']['payload']['nonce']]);
        self::assertSame(0,$this->f->command('apply',$revoke)[0]); $generation=$p->snapshot()['frame_generation'];
        $this->f->base->now+=8000;
        self::assertSame($admission,$p->admit(F::DELIVERY,Old::occupancy()['binding_id']));
        self::assertSame(0,$this->f->command('apply',$signed)[0]); self::assertSame($generation,$p->snapshot()['frame_generation']);
        self::assertSame(1,$this->f->command('resolve',['seat'=>'garrison.constable'])[0]);
        self::assertSame($private,file_get_contents($this->f->root.'/var/imperium/bootstrap-state.json'));
        self::assertSame($original,file_get_contents($this->occupancyPath())); self::assertCount(1,glob(dirname($this->occupancyPath()).'/*.json'));
        self::assertDirectoryDoesNotExist($this->f->root.'/var/imperium/offices/garrison/custody');
        self::assertStringNotContainsString(Old::SECRET,json_encode($this->f->outputs));
    }
    public static function badSignatures(): iterable
    {
        foreach(['domain','issuer_role','trust_fingerprint','instance_id','effect','object_digest','wrong_key','expired','future','self_enroll'] as $v) yield $v=>[$v];
    }
    #[DataProvider('badSignatures')]
    public function testCryptographicAndCompetenceAdversaries(string $variant): void
    {
        $this->f->ready(); $i=$this->f->sign('REVISE_GARRISON',$this->f->revision());
        $payload=&$i['decision']['payload'];
        if($variant==='expired')$payload['expires_at']=$this->f->base->now;
        elseif($variant==='future')$payload['issued_at']=$this->f->base->now+100;
        elseif($variant==='self_enroll')$payload['public_key']=$this->f->policy['public_key'];
        elseif($variant!=='wrong_key')$payload[$variant]='wrong-'.Old::SECRET;
        $i['decision']['signature']=$this->f->signature($payload);
        if($variant==='wrong_key') { $key=sodium_crypto_sign_keypair(); $i['decision']['signature']=base64_encode(sodium_crypto_sign_detached(\App\Bootstrap\CanonicalJson::encode($payload),sodium_crypto_sign_secretkey($key))); sodium_memzero($key); }
        $before=$this->f->protocol()->snapshot(); [$exit,$r,$text]=$this->f->command('apply',$i);
        self::assertSame(1,$exit);self::assertFalse($r['execution_authority']);self::assertStringNotContainsString(Old::SECRET,$text);
        self::assertSame($before,$this->f->protocol()->snapshot());
    }
    public static function badRevision(): iterable
    {
        foreach(['head','roster','prior','actor','generation','instance','scope','modified_original','expired_request','revoked_nonce'] as $v)yield $v=>[$v];
    }
    #[DataProvider('badRevision')]
    public function testExactRevisionBindingsAndNoEffect(string $variant): void
    {
        $this->f->ready();$o=$this->f->revision();
        if($variant==='head')$o['expected_head']=str_repeat('0',64);
        if($variant==='roster')$o['roster_digest']=str_repeat('0',64);
        if($variant==='prior')$o['prior_revision']=null;
        if(in_array($variant,['actor','generation','instance','scope','expired_request'],true)) {
            $r=$o['request'];unset($r['record_digest']);
            if($variant==='actor')$r['terms']['manifestation_id']='wrong-actor';
            if($variant==='generation')$r['terms']['occupancy_generation']++;
            if($variant==='instance')$r['terms']['instance_id']='wrong-instance';
            if($variant==='scope')$r['terms']['requested_extension']['execution_authority']='any';
            if($variant==='expired_request') { $input=$this->f->base->requestInput();$input['effective_at']=$this->f->base->now-60;$input['expires_at']=$this->f->base->now;
                $input['prior_revision']=$o['request']['terms']['prior_revision'];$r=$this->f->base->garrison()->prepare($input);unset($r['record_digest']); }
            $o['request']=A::seal($r);
        }
        if($variant==='modified_original') { $original=$o['occupancy'];unset($original['record_digest']);$original['inventory_response_authority']=false;$o['occupancy']=A::seal($original); }
        $nonce=bin2hex(random_bytes(24));
        if($variant==='revoked_nonce') { $this->f->protocol()->apply($this->f->sign('REVOKE_DECISION',['expected_head'=>$o['expected_head'],'target'=>$nonce]));$o['expected_head']=$this->f->protocol()->snapshot()['registry_head']; }
        $i=$this->f->sign('REVISE_GARRISON',$o,$nonce);$before=$this->f->protocol()->snapshot();
        self::assertSame(1,$this->f->command('apply',$i)[0]);self::assertSame($before,$this->f->protocol()->snapshot());
    }
    public function testExplicitAdoptionSupersessionRetirementAndChangedEvidence(): void
    {
        self::assertSame(0,$this->f->enroll()[0]); $o=$this->f->adoption('conscription.recruiter');
        $bad=$o;$bad['actor']='unrelated';self::assertSame(1,$this->f->command('apply',$this->f->sign('ADOPT_ROSTER',$bad))[0]);
        self::assertSame(0,$this->f->command('apply',$this->f->sign('ADOPT_ROSTER',$o))[0]);
        $o['expected_head']=$this->f->protocol()->snapshot()['registry_head'];
        self::assertSame(1,$this->f->command('apply',$this->f->sign('ADOPT_ROSTER',$o))[0]);
        $prior=$this->f->protocol()->resolve('conscription.recruiter')['roster'];
        $o['prior_roster']=$prior['record_digest'];$o['actor']='synthetic-successor';$o['occupancy_generation']=3;
        $o['evidence']=['source_id'=>'synthetic-owner-succession-case','source_digest'=>str_repeat('1',64)];
        self::assertSame(0,$this->f->command('apply',$this->f->sign('SUPERSEDE_RECRUITER',$o))[0]);
        $current=$this->f->protocol()->resolve('conscription.recruiter')['roster'];self::assertSame(3,$current['occupancy_generation']);
        self::assertFalse($current['historical_producer_authenticated']);
        $o['expected_head']=$this->f->protocol()->snapshot()['registry_head'];$o['prior_roster']=$current['record_digest'];$o['evidence']=null;
        self::assertSame(0,$this->f->command('apply',$this->f->sign('RETIRE_ROSTER',$o))[0]);
        self::assertSame(1,$this->f->command('resolve',['seat'=>'conscription.recruiter'])[0]);
    }
    public function testIndependentCompleteLegacyAdmissionControlsForEachPowerAndRepresentation(): void
    {
        // Fresh root per control so no earlier effect can satisfy a later assertion.
        foreach(['valid','admission_missing','custody_missing','request_as_occupancy'] as $variant) {
            $f=new F();try {
                $o=Old::occupancy();unset($o['record_digest']);$o['persona_admission_disposition_authority']=true;$o['custody_registration_authority']=true;
                if($variant==='admission_missing')unset($o['persona_admission_disposition_authority']);
                if($variant==='custody_missing')unset($o['custody_registration_authority']);
                $o=A::seal($o);if($variant==='request_as_occupancy')$o=$f->base->garrison()->prepare($f->base->requestInput());
                $f->write('var/imperium/offices/garrison/occupancy/'.Old::occupancy()['binding_id'].'.json',$o);
                $consumer=new SubordinatePersonaCanonicalAdmissionService($f->root);
                if($variant==='valid') { self::assertSame('ADMITTED',$consumer->admit(F::DELIVERY,Old::occupancy()['binding_id'])['disposition']);
                    self::assertCount(1,glob($f->root.'/var/imperium/offices/garrison/custody/*.json')); }
                else { try{$consumer->admit(F::DELIVERY,Old::occupancy()['binding_id']);self::fail('Invalid control admitted');}
                    catch(\RuntimeException $e){self::assertSame('GA87_CANONICAL_ADMISSION_CHAIN_INVALID',$e->getMessage());}
                    self::assertDirectoryDoesNotExist($f->root.'/var/imperium/offices/garrison/custody'); }
            }finally{$f->close();}
        }
    }
    public function testEnrolledDefaultsFenceLegacyReadersWritersAndTamperedOriginal(): void
    {
        $this->f->ready();
        foreach([fn()=>(new StateStore($this->f->root))->read(),fn()=>(new StateStore($this->f->root))->write(Old::state()),
            fn()=>(new SubordinatePersonaAdmissionIntakeService($this->f->root))->inspect('unused')] as $operation) {
            try{$operation();self::fail('Legacy bypass');}catch(\RuntimeException $e){self::assertSame('NAT002_LEGACY_WRITER_OR_READER_FENCED',$e->getMessage());}
        }
        $o=Old::occupancy();unset($o['record_digest']);$o['inventory_response_authority']=false;
        $this->f->write('var/imperium/offices/garrison/occupancy/'.Old::occupancy()['binding_id'].'.json',A::seal($o));
        self::assertSame(1,$this->f->command('inventory',['inquiry_id'=>F::INQUIRY])[0]);
        self::assertSame(1,$this->f->command('admit',['delivery_id'=>F::DELIVERY,'binding_id'=>Old::occupancy()['binding_id']])[0]);
    }
    public function testEnrollmentRequiresSeparateFingerprintAndCannotRepeatOrSelfEnroll(): void
    {
        $trust=new NativeTrust(new NativeJournal($this->f->root),$this->f->base);
        try{$trust->enroll($this->f->policy,str_repeat('0',64));self::fail('Wrong fingerprint');}catch(\RuntimeException $e){self::assertSame('NAT010_ENROLLMENT_INVALID',$e->getMessage());}
        self::assertSame(1,$this->f->command('enroll-from-decision',$this->f->policy)[0]);
        self::assertSame(0,$this->f->enroll()[0]);self::assertSame(1,$this->f->enroll()[0]);
        $i=$this->f->sign('REVOKE_ISSUER',['expected_head'=>null,'target'=>$this->f->fingerprint()]);
        self::assertSame(0,$this->f->command('apply',$i)[0]);
        self::assertSame(1,$this->f->command('apply',$this->f->sign('ADOPT_ROSTER',$this->f->adoption('conscription.recruiter')))[0]);
    }
    public function testConflictingReplayAndBrokenJournalDoNotRepairThemselves(): void
    {
        $signed=$this->f->ready();$changed=$signed;$changed['object']['expected_head']=null;
        self::assertSame(1,$this->f->command('apply',$changed)[0]);
        $files=glob($this->f->root.'/var/imperium/native-authority/*.json');$frame=A::read($files[1]);
        $frame['previous_digest']=str_repeat('0',64);unset($frame['record_digest']);file_put_contents($files[1],json_encode(A::seal($frame)));
        self::assertSame(1,$this->f->command('snapshot',[])[0]);
    }
    private function occupancyPath(): string { return $this->f->root.'/var/imperium/offices/garrison/occupancy/'.Old::occupancy()['binding_id'].'.json'; }
    public function testSeparateProcessCompetingRevisionsAndRevocationHaveOneWinner(): void
    {
        foreach(['revision','revoke'] as $secondAction) {
            $f=new F();try {
                $f->ready();$o=$f->revision();$first=$f->sign('REVISE_GARRISON',$o);
                $second=$secondAction==='revision'?$f->sign('REVISE_GARRISON',$o):$f->sign('REVOKE_DECISION',['expected_head'=>$o['expected_head'],'target'=>$first['decision']['payload']['nonce']]);
                $generation=$f->protocol()->snapshot()['frame_generation'];
                $a=$this->worker($f,'first','apply',$second,'before-commit');$b=$this->worker($f,'second','apply',$first,null);
                try {
                    $a->start();$this->marker($f,'first-paused',$a);$b->start();$this->marker($f,'second-started',$b);usleep(120000);
                    self::assertTrue($b->isRunning());self::assertSame('',$b->getOutput());
                    file_put_contents($f->root.'/first-release','synthetic');self::assertSame(0,$a->wait());self::assertSame(1,$b->wait());
                    self::assertStringContainsString($secondAction==='revoke'?'NAT013_':'NAT023_',$b->getOutput());
                    self::assertSame($generation+1,$f->protocol()->snapshot()['frame_generation']);
                }finally{$a->stop(0);$b->stop(0);}
            }finally{$f->close();}
        }
    }
    public function testSeparateProcessRetirementFencesConsumerAtCurrentnessBoundary(): void
    {
        $this->f->ready();$snap=$this->f->protocol()->snapshot();$r=$snap['roster']['garrison.constable'];
        $retire=$this->f->sign('RETIRE_ROSTER',['expected_head'=>$snap['registry_head'],'seat'=>$r['seat'],'actor'=>$r['actor'],
            'occupancy_generation'=>$r['occupancy_generation'],'prior_roster'=>$r['record_digest'],'evidence'=>null,
            'effective_at'=>$this->f->base->now,'expires_at'=>$this->f->base->now+60]);
        $writer=$this->worker($this->f,'first','apply',$retire,'before-commit');
        $reader=$this->worker($this->f,'second','admit',['delivery_id'=>F::DELIVERY,'binding_id'=>Old::occupancy()['binding_id']],null);
        try {
            $writer->start();$this->marker($this->f,'first-paused',$writer);$reader->start();$this->marker($this->f,'second-started',$reader);
            usleep(120000);self::assertTrue($reader->isRunning());file_put_contents($this->f->root.'/first-release','synthetic');
            self::assertSame(0,$writer->wait());self::assertSame(1,$reader->wait());self::assertStringContainsString('NAT032_',$reader->getOutput());
            self::assertSame([], (new NativeJournal($this->f->root))->inspect(fn($f)=>$f['state']['admissions']));
        }finally{$writer->stop(0);$reader->stop(0);}
    }
    public static function interruptions(): iterable
    {
        foreach(['before-commit','pending-durable','after-commit'] as $stage)foreach(['apply','admit'] as $operation)yield "$operation/$stage"=>[$operation,$stage];
    }
    #[DataProvider('interruptions')]
    public function testSeparateProcessInterruptionAndExactRecovery(string $operation,string $stage): void
    {
        $this->f->ready();$i=$operation==='apply'?$this->f->sign('REVISE_GARRISON',$this->f->revision()):['delivery_id'=>F::DELIVERY,'binding_id'=>Old::occupancy()['binding_id']];
        $before=$this->f->protocol()->snapshot()['frame_generation'];$worker=$this->worker($this->f,'first',$operation,$i,$stage);
        try{$worker->start();$this->marker($this->f,'first-paused',$worker);$worker->stop(0);}finally{$worker->stop(0);}
        if($stage==='after-commit')$this->f->base->now+=8000;
        [$exit,$result,$text]=$this->f->command($operation,$i);self::assertStringNotContainsString(Old::SECRET,$text);
        if($stage==='pending-durable') {self::assertSame(1,$exit);self::assertSame('NAT005_UNKNOWN_OUTCOME_FENCED',$result['code']);self::assertCount(1,glob($this->f->root.'/var/imperium/native-authority/*.pending'));}
        else {self::assertSame(0,$exit);self::assertSame($before+1,$this->f->protocol()->snapshot()['frame_generation']);
            self::assertSame($result,$this->f->command($operation,$i)[1]);self::assertSame($before+1,$this->f->protocol()->snapshot()['frame_generation']);}
    }
    public function testRejectedSecretInputsAndPublicProofNeverIncludePrivateBackingState(): void
    {
        $this->f->ready();$o=$this->f->revision();$o['private_key']=Old::SECRET;
        [$exit,$r,$text]=$this->f->command('prepare',['effect'=>'REVISE_GARRISON','object'=>$o,'issued_at'=>$this->f->base->now,'expires_at'=>$this->f->base->now+60,'nonce'=>str_repeat('f',48)]);
        self::assertSame(1,$exit);self::assertStringNotContainsString(Old::SECRET,$text);
        foreach(glob($this->f->root.'/var/imperium/native-authority/*') as $path)self::assertStringNotContainsString(Old::SECRET,file_get_contents($path));
        self::assertStringContainsString(Old::SECRET,file_get_contents($this->f->root.'/var/imperium/bootstrap-state.json'));
    }
    private function worker(F $f,string $mode,string $operation,array $arguments,?string $pause): Process
    {
        $f->write('worker-'.$mode.'.json',['now'=>$f->base->now,'operation'=>$operation,'arguments'=>$arguments,'pause'=>$pause]);
        return new Process([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',
            __DIR__.'/Support/native-authority-worker.php',$mode,$f->root]);
    }
    public function testEnrollmentWaitsForLegacyWriterAndPermanentlyFencesIt(): void
    {
        $writer=$this->worker($this->f,'first','legacy',[],'legacy-locked');
        $enroll=$this->worker($this->f,'second','enroll',['policy'=>$this->f->policy,'fingerprint'=>$this->f->fingerprint()],null);
        try{
            $writer->start();$this->marker($this->f,'first-paused',$writer);$enroll->start();$this->marker($this->f,'second-started',$enroll);
            usleep(120000);self::assertTrue($enroll->isRunning());file_put_contents($this->f->root.'/first-release','synthetic');
            self::assertSame(0,$writer->wait());self::assertSame(0,$enroll->wait());
            try{(new StateStore($this->f->root))->write(Old::state());self::fail('Post-enrollment old writer');}
            catch(\RuntimeException $e){self::assertSame('NAT002_LEGACY_WRITER_OR_READER_FENCED',$e->getMessage());}
            // Historical projection remains explicitly structural even after enrollment.
            self::assertFalse($this->f->base->recruiter()->export('synthetic-authority-test')['authenticated_provenance']);
        }finally{$writer->stop(0);$enroll->stop(0);}
    }
    public function testRevokedRevisionCannotAdmitFreshDeliveryAndRequestNonceCannotBeReused(): void
    {
        $signed=$this->f->ready();$p=$this->f->protocol();$o=$this->f->revision();
        $r=$o['request'];unset($r['record_digest']);$r['terms']['request_nonce']=$signed['object']['request']['terms']['request_nonce'];
        $r['request_id']='garrison-authority-request-'.A::digest($r['terms']);$o['request']=A::seal($r);
        [$exit,$error]=$this->f->command('apply',$this->f->sign('REVISE_GARRISON',$o));self::assertSame(1,$exit);self::assertSame('NAT043_REQUEST_REPLAY_CONFLICT',$error['code']);
        $revoke=$this->f->sign('REVOKE_DECISION',['expected_head'=>$p->snapshot()['registry_head'],'target'=>$signed['decision']['payload']['nonce']]);
        self::assertSame(0,$this->f->command('apply',$revoke)[0]);
        [$exit,$error]=$this->f->command('admit',['delivery_id'=>F::DELIVERY,'binding_id'=>Old::occupancy()['binding_id']]);
        self::assertSame(1,$exit);self::assertSame('NAT036_EFFECTIVE_ADMISSION_AUTHORITY_REQUIRED',$error['code']);
        self::assertSame([], (new NativeJournal($this->f->root))->inspect(fn($f)=>$f['state']['admissions']));
        // Revoking admission extension leaves the independently observed inventory power intact.
        self::assertSame(0,$this->f->command('inventory',['inquiry_id'=>F::INQUIRY])[0]);
    }
    public function testPolicyCannotBorrowAnotherDomainOrBroadenItsEffects(): void
    {
        foreach(['domain','issuer_role','effects','instance_id','writer_boundary'] as $field) {
            $policy=$this->f->policy;$policy[$field]=$field==='effects'?['EXECUTE_MISSION']:null;
            self::assertSame(1,$this->f->command('enroll',$policy)[0]);
            self::assertDirectoryDoesNotExist($this->f->root.'/var/imperium/native-authority');
        }
    }
    private function marker(F $f,string $name,Process $process): void
    {
        $until=microtime(true)+8;
        while(!is_file($f->root.'/'.$name)) {if(!$process->isRunning()||microtime(true)>$until)self::fail('Synthetic process marker absent: '.$process->getOutput().$process->getErrorOutput());usleep(10000);}
    }
}
