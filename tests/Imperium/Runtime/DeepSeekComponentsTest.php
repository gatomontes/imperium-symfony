<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime;
use App\Imperium\Runtime\Onboarding\DeepSeek\{Wire,TokenEvidence,Tariff,ProviderResponse,AccessAdapter};
use App\Tests\Imperium\Runtime\Support\DeepSeekFixture as F;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class DeepSeekComponentsTest extends TestCase
{
    public static function chat(): array { return ['id'=>'response-test','object'=>'chat.completion','created'=>1,'model'=>'deepseek-v4-flash','system_fingerprint'=>'fp-test',
        'choices'=>[['index'=>0,'message'=>['role'=>'assistant','content'=>'{"result":"public"}'],'finish_reason'=>'stop']],
        'usage'=>['prompt_tokens'=>100,'prompt_cache_hit_tokens'=>20,'prompt_cache_miss_tokens'=>80,'completion_tokens'=>10,'total_tokens'=>110,'completion_tokens_details'=>['reasoning_tokens'=>0]]]; }
    public static function tariff(): Tariff { return new Tariff('synthetic-account','deepseek-v4-flash',1,1000,440000,44000,1320000,'ceil_each_meter_microusd',0); }
    public function testExactApprovedWireAndTypedBounds(): void {
        foreach (Wire::MODELS as $model) {
            $wire=Wire::cognition($model,Wire::CONFIGURATION,'Return JSON.','Public évidence');
            self::assertSame('{"max_tokens":4096,"messages":[{"content":"Return JSON.","role":"system"},{"content":"Public évidence","role":"user"}],"model":"'.$model.'","response_format":{"type":"json_object"},"stream":false,"temperature":0,"thinking":{"type":"disabled"}}',$wire);
        }
        $wire=Wire::cognition('deepseek-v4-flash',Wire::CONFIGURATION,'Return JSON.','Public');
        Wire::preflight($wire,'deepseek-v4-flash',new TokenEvidence('sha256:'.hash('sha256',$wire),'synthetic-tokenizer-v1','synthetic-framing-v1',16384,4096,32768),self::tariff(),2,999);
        $m=ProviderResponse::cognition(json_encode(self::chat()),'deepseek-v4-flash',self::tariff(),20,'fp-test');
        self::assertSame(51,$m['usage']['cost_microusd']); self::assertSame(10,$m['usage']['output_tokens']);
        self::assertSame('provider:deepseek:response-test',$m['identity']);
    }
    #[DataProvider('invalidChat')]
    public function testStrictChatRefuses(string $case): void {
        $v=self::chat();
        switch ($case) {
            case 'model': $v['model']='other'; break;
            case 'fingerprint': $v['system_fingerprint']=null; break;
            case 'finish': $v['choices'][0]['finish_reason']='length'; break;
            case 'missing': unset($v['usage']['prompt_cache_hit_tokens']); break;
            case 'sum': $v['usage']['total_tokens']=111; break;
            case 'split': $v['usage']['prompt_cache_miss_tokens']=81; break;
            case 'overflow': $v['usage']['prompt_tokens']=PHP_INT_MAX; break;
            case 'reasoning': $v['usage']['completion_tokens_details']['reasoning_tokens']=11; break;
            case 'tool': $v['choices'][0]['message']['tool_calls']=[]; break;
            case 'type': $v['usage']['completion_tokens']='10'; break;
            case 'choice': $v['choices']=['one'=>$v['choices'][0]]; break;
            case 'extension': $v['extra']='unknown'; break;
        }
        $shapeCases=['fingerprint','missing','tool','extension'];
        $this->expectException(in_array($case,$shapeCases,true) ? \InvalidArgumentException::class : \RuntimeException::class);
        if (in_array($case,$shapeCases,true)) { $this->expectExceptionMessage($case==='fingerprint'?'INVALID_TEXT':'INVALID_FIELDS'); }
        ProviderResponse::cognition(json_encode($v),'deepseek-v4-flash',self::tariff(),20);
    }
    public static function invalidChat(): iterable { foreach (['model','fingerprint','finish','missing','sum','split','overflow','reasoning','tool','type','choice','extension'] as $case) { yield $case=>[$case]; } }
    #[DataProvider('badJson')]
    public function testRawListingBoundary(string $bytes): void { $this->expectException(\Throwable::class); ProviderResponse::listing($bytes,1); }
    public static function badJson(): iterable {
        yield ['{"object":"list","object":"list","data":[]}'];
        yield ['{"object":"list","\u006fbject":"list","data":[]}'];
        yield ['{"object":"list","data":{}}']; yield ['{"object":"list","data":[]'];
        yield ["{\"object\":\"list\",\"data\":[],\"x\":\"\xff\"}"]; yield [str_repeat(' ',1048577)];
        yield ['{"object":"list","data":[],"usage":0}'];
    }
    #[DataProvider('badPreflight')]
    public function testPreflightRefusesMissingOrFalseBounds(string $case): void {
        $wire=Wire::cognition('deepseek-v4-flash',Wire::CONFIGURATION,'JSON','public');
        $this->expectException(\Throwable::class);
        if ($case==='default') { (new AccessAdapter())->prepare([]); return; }
        if ($case==='config') { Wire::cognition('deepseek-v4-flash',[...Wire::CONFIGURATION,'top_p'=>1],'JSON','public'); return; }
        if ($case==='float') { Wire::cognition('deepseek-v4-flash',[...Wire::CONFIGURATION,'temperature'=>0.0],'JSON','public'); return; }
        if ($case==='alias') { Wire::cognition('deepseek-chat',Wire::CONFIGURATION,'JSON','public'); return; }
        if ($case==='overflow') { (new Tariff('a','deepseek-v4-flash',1,1000,PHP_INT_MAX,0,1,'ceil_each_meter_microusd',0))->cost(0,2,0); return; }
        if ($case==='fee') { new Tariff('a','deepseek-v4-flash',1,1000,1,1,1,'ceil_each_meter_microusd',1); return; }
        $tokens=$case==='missing' ? null : new TokenEvidence('sha256:'.hash('sha256',$case==='wire'?'other':$wire),'tokenizer','framing',$case==='bound'?16385:16384,4096,32768);
        Wire::preflight($wire,'deepseek-v4-flash',$tokens,self::tariff(),2,$case==='expiry'?1001:999);
    }
    public static function badPreflight(): iterable { foreach (['default','config','float','alias','overflow','fee','missing','wire','bound','expiry'] as $c) { yield [$c]; } }
    public function testLocalListingIdentityIsExplicitAndBytesPreserved(): void {
        $bytes=F::listing(); $v=ProviderResponse::listing($bytes,4);
        self::assertSame('local:deepseek-model-list:sha256:'.hash('sha256',$bytes),$v['identity']);
        self::assertSame(0,$v['usage']['cost_microusd']); self::assertSame(4,$v['usage']['milliseconds']);
        self::assertNotSame($v['identity'],ProviderResponse::listing(' '.$bytes,4)['identity']);
    }
    public function testExactPostOptionsAndUnknownWireCannotPass(): void {
        $op=['schema'=>'imperium.bootstrap-prepared-operation/v1','method'=>'POST','destination'=>'https://api.deepseek.com:443/chat/completions',
            'provider'=>'deepseek','adapter'=>Wire::ADAPTER,'model'=>'deepseek-v4-flash',
            'wire'=>Wire::cognition('deepseek-v4-flash',Wire::CONFIGURATION,'Return JSON.','Public'),
            'configuration_ref'=>['schema'=>'configuration','id'=>'config-test','digest'=>'sha256:'.str_repeat('a',64)],
            'credential_operation'=>'component-only','maximum'=>[],'expires_at'=>1000,'authority_source'=>[]];
        $options=Wire::options($op,'fake-component-key',60000);
        self::assertSame($op['wire'],$options['body']);self::assertSame(0,$options['max_redirects']);self::assertSame(60,$options['max_duration']);
        $body=json_decode($op['wire'],true);$body['top_p']=1;$op['wire']=json_encode($body);
        $this->expectExceptionMessage('INVALID_FIELDS');Wire::options($op,'fake-component-key',60000);
    }
    public function testPrivatePostTransportComponentWithMockDoesNotCreateAuthority(): void {
        $f=new \App\Tests\Imperium\Runtime\Support\OnboardingAuthorityFixture();
        try {
            $wire=Wire::cognition('deepseek-v4-flash',Wire::CONFIGURATION,'Return JSON.','Public');
            $op=['schema'=>'imperium.bootstrap-prepared-operation/v1','method'=>'POST','destination'=>'https://api.deepseek.com:443/chat/completions',
                'provider'=>'deepseek','adapter'=>Wire::ADAPTER,'model'=>'deepseek-v4-flash','wire'=>$wire,
                'configuration_ref'=>['schema'=>'configuration','id'=>'config-test','digest'=>'sha256:'.str_repeat('a',64)],
                'credential_operation'=>'component-only','maximum'=>['calls'=>1,'input_tokens'=>16384,'output_tokens'=>4096,'cost_microusd'=>100000,'milliseconds'=>60000],
                'expires_at'=>$f->now+100,'authority_source'=>[]];
            $calls=0;$http=new \Symfony\Component\HttpClient\MockHttpClient(function($method,$url,$options)use(&$calls,$wire){
                ++$calls;self::assertSame('POST',$method);self::assertSame('https://api.deepseek.com/chat/completions',$url);
                self::assertSame($wire,$options['body']);self::assertSame(0,$options['max_redirects']);self::assertLessThanOrEqual(60,$options['max_duration']);
                return new \Symfony\Component\HttpClient\Response\MockResponse(json_encode(self::chat()));
            });
            $tick=1;$runtime=new \App\Imperium\Runtime\Onboarding\DeepSeek\Runtime($f->store,mock:$http,monotonicMilliseconds:static function()use(&$tick):int{return $tick++;});
            $tariff=new Tariff('synthetic-account','deepseek-v4-flash',$f->now-1,$f->now+101,440000,44000,1320000,'ceil_each_meter_microusd',0);
            $tokens=new TokenEvidence('sha256:'.hash('sha256',$wire),'synthetic-tokenizer','synthetic-framing',16384,4096,32768);
            // Component-only privileged test access. There is no positive O2 cognition entry or Augur producer.
            $result=(new \ReflectionMethod($runtime,'exchange'))->invoke($runtime,$op,'fake-component-key',$tokens,$tariff);
            self::assertSame(1,$calls);self::assertSame(\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules::hash($op),$result['operation_digest']);
            self::assertSame(51,$result['usage']['cost_microusd']);
            self::assertArrayNotHasKey('onboarding',$f->store->journal->read()['state']);
        } finally { $f->close(); }
    }
    public function testLockedNativeProxyConfigurationIsDirectWithoutAnyNetwork(): void {
        $client=new \Symfony\Component\HttpClient\NativeHttpClient();
        $proxy=(new \ReflectionMethod($client,'getProxy'))->invoke(null,'http://127.0.0.1:9',
            ['scheme'=>'https:','host'=>'api.deepseek.com'],'*');
        $context=stream_context_create();
        (new \ReflectionMethod($client,'configureHeadersAndProxy'))->invoke(null,$context,'api.deepseek.com',
            ['Accept: application/json'],$proxy,true);
        $options=stream_context_get_options($context);
        self::assertNull($options['http']['proxy']);self::assertFalse($options['http']['request_fulluri']);
        self::assertSame('api.deepseek.com',$options['ssl']['peer_name']);
        self::assertSame(['Accept: application/json'],$options['http']['header']);
    }
}
