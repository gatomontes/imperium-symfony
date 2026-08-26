<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionResourceInvocationDecisionService
{
    private string$a;
    private string$b;
    private string$c;
    private string$d;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root){
        $this->a=$root.'/var/imperium/offices/clavium/delegate-mission-model-access-attestations';
        $this->b=$root.'/var/imperium/offices/conscription/delegate-mission-model-bindings';
        $this->c=$root.'/var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions';
        $this->d=$root.'/var/imperium/imperator/delegate-mission-resource-invocation-decisions';

    }

 public function decide(string$id,
    string$authorityId,
    string$disposition,
    string$rationale,
    \DateTimeImmutable$at):array{
        $disposition=strtoupper(trim($disposition));
        if(!in_array($disposition,
        ['AUTHORIZED',
        'REFUSED'],
        true)||
        ''===trim($rationale))throw new \InvalidArgumentException('I300_DELEGATE_RESOURCE_INVOCATION_DECISION_INVALID');
        $a=$this->read($this->a.'/'.$id.'.json',
        'I301_DELEGATE_ACCESS_ATTESTATION_ABSENT');
        foreach(glob($this->d.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'I309_DELEGATE_RESOURCE_INVOCATION_CONFLICT');
            if(($x['source_access_attestation']['id']??null)===$id)return$x;

        }
        $b=$this->read($this->b.'/'.($a['model_binding']['id']??'').'.json',
        'I302_DELEGATE_MODEL_BINDING_ABSENT');
        $c=$this->read($this->c.'/'.($b['source_commission']['id']??'').'.json',
        'I303_DELEGATE_COMMISSION_ABSENT');
        $auth=$a['imperator_resource_invocation_decision_authority']??[];
        $yes='AUTHORIZED'===$disposition;
        if(!$this->ok($a)||
        !$this->ok($b)||
        !$this->ok($c)||
        'imperium.clavium-delegate-mission-model-access-attestation/v1'!==($a['schema']??null)||
        'DELEGATE_MISSION_MODEL_ACCESS_ATTESTED_PENDING_RESOURCE_AND_INVOCATION_DECISION'!==($a['status']??null)||
        true!==($a['access_available']??null)||
        $authorityId!==($auth['authority_id']??null)||
        true!==($auth['authority_single_use']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        ($a['model_binding']['digest']??null)!==$b['record_digest']||
        ($b['source_commission']['digest']??null)!==$c['record_digest']||
        ($a['target']??null)!==($b['target']??null)||
        ($a['runtime_binding']??null)!==($b['runtime_binding']??null)||
        new \DateTimeImmutable($a['provider_access_evidence']['expires_at'])<=$at||
        true===($a['credential_released']??null)||
        true===($a['provider_invocation_authority']??null)||
        true===($c['commission_contract']['provider_invocation_allowed']??null))throw new \RuntimeException('I304_DELEGATE_RESOURCE_INVOCATION_CHAIN_INVALID');
        $did='delegate-mission-resource-invocation-decision-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $a['record_digest'],
        $authorityId,
        $disposition,
        $rationale])),
        0,
        20);
        $next=$yes?'delegate-mission-provider-invocation-activation-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$did,
        $b['record_digest'],
        $c['record_digest'],
        $a['provider_access_evidence']])),
        0,
        20):null;
        return$this->save($did,
        ['schema'=>'imperium.imperator-delegate-mission-resource-invocation-decision/v1',
        'decision_id'=>$did,
        'instance_id'=>$a['instance_id'],
        'source_access_attestation'=>['id'=>$id,
        'digest'=>$a['record_digest']],
        'source_model_binding'=>['id'=>$b['binding_id'],
        'digest'=>$b['record_digest']],
        'source_commission'=>['id'=>$c['commission_id'],
        'digest'=>$c['record_digest']],
        'target'=>$b['target'],
        'model'=>['provider_model_version'=>$b['provider_model_version'],
        'runtime_binding'=>$b['runtime_binding'],
        'configuration'=>$b['configuration']],
        'authorized_requirements'=>$yes?['required_inputs'=>$c['commission_contract']['required_inputs']]:[],
        'disposition'=>$disposition,
        'rationale'=>trim($rationale),
        'decision_authority'=>['id'=>$authorityId,
        'consumed'=>true,
        'continuing_authority'=>false],
        'decided_at'=>$at->format(DATE_ATOM),
        'status'=>$yes?'DELEGATE_MISSION_RESOURCE_AND_INVOCATION_AUTHORIZED_PENDING_SCOPED_ACTIVATION':'DELEGATE_MISSION_RESOURCE_AND_INVOCATION_REFUSED',
        'provider_invocation_activation_authority'=>$yes?['authority_id'=>$next,
        'authority_single_use'=>true,
        'authority_exercisable'=>true,
        'holder'=>'clavium.locksmith',
        'purpose'=>'ACTIVATE_ONE_EXACT_DELEGATE_PROVIDER_INVOCATION',
        'consumed'=>false,
        'continuing_authority'=>false]:null,
        'credential_released'=>false,
        'provider_invocation_authority'=>false,
        'resource_released'=>false,
        'external_action_authority'=>false,
        'execution_authority'=>false,
        'sealed'=>true]);

    }

 private function read($p,
    $e):array{
        if(!is_file($p))throw new \RuntimeException($e);
        return json_decode((string)file_get_contents($p),
        true,
        512,
        JSON_THROW_ON_ERROR);

    }
    private function ok(array$r):bool{
        $d=$r['record_digest']??null;
        unset($r['record_digest']);
        return is_string($d)&&
        hash_equals($d,
        hash('sha256',
        CanonicalJson::encode($r)));

    }
    private function save($id,
    array$r):array{
        if(!is_dir($this->d))mkdir($this->d,
        0770,
        true);
        $r['record_digest']=hash('sha256',
        CanonicalJson::encode($r));
        file_put_contents($this->d.'/'.$id.'.json',
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX);
        return$r;

    }

}

