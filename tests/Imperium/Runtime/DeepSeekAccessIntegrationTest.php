<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Tests\Imperium\Runtime\Support\DeepSeekFixture as F;
use App\Imperium\Runtime\Onboarding\DeepSeek\{Runtime,EnvelopeStore,Wire};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;
use Symfony\Component\HttpClient\{MockHttpClient,RetryableHttpClient,Response\MockResponse};
use PHPUnit\Framework\{TestCase,Attributes\DataProvider};

final class DeepSeekAccessIntegrationTest extends TestCase
{
    public function testActualB0O2AccessSettlesExactAttributionAndReplays(): void
    {
        $f=new F(); try {
            $f->ready(); $q=$f->request('access'); $r=$f->runtime->advance($f->f::json($q)); $c=$f->claim();
            self::assertCount(1,$f->requests); self::assertCount(5,$c['custody']); self::assertNotNull($c['settled']);
            $request=$f->requests[0]; self::assertSame('GET',$request['method']);
            // Symfony canonicalizes explicit default port 443; this is the same destination, not a redirect.
            self::assertSame('https://api.deepseek.com/models',$request['url']);
            self::assertSame('https://api.deepseek.com:443/models',$c['operation']['prepared']['destination']);
            self::assertSame('',$request['options']['body']); self::assertSame(0,$request['options']['max_redirects']);
            self::assertFalse($request['options']['buffer']); self::assertSame('http://127.0.0.1:9',$request['options']['proxy']);self::assertSame('*',$request['options']['no_proxy']);
            self::assertGreaterThan(0,$request['options']['max_duration']); self::assertLessThanOrEqual(10,$request['options']['max_duration']);
            $e=$f->envelopes->read(R::reference($c['record'])); self::assertSame(F::listing(),$e['response']);
            self::assertSame(R::hash($c['operation']['prepared']),$e['operation_digest']);
            self::assertSame('local:deepseek-model-list:sha256:'.hash('sha256',F::listing()),$e['metadata']['provider_response_id']);
            $head=$f->f->head(); $f->runtime->advance($f->f::json($q)); self::assertSame($head,$f->f->head()); self::assertCount(1,$f->requests);
            self::assertSame(array_fill_keys(['deployment_approved','enrollment_authorized','live_ready','activation','execution_authority'],false),$r['operational_flags']);
            $state=$f->f->store->journal->read()['state']; self::assertSame([],$state['onboarding']['source_fences']);
            $sentinel=implode('-',['synthetic','o3','sentinel','not','a','credential']); self::assertStringNotContainsString($sentinel,json_encode([$state,$e,$f->requests]));
            // Restart has no delivery right and can only recognize/finish original evidence.
            $restart=new Runtime($f->f->store,$f->adapter,$f->keys,$f->envelopes,new MockHttpClient(static function(){throw new \RuntimeException('Unexpected transport');}));
            $restart->reconcile(array_key_first($state['onboarding']['claims'])); self::assertSame($head,$f->f->head());
            $f->last=$r['result_ref'];
            foreach (['select-base','w1.attempt.0'] as $step) {
                try { $f->advance($step);self::fail('Missing producer must refuse'); }
                catch (\RuntimeException $e) { self::assertStringContainsString($step==='select-base'?'PROJECTION_MISSING':'NOT_READY',$e->getMessage()); }
                self::assertSame($head,$f->f->head());self::assertCount(1,$f->requests);
            }
        } finally { $f->close(); }
    }
    #[DataProvider('preRefusals')]
    public function testMissingOrChangedAuthorityNeverReservesOrDispatches(string $case): void
    {
        $f=new F(missingEvidence:$case==='evidence'); try {
            $f->ready();
            if ($case==='rotation') { $f->keys->version='rotated'; }
            if ($case==='expiry') { $f->f->now+=300; }
            if ($case==='revocation') { $f->f->revoke('policy',$f->policy['id']); }
            if ($case==='grant-act-revocation') {
                $s=$f->f->store->journal->read()['state']['onboarding'];
                $admission=$s['evidence'][R::key(R::reference($f->grant))]['admission_key'];
                $f->f->revoke('act',$s['acts'][$admission]['envelope']['payload']['nonce']);
            }
            $head=$f->f->head();
            try { $f->advance('access'); self::fail('Expected refusal'); } catch (\RuntimeException $e) { self::assertStringStartsWith('O',$e->getMessage()); }
            self::assertSame($head,$f->f->head()); self::assertSame([],$f->requests);
            self::assertSame([],$f->f->store->journal->read()['state']['onboarding']['claims']);
        } finally { $f->close(); }
    }
    public static function preRefusals(): iterable { foreach (['evidence','rotation','expiry','revocation','grant-act-revocation'] as $v) { yield [$v]; } }
    #[DataProvider('httpFailures')]
    public function testHTTPFailuresKeepMaximumAndNeverRetry(string $case): void
    {
        $f=new F(function(F $f)use($case): MockResponse {
            if (ctype_digit($case)) { return new MockResponse('{"error":"public"}',['http_code'=>(int)$case]); }
            if ($case==='exception') { throw new \RuntimeException('transport failed'); }
            if ($case==='duplicate') { return new MockResponse('{"object":"list","object":"list","data":[]}'); }
            if ($case==='truncated') { return new MockResponse('{"object":"list"'); }
            if ($case==='secret') { return new MockResponse(json_encode(['object'=>'list','data'=>[],'secret'=>implode('-',['synthetic','o3','sentinel','not','a','credential'])])); }
            if ($case==='escaped-secret') {
                $secret=implode('-',['synthetic','o3','sentinel','not','a','credential']);
                $body=json_encode(['object'=>'list','data'=>[['id'=>$secret,'object'=>'model','created'=>1,'owned_by'=>'deepseek']]]);
                return new MockResponse(str_replace('-', '\\u002d',$body));
            }
            if ($case==='large') { return new MockResponse((static function(){yield str_repeat(' ',700000);yield str_repeat(' ',400000);})()); }
            return new MockResponse((function() use($f){yield ' '; $f->tick+=10000; yield F::listing();})());
        }); try {
            $f->ready(); $q=$f->request('access');
            try { $f->runtime->advance($f->f::json($q)); self::fail(); } catch (\RuntimeException $e) { self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage()); self::assertNull($e->getPrevious()); }
            $c=$f->claim(); self::assertNull($c['settled']); self::assertCount(3,$c['custody']); self::assertSame(10000,$c['maximum']['milliseconds']); self::assertCount(1,$f->requests);
            $f->runtime->advance($f->f::json($q)); self::assertCount(1,$f->requests);
            $f->last=$c['record']['body']['command_ref']; $f->f->now+=1801;
            try { $f->advance('access'); self::fail(); } catch (\RuntimeException) { self::assertCount(1,$f->requests); }
            self::assertNull($f->claim()['settled']); self::assertNotEmpty($f->f->store->journal->read()['state']['onboarding']['source_fences']);
        } finally { $f->close(); }
    }
    public static function httpFailures(): iterable { foreach (['301','401','402','429','500','503','exception','duplicate','truncated','secret','escaped-secret','large','deadline'] as $v) { yield [$v]; } }
    #[DataProvider('keyChanges')]
    public function testKeyBoundaryCurrentnessAndCallbackOnce(string $case): void
    {
        $f=new F(); try {
            $f->ready();
            if ($case==='repeat') { $f->keys->repeat=true; }
            elseif ($case==='omit') { $f->keys->omit=true; }
            else { $f->keyHook=function()use($f,$case):void { if($case==='rotation'){$f->keys->version='rotated';} elseif($case==='expiry'){$f->f->now+=300;}else{$f->f->revoke('policy',$f->policy['id']);} }; }
            try { $f->advance('access'); self::fail(); } catch (\RuntimeException $e) { self::assertSame('O2_CUSTODY_REFUSED_OR_OUTCOME_UNKNOWN',$e->getMessage()); }
            self::assertCount($case==='repeat'?1:0,$f->requests); self::assertNull($f->claim()['settled']);
        } finally { $f->close(); }
    }
    public static function keyChanges(): iterable { foreach (['repeat','omit','rotation','expiry','revocation'] as $v) { yield [$v]; } }
    public function testNoPublicDeliveryPortsAndRetryDecoratorRefuses(): void
    {
        $methods=array_filter((new \ReflectionClass(Runtime::class))->getMethods(\ReflectionMethod::IS_PUBLIC),static fn($m)=>!$m->isConstructor());
        self::assertSame(['advance','reconcile'],array_values(array_map(static fn($m)=>$m->getName(),$methods)));
        $f=new F(); try {
            $this->expectException(\RuntimeException::class);
            new Runtime($f->f->store,$f->adapter,$f->keys,$f->envelopes,new RetryableHttpClient(new MockHttpClient()));
        } finally { $f->close(); }
    }
    public function testPrivateCapabilitiesRejectCopiesSerializationAndOperationSwap(): void
    {
        $f=new F(); try {
            $f->ready();
            $ledger=new \App\Imperium\Runtime\Onboarding\Ledger\CommandLedger($f->f->store,$f->adapter,$f->adapter);
            $ledger->advance($f->f::json($f->request('access')));
            $coordinator=(new \ReflectionProperty(Runtime::class,'coordinator'))->getValue($f->runtime);
            $id=array_key_first($f->f->store->journal->read()['state']['onboarding']['claims']);
            (new \ReflectionMethod($coordinator,'checkpoint'))->invoke($coordinator,$id,0);
            $c=$f->claim();$claim=$c['record'];$op=$c['operation']['prepared'];
            // Reflection is test-only to inspect the otherwise unreachable infrastructure boundary.
            $issue=new \ReflectionMethod(Runtime::class,'issue');$consume=new \ReflectionMethod(Runtime::class,'consume');
            $cap=$issue->invoke($f->runtime,$claim,$op);
            try { serialize($cap);self::fail(); } catch (\RuntimeException $e) { self::assertSame('O3_CAPABILITY_NOT_SERIALIZABLE',$e->getMessage()); }
            try { $copy=clone $cap;self::fail(); } catch (\Error $e) { self::assertStringContainsString('__clone',$e->getMessage()); }
            $delivered=0;$callback=static function()use(&$delivered):void{++$delivered;};
            try { $consume->invoke($f->runtime,new \stdClass(),$claim,$op,$callback);self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('O2_CAPABILITY_FOREIGN_OR_USED',$e->getMessage()); }
            $changed=$op;$changed['wire']='swapped';
            try { $consume->invoke($f->runtime,$cap,$claim,$changed,$callback);self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('O2_CAPABILITY_SCOPE',$e->getMessage()); }
            try { $consume->invoke($f->runtime,$cap,$claim,$op,$callback);self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('O2_CAPABILITY_FOREIGN_OR_USED',$e->getMessage()); }
            try { $issue->invoke($f->runtime,$claim,$op);self::fail(); }
            catch (\RuntimeException $e) { self::assertSame('O2_CAPABILITY_ALREADY_ISSUED',$e->getMessage()); }
            self::assertSame(0,$delivered);self::assertSame([],$f->requests);self::assertNull($f->claim()['settled']);
        } finally { $f->close(); }
    }
}
