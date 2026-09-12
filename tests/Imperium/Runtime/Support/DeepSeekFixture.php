<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Rules as R,Admission,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\StateMigration;
use App\Imperium\Runtime\Onboarding\DeepSeek\{Runtime,Wire,AccessAdapter,EnvelopeStore,KeySource,EvidenceVerifier};
use Symfony\Component\HttpClient\{MockHttpClient,Response\MockResponse};

/** Purpose-built synthetic grant/account originals signed by the real B0 producer. */
final class DeepSeekFixture
{
    public OnboardingAuthorityFixture $f;
    public Runtime $runtime;
    public AccessAdapter $adapter;
    public EnvelopeStore $envelopes;
    public KeySource $keys;
    public array $policy;
    public array $grant;
    public array $requests=[];
    public ?array $last=null;
    public int $tick=100;
    public ?\Closure $keyHook=null;
    private int $serial=0;
    public function __construct(?\Closure $respond=null,bool $missingEvidence=false,?\Closure $change=null,?\Closure $storageHook=null)
    {
        $this->f=$f=new OnboardingAuthorityFixture(); $f->enroll(); $p=$f->policy();
        $adapter=$f->source('adapter-description',['schema'=>'imperium.deepseek-adapter/v1','adapter'=>Wire::ADAPTER]);
        $credential=$f->source('credential-binding',['authentication'=>'api-key','provider'=>'deepseek','opaque_binding'=>'synthetic-generation']);
        $custody=$f->source('deployment-custody',['schema'=>'imperium.deepseek-custody/v1','custody'=>Runtime::CUSTODY,'credential_binding_ref'=>$credential]);
        $account=$f->source('deepseek-account-observation',['schema'=>'imperium.synthetic-deepseek-account/v1',
            'account_scope'=>'synthetic-account','credential_binding_ref'=>$credential,'method'=>'GET',
            'destination'=>'https://api.deepseek.com:443/models','cost_microusd'=>0,'input_tokens'=>0,'output_tokens'=>0,
            'not_before'=>$f->now-1,'expires_at'=>$f->now+600]);
        $limits=['calls'=>13,'input_tokens'=>196608,'output_tokens'=>49152,'cost_microusd'=>1200000,'milliseconds'=>730000];
        $root=$f->source('budget-root',['schema'=>'imperium.bootstrap-budget-root/v1','limits'=>$limits]);
        $budget=$f->source('shared-budget',['schema'=>'imperium.bootstrap-budget-association/v1','lineage_ref'=>$root,'limits'=>$limits,'formation_sources'=>[]]);
        $grant=$f->source('deepseek-access-grant',['schema'=>'imperium.deepseek-access-grant/v1',
            'configuration_ref'=>$p['body']['candidate_bindings'][0]['configuration_ref'],
            'executor'=>['kind'=>'infrastructure','adapter_ref'=>$adapter,'deployment_custody_ref'=>$custody,'credential_binding_ref'=>$credential],
            'credential_operation'=>'deepseek.models.observe','account_evidence_ref'=>$account,
            'maximum'=>['calls'=>1,'input_tokens'=>0,'output_tokens'=>0,'cost_microusd'=>0,'milliseconds'=>10000],'expires_at'=>$f->now+300]);
        $this->grant=$f->sources[R::key($grant)];
        $p['body']['adapter_ref']=$adapter; $p['body']['credential_ref']=$credential; $p['body']['budget_ref']=$budget;
        foreach ($p['body']['candidate_bindings'] as &$c) { $c['adapter_ref']=$adapter; } unset($c);
        foreach ($p['body']['effect_slots'] as &$slot) {
            if ($slot['slot_id'] !== 'slot.access') { continue; }
            $term=$f->sources[R::key($slot['terms_rule']['object_ref'])]; unset($f->sources[R::key($slot['terms_rule']['object_ref'])]); unset($term['record_digest']);
            $term['body']['terms']=$grant; $term['sources']=[$grant]; $term=R::seal($term);
            $f->sources[R::key(R::reference($term))]=$term; $slot['terms_rule']['object_ref']=R::reference($term);
        } unset($slot);
        if ($change !== null) { $change($p,$f); }
        unset($p['record_digest']); $p['sources']=R::refs(array_map(R::reference(...),array_values($f->sources))); $p=R::seal($p); $this->policy=$p;
        (new Admission($f->store))->retain($f::json($f->sign($p,'AUTHORIZE_BOOTSTRAP_POLICY')),$f::json($p),array_map($f::json(...),array_values($f->sources)));
        (new StateMigration($f->store))->migrate($f->head());
        $this->keys=new class($this) implements KeySource {
            public string $version='synthetic-generation'; public bool $repeat=false; public bool $omit=false;
            public function __construct(private DeepSeekFixture $f) {}
            public function generation(): string { return $this->version; }
            public function withKey(callable $delivery): void {
                if ($this->f->keyHook !== null) { ($this->f->keyHook)(); }
                if ($this->omit) { return; }
                // Built at runtime and never logged; no actual credential is read.
                $secret=implode('-', ['synthetic','o3','sentinel','not','a','credential']);
                $delivery($secret); if ($this->repeat) { $delivery($secret); }
            }
        };
        $evidence=new class($account) implements EvidenceVerifier {
            public function __construct(private array $pinned) {}
            public function access(array $original,array $grant,int $now): void {
                R::require(R::same(R::reference($original),$this->pinned),'SYNTHETIC_ACCOUNT_PIN');
                $v=Policy::content($original,'deepseek-account-observation');
                R::object($v,['schema','account_scope','credential_binding_ref','method','destination','cost_microusd','input_tokens','output_tokens','not_before','expires_at']);
                R::require($v['schema']==='imperium.synthetic-deepseek-account/v1' && $v['account_scope']==='synthetic-account'
                    && R::same($v['credential_binding_ref'],$grant['executor']['credential_binding_ref'])
                    && $v['method']==='GET' && $v['destination']==='https://api.deepseek.com:443/models'
                    && $v['cost_microusd']===0 && $v['input_tokens']===0 && $v['output_tokens']===0
                    && $v['not_before']<=$now && $now<$v['expires_at'] && $grant['expires_at']<=$v['expires_at'],'SYNTHETIC_ACCOUNT_SCOPE');
            }
        };
        $this->adapter=new AccessAdapter($this->grant,$missingEvidence ? new \App\Imperium\Runtime\Onboarding\DeepSeek\MissingEvidence() : $evidence,$this->keys);
        mkdir($f->root.'/responses'); $this->envelopes=new EnvelopeStore($f->root.'/responses',$storageHook);
        $http=new MockHttpClient(function(string $method,string $url,array $options) use ($respond): MockResponse {
            // Keep only public transport evidence. Authentication value is checked, never copied to artifacts.
            $auth=array_values(array_filter($options['headers'],static fn(string $h): bool=>str_starts_with(strtolower($h),'authorization:')));
            if (count($auth)!==1 || !str_starts_with($auth[0],'Authorization: Bearer synthetic-')) { throw new \RuntimeException('Mock authentication missing'); }
            $safe=$options; unset($safe['headers'],$safe['normalized_headers']);
            $this->requests[]=['method'=>$method,'url'=>$url,'options'=>$safe];
            return $respond === null ? new MockResponse(self::listing()) : $respond($this);
        });
        $this->runtime=new Runtime($f->store,$this->adapter,$this->keys,$this->envelopes,$http,fn(): int=>$this->tick++);
    }
    public static function listing(): string { return '{"object":"list","data":[{"id":"deepseek-v4-flash","object":"model","created":1,"owned_by":"deepseek"}]}'; }
    public function request(?string $step=null,?string $id=null): array {
        $h=$this->f->head(); if ($h['digest']!==null) { $h['digest']='sha256:'.$h['digest']; }
        return ['schema'=>'imperium.provider-onboarding-request/v2','sequence_id'=>'sequence-test','command_id'=>$id??'command-'.str_pad((string)++$this->serial,8,'0',STR_PAD_LEFT),
            'mode'=>'advance','instance_id'=>'instance-test','policy_ref'=>['id'=>$this->policy['id'],'version'=>$this->policy['body']['policy_version'],'digest'=>$this->policy['record_digest']],
            'expected_head'=>$h,'predecessor_ref'=>$this->last,'step_id'=>$step,'evidence_refs'=>[]];
    }
    public function advance(?string $step=null): array { $r=$this->runtime->advance($this->f::json($this->request($step))); $this->last=$r['result_ref']; return $r; }
    public function ready(): void { $this->advance(); $this->advance('configure'); $this->advance('admit-evidence'); }
    public function claim(): array { return array_values($this->f->store->journal->read()['state']['onboarding']['claims'])[0]; }
    public function close(): void { $this->f->close(); }
}
