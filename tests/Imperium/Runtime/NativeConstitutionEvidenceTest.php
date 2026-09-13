<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\Augur\{AugurMigration,ConstitutionSource,NativeConstitutionEvidence};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\CommandLedger;
use App\Tests\Imperium\Runtime\Support\AugurFreshFixture as F;
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

/** Real institutional preparation/admission/publication; only provider dependencies
 * and public artifact contents are synthetic. No expected-record verifier. */
final class NativeConstitutionEvidenceTest extends TestCase
{
    private function ready(F $f):void
    {
        (new AugurMigration($f->d->f->store))->migrate($f->d->f->head());
        $f->ready();$f->d->advance('select-base');$f->d->advance('map-base');
    }
    #[DataProvider('authorityModes')]
    public function testPreparedApprovalPublishesAndRevalidatesExactHolder(string $mode):void
    {
        $f=new F(configure:static function(array &$policy)use($mode):void{
            foreach($policy['body']['effect_slots'] as &$slot){if($slot['slot_id']==='slot.found-augur'){$slot['authority_mode']=$mode;}}unset($slot);
        },nativeConstitution:true);try{
            $d=$f->d;$store=$d->f->store;$this->ready($f);$head=$d->f->head();
            if($mode==='signed_act'){
                $slot=array_values(array_filter($d->policy['body']['effect_slots'],static fn(array $slot):bool=>$slot['slot_id']==='slot.found-augur'))[0];
                $terms=$d->f->sources[R::key($slot['terms_rule']['permitted_object_refs'][0])];
                (new Admission($store))->retain($d->f::json($d->f->sign($terms,'CONSTITUTE_FOUNDING_AUGUR',R::reference($d->policy))),$d->f::json($terms));
                $head=$d->f->head();
            }
            self::assertSame([],$store->journal->read()['state']['onboarding']['bindings']);
            $producer=$f->founding();$q=$d->request('found-augur');
            $ledger=new CommandLedger($store,$d->adapter,$d->adapter,$producer);
            $result=$ledger->advance($d->f::json($q));$s=$store->journal->read()['state']['onboarding'];
            $holder=array_values($s['bindings'])[0]['record'];
            self::assertSame($holder,$store->journal->inspect(fn(array $frame):array=>$producer->current($store,$frame['state']['onboarding'],R::reference($holder))));
            self::assertSame($head['generation']+1,$holder['body']['generation']);
            foreach(['charter_ref','persona_ref','profile_ref'] as $field){self::assertSame(Policy::content($f->constitution,'augur-constitution')[$field],$holder['body'][$field]);}
            self::assertSame(array_fill_keys(array_keys($result['operational_flags']),false),$result['operational_flags']);
            $after=$d->f->head();self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($q))['status']);self::assertSame($after,$d->f->head());
            $d->f->revoke('policy',$d->policy['id']);
            try{$store->journal->inspect(fn(array $frame):array=>$producer->current($store,$frame['state']['onboarding'],R::reference($holder)));self::fail('Revoked approval must block current use');}
            catch(\RuntimeException $e){self::assertStringContainsString('REVOKED',$e->getMessage());}
            self::assertSame('HISTORICAL_RECOGNITION',$ledger->advance($d->f::json($q))['status']);
        }finally{$f->close();}
    }
    public static function authorityModes():iterable{yield ['policy_effect'];yield ['signed_act'];}
    public function testArtifactPreparationDoesNotCreateAuthorityOrPublication():void
    {
        $f=new F(nativeConstitution:true);try{
            $store=$f->d->f->store;$head=$f->d->f->head();
            try{(new NativeConstitutionEvidence())->verify($f->constitution,$f->constitutionalOriginals);self::fail('Detached originals are not competence');}
            catch(\RuntimeException $e){self::assertSame('O2_CONSTITUTION_OWNER_FRAME_REQUIRED',$e->getMessage());}
            self::assertSame($head,$f->d->f->head());
            $this->ready($f);$s=$store->journal->read()['state']['onboarding'];
            // A fabricated publication cannot be recognized before the owner commit.
            try{$f->founding()->current($store,$s,['schema'=>'imperium.bootstrap-augur-holder/v1','id'=>'holder-never-published','digest'=>'sha256:'.str_repeat('0',64)]);self::fail();}
            catch(\RuntimeException $e){self::assertSame('O2_HOLDER_ORIGINAL_MISSING',$e->getMessage());}
        }finally{$f->close();}
    }
    #[DataProvider('alterations')]
    public function testCurrentFrameRejectsSubstitution(string $case):void
    {
        $alternative=null;
        $f=new F(configure:function(array &$p,$authority,array $profile)use(&$alternative,$case):void{
            // Included in an authentic signed bundle, but absent from the finite
            // founding terms: admission and hashes alone cannot approve it.
            $charter=$authority->sources[R::key($authority->source('public-charter',['seat'=>'oracle.augur','text'=>'A different charter']))];
            $persona=$authority->sources[R::key($authority->source('public-persona',['text'=>'A different persona']))];
            $alternative=ConstitutionSource::prepare($authority->store,new OperatorRootOwnership($authority->root),$charter,$persona,$authority->sources[R::key($profile)],$authority->now-1,$authority->now+1800);
            $authority->sources[R::key(R::reference($alternative))]=$alternative;
        },nativeConstitution:true);
        try{
            $this->ready($f);$d=$f->d;$store=$d->f->store;$head=$d->f->head();
            $store->journal->inspect(function(array $frame)use($f,$d,$store,$alternative,$case):void{
                $s=$frame['state']['onboarding'];$originals=$f->constitutionalOriginals;$constitution=$f->constitution;$owner=new OperatorRootOwnership($d->f->root);
                if($case==='unapproved'){
                    $constitution=$alternative;$grant=Policy::content($constitution,'augur-constitution');
                    foreach(['charter_ref','persona_ref','profile_ref'] as $field){$originals[R::key($grant[$field])]=$store->checkSource($s,$grant[$field]);}
                }elseif($case==='artifact-bytes'){$key=array_key_first($originals);$originals[$key]['body']['content']='Changed';}
                elseif($case==='unknown-authority'){$s['admissions']=[];}
                elseif($case==='foreign-issuer'){$s['trust']['body']['issuer']['id']='operator-foreign';}
                try{(new NativeConstitutionEvidence())->verifyInFrame($store,$owner,$s,$d->policy,$constitution,$originals,null);self::fail('Unauthorized evidence accepted');}
                catch(\RuntimeException $e){self::assertStringStartsWith('O2_',$e->getMessage());
                    if($case==='unapproved'){self::assertSame('O2_CONSTITUTION_APPROVED_ARTIFACTS',$e->getMessage());}
                    if($case==='artifact-bytes'){self::assertSame('O2_CONSTITUTION_ARTIFACT_ORIGINAL',$e->getMessage());}
                }
            });
            self::assertSame($head,$d->f->head());
        }finally{$f->close();}
    }
    public static function alterations():iterable
    {
        foreach(['unapproved','artifact-bytes','unknown-authority','foreign-issuer'] as $case){yield $case=>[$case];}
    }
    #[DataProvider('scopes')]
    public function testEvenCompetentApprovalCannotChangeFrozenScope(string $field,mixed $value):void
    {
        $f=new F(configure:function(array &$policy,$authority)use($field,$value):void{
            foreach($policy['body']['effect_slots'] as &$slot){if($slot['slot_id']!=='slot.found-augur'){continue;}
                $terms=$authority->sources[R::key($slot['terms_rule']['permitted_object_refs'][0])];
                $intent=Policy::content($authority->sources[R::key($terms['body']['terms'])],'augur-founding-intent');
                $source=$authority->sources[R::key($intent['constitution_ref'])];$grant=Policy::content($source,'augur-constitution');
                $grant[$field]=$value;$content=$authority::json($grant);
                $source=$authority->store->make('imperium.bootstrap-source/v1','foreign-constitution',[
                    'kind'=>'augur-constitution','content'=>$content,'content_digest'=>'sha256:'.hash('sha256',$content),'limitations'=>'Synthetic adverse scope'], $source['sources']);
                $authority->sources[R::key(R::reference($source))]=$source;
                $intent['constitution_ref']=R::reference($source);$ref=$authority->source('augur-founding-intent',$intent);
                $terms=$authority->store->make('imperium.bootstrap-proposed-terms/v1','foreign-terms',[
                    'effect'=>'CONSTITUTE_FOUNDING_AUGUR','terms'=>$ref,'required_completed_refs'=>[]],[$ref]);
                $authority->sources[R::key(R::reference($terms))]=$terms;$slot['terms_rule']['permitted_object_refs']=[R::reference($terms)];
            }unset($slot);
        },nativeConstitution:true);
        try{
            $this->ready($f);$d=$f->d;$head=$d->f->head();
            try{(new CommandLedger($d->f->store,founding:$f->founding()))->advance($d->f::json($d->request('found-augur')));self::fail();}
            catch(\RuntimeException $e){self::assertSame('O2_CONSTITUTION_SCOPE',$e->getMessage());}
            self::assertSame($head,$d->f->head());
        }finally{$f->close();}
    }
    public static function scopes():iterable
    {
        yield ['root_identity','sha256:'.str_repeat('0',64)];yield ['seat','courtyard.courtthane'];
        yield ['instance_id','instance-other'];yield ['operator_id','operator-other'];yield ['expires_at',1799999999];
    }
    #[DataProvider('currentCases')]
    public function testFreshProcessUsesCurrentAuthorityAfterConstruction(bool $revoke):void
    {
        $f=new F(nativeConstitution:true);$process=null;
        try{
            $this->ready($f);$d=$f->d;$store=$d->f->store;$root=$d->f->root;
            (new CommandLedger($store,founding:$f->founding()))->advance($d->f::json($d->request('found-augur')));
            $holder=array_values($store->journal->read()['state']['onboarding']['bindings'])[0]['record'];
            $pins=[];foreach($d->f->sources as $key=>$source){if(in_array($source['body']['kind']??'', ['augur-base-facts','augur-base-observation','synthetic-provider-bytes'],true)){$pins[$key]=$source;}}
            file_put_contents($root.'/fresh-worker-input.json',$d->f::json(['now'=>$d->f->now,'native_constitution'=>true,
                'constitution'=>$f->constitution,'artifacts'=>[],'base_pins'=>$pins,'holder_ref'=>R::reference($holder)]));
            $process=proc_open([PHP_BINARY,'-d','allow_url_fopen=0','-d','disable_functions=curl_exec,curl_multi_exec,fsockopen,pfsockopen,stream_socket_client,socket_connect',__DIR__.'/Support/augur-fresh-worker.php',$root,'current'],
                [0=>['pipe','r'],1=>['file',$root.'/current-output','w'],2=>['file',$root.'/current-error','w']],$pipes);
            self::assertIsResource($process);fclose($pipes[0]);$deadline=microtime(true)+120;
            while(!is_file($root.'/fresh-ready-current')){if(microtime(true)>$deadline){self::fail('Worker did not construct');}usleep(10000);}
            if($revoke){$d->f->revoke('policy',$d->policy['id']);}
            $head=$d->f->head();file_put_contents($root.'/fresh-go','go');
            do{$status=proc_get_status($process);if(!$status['running']){break;}if(microtime(true)>$deadline){self::fail('Worker did not finish');}usleep(10000);}while(true);
            self::assertSame($revoke?23:0,$status['exitcode'],file_get_contents($root.'/current-error'));
            self::assertSame('',file_get_contents($root.'/current-error'));
            if($revoke){self::assertStringContainsString('REVOKED',file_get_contents($root.'/current-output'));}
            else{self::assertSame("CURRENT\n",file_get_contents($root.'/current-output'));}
            self::assertSame($head,$d->f->head());
        }finally{if(is_resource($process)){if(proc_get_status($process)['running']){proc_terminate($process);}proc_close($process);}$f->close();}
    }
    public static function currentCases():iterable{yield [false];yield [true];}
    public function testDormantCompositionUsesNativeConstitutionByDefault():void
    {
        $f=new F(nativeConstitution:true);try{
            $this->ready($f);$d=$f->d;$root=$d->f->root;
            $pins=[];foreach($d->f->sources as $key=>$source){if(in_array($source['body']['kind']??'', ['augur-base-facts','augur-base-observation','synthetic-provider-bytes'],true)){$pins[$key]=$source;}}
            mkdir($root.'/custody');mkdir($root.'/custody/keys');mkdir($root.'/composition-responses');
            file_put_contents($root.'/custody/custody.lock','');file_put_contents($root.'/custody/generation','synthetic-generation');
            file_put_contents($root.'/custody/keys/synthetic-generation','synthetic-unused-key');
            $composition=new \App\Imperium\Runtime\Onboarding\Deployment\Composition($d->f->store,new OperatorRootOwnership($root),$d->grant,
                $root.'/custody',$root.'/composition-responses',base:new \App\Tests\Imperium\Runtime\Support\SyntheticAugurBaseEvidence($pins),
                mock:new \Symfony\Component\HttpClient\MockHttpClient(static function():never{throw new \LogicException('Founding must not dispatch');}));
            $result=$composition->onboard($d->f::json($d->request('found-augur')));
            self::assertSame('ASSESSMENT_AUTHORIZED',$result['status']);
            self::assertCount(1,$d->f->store->journal->read()['state']['onboarding']['bindings']);
            self::assertCount(1,$d->requests); // Only the fixture's earlier mock access.
        }finally{$f->close();}
    }
}
