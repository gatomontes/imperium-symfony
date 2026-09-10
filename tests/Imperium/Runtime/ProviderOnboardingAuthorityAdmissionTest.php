<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules,StrictJson,Enrollment,Admission,Resolver,Policy};
use App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture as F;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ProviderOnboardingAuthorityAdmissionTest extends TestCase
{
    private F $f;
    protected function setUp():void { $this->f=new F(); }
    protected function tearDown():void { $this->f->close(); }
    private function refuses(callable $call,?string $code=null):void {
        $head=$this->f->head();
        try { $call(); self::fail('Expected refusal'); }
        catch (\RuntimeException|\InvalidArgumentException $error) { if ($code!==null) { self::assertStringContainsString($code,$error->getMessage()); } }
        self::assertSame($head,$this->f->head(),'Refusal must not publish a frame');
    }
    public function testActualCryptographicProducerToConsumerAndRestart():void {
        $this->f->enroll(); [$h,$e,$result]=$this->f->admitPolicy();
        self::assertSame('ORIGINAL_ADMITTED',$result['status']); self::assertFalse($result['effect_completed']);
        $resolver=new Resolver($this->f->store);
        self::assertSame(F::json($h),F::json($resolver->original(Rules::reference($h))));
        $observation=$resolver->current(F::authority($result),'AUTHORIZE_BOOTSTRAP_POLICY',Rules::reference($h));
        self::assertSame('CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION',$observation['status']); self::assertFalse($observation['execution_authority']);
        $reopened=new \App\Imperium\Runtime\Onboarding\AuthorityAdmission\AuthorityStore($this->f->root,$this->f->store->clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
        self::assertSame(F::json($h),F::json((new Resolver($reopened))->original(Rules::reference($h))));
        $before=$this->f->head(); $this->f->now+=2000;
        $repeat=(new Admission($reopened))->retain(F::json($e),F::json($h),array_map(F::json(...),array_values($this->f->sources)));
        self::assertSame('HISTORICAL_RECOGNITION',$repeat['status']); self::assertFalse($repeat['current_authority']); self::assertSame($before,$this->f->head());
        $this->refuses(fn()=>(new Resolver($reopened))->original(Rules::reference($h)),'ACT_TIME');
    }
    public function testNoTrustLearningAndNoProjectionAdmission():void {
        $h=$this->f->policy(); $e=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY');
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h)),'TRUST_ABSENT');
        $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)),'TRUST_ABSENT');
        self::assertArrayNotHasKey('onboarding',$this->f->store->journal->read()['state']);
        $this->f->enroll(); $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json(['status'=>'PROPOSED_BASE']),F::json($h)));
    }
    #[DataProvider('badJson')]
    public function testStrictJson(string $raw):void { $this->expectException(\InvalidArgumentException::class); StrictJson::decode($raw); }
    public static function badJson():iterable {
        foreach (['{"x":1,"x":2}','{"x":1,"\\u0078":2}','{"x":1.0}','{"x":1e0}','{"x":-1}','{"x":01}','{"x":9223372036854775808}','{"0":"a"}','{}',"\xEF\xBB\xBF[]",'[]x','[truefalse]',str_repeat('[',33).'0'.str_repeat(']',33),"[\"\xFF\"]",'{"x":NaN}'] as $i=>$s) { yield 'invalid-'.$i=>[$s]; }
        yield 'byte limit'=>[str_repeat(' ',1048577)];
    }
    public function testStrictScalarsAndIntegerRange():void { self::assertSame([0,PHP_INT_MAX,true,false,null,'a'],StrictJson::decode('[0,9223372036854775807,true,false,null,"a"]')); }
    #[DataProvider('badActs')]
    public function testSignedAdversarialFields(array $path,mixed $value,bool $resign):void {
        $this->f->enroll(); $h=$this->f->policy(); $e=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY');
        $cursor=&$e; foreach ($path as $part) { $cursor=&$cursor[$part]; } $cursor=$value; unset($cursor);
        if ($resign) { $e=$this->f->resign($e); }
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),array_map(F::json(...),array_values($this->f->sources))));
    }
    public static function badActs():iterable {
        foreach (['schema'=>'imperium.citadel-owner-decision/v1','domain'=>'IMPERIUM_NATIVE_INSTITUTIONAL_V1','instance_id'=>'instance-foreign','citadel_id'=>'citadel-foreign','trust_fingerprint'=>'sha256:'.str_repeat('b',64),'effect'=>'QUALIFY_BIND_AUGUR','object_digest'=>'sha256:'.str_repeat('b',64),'issued_at'=>1800000001,'expires_at'=>1800000000,'nonce'=>'../../escape','unexpected'=>true] as $k=>$v) { yield $k=>[['payload',$k],$v,true]; }
        yield 'numeric time'=>[['payload','issued_at'],'1800000000',true];
        yield 'milliseconds'=>[['payload','issued_at'],1800000000000,true];
        yield 'float time'=>[['payload','issued_at'],1.5,true];
        yield 'bool time'=>[['payload','issued_at'],true,true];
        yield 'institution'=>[['payload','issuer'],['kind'=>'institution','seat'=>'conscription.recruiter','binding_ref'=>[],'generation'=>1],true];
        yield 'foreign operator'=>[['payload','issuer','id'],'operator-foreign',true];
        yield 'policy derived'=>[['payload','policy_ref'],['schema'=>'imperium.operator-bootstrap-policy/v1','id'=>'policy-self','digest'=>'sha256:'.str_repeat('a',64)],true];
        yield 'future head'=>[['payload','expected_head','generation'],9,true];
        yield 'prefixed head'=>[['payload','expected_head','digest'],'sha256:'.str_repeat('a',64),true];
        yield 'bad signature'=>[['signature'],base64_encode(str_repeat('x',64)),false];
        yield 'noncanonical base64'=>[['signature'],str_repeat(' ',88),false];
        yield 'short signature'=>[['signature'],base64_encode(str_repeat('x',63)),false];
    }
    #[DataProvider('badEnrollment')]
    public function testSeparateAdministrativeEnrollmentRefusals(string $field,mixed $value):void {
        $h=$this->f->enrollment(); $h['body'][$field]=$value; unset($h['record_digest']); $h=Rules::seal($h);
        $this->refuses(fn()=>(new Enrollment($this->f->store))->enroll(F::json($h),'sha256:'.hash('sha256',$this->f->public),$this->f->head()));
    }
    public static function badEnrollment():iterable {
        foreach (['competence'=>'CITADEL_MISSION_FORMATION','fingerprint'=>'sha256:'.str_repeat('b',64),'public_key'=>base64_encode(str_repeat('x',32)),
            'issuer'=>['kind'=>'operator','id'=>'other-human'],'effects'=>['AUTHORIZE_BOOTSTRAP_POLICY'],'not_before'=>1800000001,'expires_at'=>1800000000,'custody_receipt'=>'','verified'=>true] as $k=>$v) { yield $k=>[$k,$v]; }
    }
    #[DataProvider('badPolicies')]
    public function testCompletePolicyClosure(array $path,mixed $value):void {
        $this->f->enroll(); $h=$this->f->policy(); $cursor=&$h['body']; foreach ($path as $part) { $cursor=&$cursor[$part]; } $cursor=$value; unset($cursor);
        unset($h['record_digest']); $h=Rules::seal($h); $this->refuses(fn()=>$this->f->admitPolicy($h));
    }
    public static function badPolicies():iterable {
        foreach (['installation_mode'=>'EXISTING','provider'=>'other','application_mode'=>'B','policy_version'=>'future','expires_at'=>1800001801,'new_field'=>true] as $k=>$v) { yield $k=>[[$k],$v]; }
        yield 'budget increase'=>[['limits','total_micro_usd'],1200001];
        yield 'numeric budget'=>[['limits','total_micro_usd'],'1200000'];
        yield 'retry allowance'=>[['evidence_policy','retryable_failure_allowlist'],['timeout']];
        yield 'private scope'=>[['evidence_policy','data_scope'],'PRIVATE'];
        yield 'wrong age'=>[['evidence_policy','freshness_ms','access'],900001];
        yield 'missing universe'=>[['candidate_bindings'],[]];
        yield 'different model'=>[['candidate_bindings',0,'model_id'],'deepseek-other'];
        yield 'revision claim'=>[['candidate_bindings',0,'revision_pin'],'PINNED'];
        yield 'target augur'=>[['targets',0,'role'],'oracle.augur'];
        yield 'predicate missing'=>[['targets',0,'predicate_ids'],[]];
        yield 'permitted missing'=>[['targets',0,'permitted_bindings'],[]];
        yield 'graph missing'=>[['steps'],[]];
        yield 'unknown action'=>[['steps',0,'action'],'EXECUTE_CALLBACK'];
        yield 'cycle'=>[['steps',0,'depends_on'],['apply-assignments']];
        yield 'duplicate step'=>[['steps',1,'step_id'],'configure'];
        yield 'slot on pure step'=>[['steps',0,'effect_slot_id'],'slot.access'];
        yield 'retry dependency'=>[['steps',7,'depends_on'],['w1.attempt.0']];
        yield 'wrong predecessor'=>[['steps',7,'run_condition','prior_attempt_step_id'],'w2.attempt.0'];
        yield 'wrong group input'=>[['steps',10,'input_refs',2,'group_id'],'W3'];
        yield 'arbitrary condition'=>[['steps',6,'run_condition'],['kind'=>'expression','code'=>'true']];
        yield 'missing groups'=>[['assessment_groups'],[]];
        yield 'five attempts'=>[['assessment_groups',0,'attempt_step_ids'],['a','b','c','d','e']];
        yield 'group cycle'=>[['assessment_groups',0,'requires_success_of'],['W3']];
        yield 'missing slots'=>[['effect_slots'],[]];
        yield 'slot reuse'=>[['effect_slots',1,'slot_id'],'slot.admit-evidence'];
        yield 'two uses'=>[['effect_slots',0,'max_uses'],2];
        yield 'expired slot'=>[['effect_slots',0,'expires_at'],1800000000];
        yield 'wrong pairing'=>[['effect_slots',0,'effect'],'APPLY_BOOTSTRAP_ASSIGNMENTS'];
        yield 'slot dependency'=>[['effect_slots',0,'depends_on'],[]];
        yield 'unbounded terms'=>[['effect_slots',0,'terms_rule'],['kind'=>'wildcard']];
        yield 'unregistered mode'=>[['effect_slots',0,'authority_mode'],'batch'];
        yield 'missing allowed effect'=>[['allowed_effects'],[]];
    }
    #[DataProvider('registry')]
    public function testEveryRegistrySupplyMode(string $effect,string $mode,bool $allowed):void {
        if (!$allowed) { $this->expectException(\RuntimeException::class); }
        Rules::effect($effect,$mode); if ($allowed) { self::assertTrue(true); }
    }
    public static function registry():iterable {
        foreach (Rules::EFFECTS as $e) { foreach (['signed_act','policy_effect'] as $mode) { yield $e.'-'.$mode=>[$e,$mode,$mode==='signed_act'||in_array($e,Rules::DERIVED,true)]; } }
    }
    #[DataProvider('revocations')]
    public function testRevocationStopsCurrentWorkButRetainsHistory(string $kind):void {
        $this->f->enroll(); [$h,$e,$result]=$this->f->admitPolicy(); $raws=array_map(F::json(...),array_values($this->f->sources));
        $id=match($kind){'act'=>$e['payload']['nonce'],'policy'=>$h['id'],'issuer'=>'operator-test'}; $this->f->revoke($kind,$id);
        $repeat=(new Admission($this->f->store))->retain(F::json($e),F::json($h),$raws); self::assertSame('HISTORICAL_RECOGNITION',$repeat['status']);
        $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)));
    }
    public static function revocations():iterable { foreach (['act','policy','issuer'] as $k) { yield $k=>[$k]; } }
    public function testConflictAndHistoricalHeadVersusCurrentObservation():void {
        $this->f->enroll(); [$h,$e,$r]=$this->f->admitPolicy(); $raws=array_map(F::json(...),array_values($this->f->sources));
        $this->f->store->journal->change(static function(array &$state):void { $state['unrelated']='preserved'; });
        $result=(new Resolver($this->f->store))->current(F::authority($r),'AUTHORIZE_BOOTSTRAP_POLICY',Rules::reference($h)); self::assertSame($this->f->head(),$result['observed_head']);
        $e['payload']['expected_head']=$this->f->head(); $e=$this->f->resign($e);
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),$raws),'ADMISSION_CONFLICT');
        self::assertSame('preserved',$this->f->store->journal->read()['state']['unrelated']);
    }
    public function testPolicyEffectCannotBypassFutureProgressionAndOriginalIdentity():void {
        $this->f->enroll(); [$h,$e,$r]=$this->f->admitPolicy(); $slot=$h['body']['effect_slots'][0];
        $a=['kind'=>'policy_effect','policy_ref'=>Rules::reference($h),'policy_admission_ref'=>Rules::reference($r['admission']),
            'slot_id'=>$slot['slot_id'],'slot_digest'=>Rules::hash($slot),'terms_ref'=>$slot['terms_rule']['object_ref'],'derivation_input_refs'=>[]];
        $this->refuses(fn()=>(new Resolver($this->f->store))->current($a,$slot['effect'],$a['terms_ref']),'DYNAMIC_PREREQUISITES_MISSING');
        $a['slot_digest']='sha256:'.str_repeat('b',64); $this->refuses(fn()=>(new Resolver($this->f->store))->current($a,$slot['effect'],$a['terms_ref']),'SLOT_SCOPE');
        $ref=Rules::reference($h); $ref['digest']='sha256:'.str_repeat('b',64); $this->refuses(fn()=>(new Resolver($this->f->store))->original($ref),'SOURCE_MISSING');
        $state=$this->f->store->journal->read()['state']['onboarding'];
        self::assertSame(['schema','trust','acts','policies','evidence','revocations','admissions'],array_keys($state));
    }
    public function testMissingExtraTamperedAndForeignSources():void {
        $this->f->enroll(); $h=$this->f->policy(); $e=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY'); $raws=array_map(F::json(...),array_values($this->f->sources));
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),array_slice($raws,1)),'SOURCE_MISSING');
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),[...$raws,$raws[0]]),'DUPLICATE_BUNDLE');
        $extra=$this->f->source('unrelated','Do not execute this source text'); $extraH=$this->f->sources[Rules::key($extra)];
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),[...$raws,F::json($extraH)]),'UNREACHABLE_SUPPORT');
        $tampered=json_decode($raws[0],true); $tampered['body']['content']='tamper'; $bad=$raws; $bad[0]=F::json($tampered);
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),$bad),'RECORD_DIGEST');
        $tampered['instance_id']='instance-foreign'; unset($tampered['record_digest']); $bad[0]=F::json(Rules::seal($tampered));
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),$bad),'FOREIGN_RECORD');
    }
    public function testExcludedMetadataAndJournalCompatibility():void {
        foreach (glob(dirname(__DIR__,3).'/src/Imperium/Runtime/Onboarding/AuthorityAdmission/*.php') as $file) {
            $class='App\\Imperium\\Runtime\\Onboarding\\AuthorityAdmission\\'.basename($file,'.php');
            self::assertCount(1,(new \ReflectionClass($class))->getAttributes(\Symfony\Component\DependencyInjection\Attribute\Exclude::class));
        }
        $this->f->store->journal->change(static function(array &$s):void {$s['legacy']='unchanged';});
        $head=$this->f->head(); $this->f->store->journal->changeAtHead(static function(array &$s,array $observed)use($head):void { self::assertSame($head,$observed); self::assertSame('unchanged',$s['legacy']); });
        self::assertSame($head,$this->f->head());
    }
    public function testSignedRetentionRemainsPendingAndCannotExpandDeclaredTerms():void {
        $this->f->enroll(); $h=$this->f->policy(); $h['body']['effect_slots'][0]['authority_mode']='signed_act';
        unset($h['record_digest']); $h=Rules::seal($h); [$h,$e,$r]=$this->f->admitPolicy($h);
        $ref=$h['body']['effect_slots'][0]['terms_rule']['object_ref']; $terms=$this->f->sources[Rules::key($ref)];
        $envelope=$this->f->sign($terms,'ADMIT_BOOTSTRAP_EVIDENCE',Rules::reference($h));
        $result=(new Admission($this->f->store))->retain(F::json($envelope),F::json($terms));
        self::assertFalse($result['effect_completed']); self::assertFalse($result['authority_consumed']);
        $resolved=(new Resolver($this->f->store))->current(F::authority($result),'ADMIT_BOOTSTRAP_EVIDENCE',$ref);
        self::assertSame('CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION',$resolved['status']); self::assertFalse($resolved['execution_authority']);
        $terms['id']='terms-expanded'; unset($terms['record_digest']); $terms=Rules::seal($terms);
        $bad=$this->f->sign($terms,'ADMIT_BOOTSTRAP_EVIDENCE',Rules::reference($h));
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($bad),F::json($terms)),'SIGNED_TERMS_OUTSIDE_POLICY');
        $wrong=F::authority($result); $wrong['admission_ref']=Rules::reference($h);
        $this->refuses(fn()=>(new Resolver($this->f->store))->current($wrong,'ADMIT_BOOTSTRAP_EVIDENCE',$ref),'ADMISSION_MISSING');
    }
    public function testSupportPermutationRecognitionPreservesOriginalBytes():void {
        $this->f->enroll(); [$h,$e,$r]=$this->f->admitPolicy(); $before=$this->f->head();
        $again=(new Admission($this->f->store))->retain(F::json($e),F::json($h),array_reverse(array_map(F::json(...),array_values($this->f->sources))));
        self::assertSame('HISTORICAL_RECOGNITION',$again['status']); self::assertSame($before,$this->f->head());
        self::assertSame($r['admission'],$again['admission']);
    }
    public function testReferenceDepthAndDuplicateIdentityFailWithoutRetention():void {
        $this->f->enroll(); $h=$this->f->policy(); $records=array_values($this->f->sources);
        $last=$records[0];
        for ($i=0;$i<18;++$i) {
            $next=$last; $next['id']='depth-'.str_pad((string)$i,8,'0',STR_PAD_LEFT); $next['sources']=[Rules::reference($last)]; unset($next['record_digest']);
            $last=Rules::seal($next); $records[]=$last;
        }
        $h['sources']=Rules::refs([...$h['sources'],Rules::reference($last)]); unset($h['record_digest']); $h=Rules::seal($h); $e=$this->f->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY');
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),array_map(F::json(...),$records)),'SOURCE_DEPTH');
        $duplicate=$records[0]; $duplicate['body']['limitations']='Different record, same identity'; unset($duplicate['record_digest']); $duplicate=Rules::seal($duplicate);
        $this->refuses(fn()=>(new Admission($this->f->store))->retain(F::json($e),F::json($h),[F::json($records[0]),F::json($duplicate)]),'BUNDLE_ID_CONFLICT');
    }
    public function testTamperedFrameFailsClosedOnRestart():void {
        $this->f->enroll(); [$h]=$this->f->admitPolicy();
        $path=$this->f->root.'/var/imperium/citadel/formation/000000000002.json';
        $frame=json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR); $frame['state']['onboarding']['schema']='future';
        file_put_contents($path,F::json($frame)); $this->expectExceptionMessage('CMF003_JOURNAL_CHAIN_INVALID');
        (new Resolver($this->f->store))->original(Rules::reference($h));
    }
    public function testUnsupportedStateAndRetainedByteTampering():void {
        $this->f->enroll(); [$h]=$this->f->admitPolicy();
        $this->f->store->journal->change(static function(array &$s):void { $s['onboarding']['schema']='future'; });
        $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)),'STATE_VERSION');
        $this->f->store->journal->change(static function(array &$s)use($h):void {
            $s['onboarding']['schema']='imperium.onboarding-authority-state/v1';
            $s['onboarding']['policies'][Rules::key(Rules::reference($h))]['raw']='{"forged":true}';
        });
        $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)),'RETAINED_BYTES');
    }
    public function testHalfOpenValidityAndNoImplicitTimeUnitConversion():void {
        $this->f->enroll(); [$h,$e,$r]=$this->f->admitPolicy(); $this->f->now+=1799;
        self::assertFalse((new Resolver($this->f->store))->current(F::authority($r),'AUTHORIZE_BOOTSTRAP_POLICY',Rules::reference($h))['execution_authority']);
        ++$this->f->now; $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)),'ACT_TIME');
        $this->f->now=1800010000; $this->refuses(fn()=>(new Resolver($this->f->store))->original(Rules::reference($h)),'TRUST_TIME');
        foreach ([1800000000000,PHP_INT_MAX,0] as $time) { try { Rules::time($time); self::fail('Invalid seconds accepted'); } catch (\RuntimeException $e) { self::assertSame('O2_TIME_SECONDS',$e->getMessage()); } }
    }
}
