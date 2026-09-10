<?php
declare(strict_types=1);
namespace App\Tests\Imperium\Runtime\Support;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Enrollment,Admission,Resolver,Rules,SelectedPolicy};

/** Synthetic keys, public originals and deployment confirmation, isolated from installation. */
final class OnboardingAuthorityFixture
{
    public string $root; public int $now=1800000000; public string $secret; public string $public;
    public AuthorityStore $store; public array $sources=[]; private int $serial=0;
    public function __construct() {
        $this->root=sys_get_temp_dir().'/imperium-o2-test-'.bin2hex(random_bytes(8)); mkdir($this->root);
        $key=sodium_crypto_sign_keypair(); $this->secret=sodium_crypto_sign_secretkey($key); $this->public=sodium_crypto_sign_publickey($key);
        $clock=new class($this) implements Clock { public function __construct(private OnboardingAuthorityFixture $f) {} public function now():\DateTimeImmutable { return new \DateTimeImmutable('@'.$this->f->now); } };
        $this->store=new AuthorityStore($this->root,$clock,'instance-test','citadel-test','operator-test',str_repeat('a',40));
    }
    public function close():void {
        $resolved=realpath($this->root); $base=realpath(sys_get_temp_dir());
        if ($resolved===false || $base===false || !str_starts_with(strtolower($resolved),strtolower($base.DIRECTORY_SEPARATOR).'imperium-o2-test-')) { throw new \RuntimeException('Unsafe test cleanup'); }
        (new \Symfony\Component\Filesystem\Filesystem())->remove($resolved);
    }
    public static function json(mixed $v):string { return CanonicalJson::encode($v); }
    public function head():array { $f=$this->store->journal->read(); return ['generation'=>$f['generation'],'digest'=>$f['record_digest']]; }
    public function enrollment():array {
        return $this->store->make('imperium.bootstrap-enrollment/v1','enrollment-test',[
            'public_key'=>base64_encode($this->public),'fingerprint'=>'sha256:'.hash('sha256',$this->public),
            'issuer'=>['kind'=>'operator','id'=>'operator-test'],'competence'=>'OPERATOR_BOOTSTRAP_POLICY',
            'effects'=>array_values(array_diff(Rules::EFFECTS,Rules::UNSUPPORTED)),
            'not_before'=>$this->now-10,'expires_at'=>$this->now+10000,'custody_receipt'=>'Synthetic independent administrator ceremony']);
    }
    public function enroll():array { return (new Enrollment($this->store))->enroll(self::json($this->enrollment()),'sha256:'.hash('sha256',$this->public),$this->head()); }
    public function source(string $kind,mixed $content):array {
        $content=is_string($content)?$content:self::json($content);
        $h=$this->store->make('imperium.bootstrap-source/v1','source-'.str_pad((string)++$this->serial,8,'0',STR_PAD_LEFT),[
            'kind'=>$kind,'content'=>$content,'content_digest'=>'sha256:'.hash('sha256',$content),'limitations'=>'SYNTHETIC: not installed authority, entitlement or provider evidence']);
        $this->sources[Rules::key(Rules::reference($h))]=$h; return Rules::reference($h);
    }
    public function sign(array $h,string $effect,?array $policy=null,?string $nonce=null):array {
        $p=['schema'=>'imperium.operator-bootstrap-act/v1','domain'=>'IMPERIUM_OPERATOR_BOOTSTRAP_V1','instance_id'=>'instance-test','citadel_id'=>'citadel-test',
            'trust_fingerprint'=>'sha256:'.hash('sha256',$this->public),'issuer'=>['kind'=>'operator','id'=>'operator-test'],
            'effect'=>$effect,'object_digest'=>Rules::hash($h),'policy_ref'=>$policy,'expected_head'=>$this->head(),'issued_at'=>$this->now,'expires_at'=>$this->now+1800,'nonce'=>$nonce??bin2hex(random_bytes(24))];
        return ['payload'=>$p,'signature'=>base64_encode(sodium_crypto_sign_detached(self::json($p),$this->secret))];
    }
    public function resign(array $envelope):array { $envelope['signature']=base64_encode(sodium_crypto_sign_detached(self::json($envelope['payload']),$this->secret)); return $envelope; }
    public function policy():array {
        $this->sources=[];
        $generic=$this->source('public-original','Synthetic preserved original');
        $adapter=$this->source('adapter-description','Synthetic fixed DeepSeek adapter; no runtime adapter supplied');
        $credential=$this->source('credential-binding',['authentication'=>'api-key','provider'=>'deepseek','opaque_binding'=>'public-test-binding']);
        $config=$this->source('request-configuration',['max_tokens'=>4096,'stream'=>false,'temperature'=>0,'thinking'=>['type'=>'disabled'],'response_format'=>['type'=>'json_object'],'message_roles'=>['system','user']]);
        $workload=$this->source('workload',['original_sha256'=>'sha256:0811784aae9263b2507ea1ade05586a64ae9ee8c550559612520a1694945212f',
            'original_bytes'=>(string)file_get_contents(dirname(__DIR__,4).'/docs/provider-onboarding/o0-workloads.json'),'groups'=>['W1','W2','W3']]);
        $predicates=[...\App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim::PREDICATES,'profile.fits'];
        $required=$this->source('requirements',['selection_rule'=>'role_capacity_middle_tier_v1','required_predicates'=>$predicates]);
        $expected=$this->source('expected-assignments',[
            ['role'=>'courtyard.courtthane','generation'=>0,'binding_ref'=>null],['role'=>'clavium.locksmith','generation'=>0,'binding_ref'=>null]]);
        $candidates=[];
        foreach (['deepseek-v4-flash','deepseek-v4-pro'] as $model) {
            $candidates[]=['binding_ref'=>$this->source('candidate-binding',$model),'provider'=>'deepseek','model_id'=>$model,'model_version'=>'synthetic-v1','configuration_ref'=>$config,
                'adapter_ref'=>$adapter,'mapping_ref'=>$generic,'revision_pin'=>'UNAVAILABLE_ACCEPTED_ALIAS'];
        }
        $graph=json_decode(SelectedPolicy::GRAPH,true,512,JSON_THROW_ON_ERROR);
        $replace=function(mixed $v,?string $key=null)use(&$replace,$generic,$workload):mixed {
            if (!is_array($v)) { return $v; }
            if (isset($v['preparation_unresolved_ref'])) { return $v['preparation_unresolved_ref']==='issue_expiry'?$this->now+1800:$generic; }
            if (isset($v['preparation_document'])) { return $workload; }
            $out=[];
            foreach ($v as $k=>$item) {
                if ($k==='input_refs') { $out[$k]=array_map(static fn(array $s):array=>isset($s['preparation_unresolved_ref'])?['kind'=>'public_ref','ref'=>$generic]:$s,$item); }
                else { $out[$k]=$replace($item,(string)$k); }
            }
            return $out;
        };
        $graph=$replace($graph);
        foreach ($graph['effect_slots'] as &$slot) {
            if ($slot['terms_rule']['kind']==='assessed_assignment_set') { continue; }
            $terms=$this->store->make('imperium.bootstrap-proposed-terms/v1','terms-'.str_pad((string)++$this->serial,8,'0',STR_PAD_LEFT),
                ['effect'=>$slot['effect'],'terms'=>$generic,'required_completed_refs'=>[]],[$generic]);
            $this->sources[Rules::key(Rules::reference($terms))]=$terms;
            if ($slot['terms_rule']['kind']==='exact') { $slot['terms_rule']['object_ref']=Rules::reference($terms); }
            else { $slot['terms_rule']['permitted_object_refs']=[Rules::reference($terms)]; }
        } unset($slot);
        $targets=[]; foreach (['courtyard.courtthane','clavium.locksmith'] as $role) { $targets[]=['role'=>$role,'profile_ref'=>$generic,'predicate_ids'=>$predicates,'permitted_bindings'=>array_column($candidates,'binding_ref')]; }
        $b=['policy_version'=>'o2-fresh-v1','installation_mode'=>'FRESH','provider'=>'deepseek','adapter_ref'=>$adapter,'credential_ref'=>$credential,
            'candidate_bindings'=>$candidates,'targets'=>$targets,'workload_ref'=>$workload,'requirements_ref'=>$required,
            'evidence_policy'=>['freshness_ms'=>json_decode(SelectedPolicy::FRESHNESS,true,512,JSON_THROW_ON_ERROR),'data_scope'=>'PUBLIC_ONLY','retryable_failure_allowlist'=>[]],
            'limits'=>json_decode(SelectedPolicy::LIMITS,true,512,JSON_THROW_ON_ERROR),'allowed_effects'=>array_values(array_unique(array_column($graph['effect_slots'],'effect'))),
            'budget_ref'=>$generic,...$graph,'application_mode'=>'A','expected_assignments'=>$expected,'expires_at'=>$this->now+1800];
        return $this->store->make('imperium.operator-bootstrap-policy/v1','policy-'.bin2hex(random_bytes(8)),$b,array_map(Rules::reference(...),array_values($this->sources)));
    }
    public function admitPolicy(?array $h=null):array {
        $h??=$this->policy(); $e=$this->sign($h,'AUTHORIZE_BOOTSTRAP_POLICY');
        $result=(new Admission($this->store))->retain(self::json($e),self::json($h),array_map(self::json(...),array_values($this->sources)));
        return [$h,$e,$result];
    }
    public function revoke(string $kind,string $id):array {
        $h=$this->store->make('imperium.bootstrap-revocation/v1','revocation-'.bin2hex(random_bytes(8)),['target_kind'=>$kind,'target_id'=>$id,'expected_head'=>$this->head()]);
        return (new Admission($this->store))->retain(self::json($this->sign($h,'REVOKE_BOOTSTRAP')),self::json($h));
    }
    public static function authority(array $result):array { return ['kind'=>'signed_act','act_ref'=>$result['admission']['body']['act_ref'],'admission_ref'=>Rules::reference($result['admission'])]; }
}
