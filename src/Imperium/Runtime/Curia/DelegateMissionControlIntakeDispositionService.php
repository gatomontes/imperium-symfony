<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionControlIntakeDispositionService
{
    private string$a;
    private string$o;
    private string$c;
    private string$d;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root){
        $this->a=$root.'/var/imperium/mission/delegate-mission-runtime-activations';
        $this->o=$root.'/var/imperium/offices/curia/occupancy';
        $this->c=$root.'/var/imperium/offices/garrison/custody';
        $this->d=$root.'/var/imperium/offices/curia/delegate-mission-control-intake-dispositions';

    }

 public function decide(string$id,
    string$bindingId,
    string$disposition,
    string$rationale,
    \DateTimeImmutable$at):array{
        if(!preg_match('/^delegate-mission-runtime-activation-[a-f0-9]{20}$/',
        $id))throw new \InvalidArgumentException('C250_DELEGATE_MISSION_ACTIVATION_ID_INVALID');
        $disposition=strtoupper(trim($disposition));
        $rationale=trim($rationale);
        if(!in_array($disposition,
        ['ACCEPTED',
        'REFUSED',
        'RETURNED_FOR_REVISION',
        'DEFERRED'],
        true)||
        ''===$rationale)throw new \InvalidArgumentException('C251_DELEGATE_MISSION_CONTROL_INTAKE_DISPOSITION_INVALID');
        $a=$this->read($this->a.'/'.$id.'.json',
        'C252_DELEGATE_MISSION_ACTIVATION_ABSENT');
        $s=$this->read($this->o.'/'.$bindingId.'.json',
        'C253_DELEGATE_MISSION_SENESCHAL_ABSENT');
        $c=$this->read($this->c.'/'.($a['operational_custody']['id']??'').'.json',
        'C254_DELEGATE_MISSION_CUSTODY_ABSENT');
        foreach(glob($this->d.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'C259_DELEGATE_MISSION_CONTROL_INTAKE_CONFLICT');
            if(($x['source_activation']['id']??null)===$id){
                if(($x['source_activation']['digest']??null)!==($a['record_digest']??null)||
                ($x['seneschal']['binding_id']??null)!==$bindingId||
                ($x['disposition']??null)!==$disposition||
                ($x['rationale']??null)!==$rationale)throw new \RuntimeException('C259_DELEGATE_MISSION_CONTROL_INTAKE_CONFLICT');
                return$x;

            }

        }
        $auth=$a['mission_control_intake_authority']??[];
        if(!$this->ok($a)||
        !$this->ok($s)||
        !$this->ok($c)||
        'imperium.conscription-delegate-mission-runtime-activation/v1'!==($a['schema']??null)||
        $id!==($a['activation_id']??null)||
        OfficerClass::Delegate->value!==($a['officer_class']??null)||
        'DELEGATE_MISSION_RUNTIME_ACTIVE_PENDING_MISSION_CONTROL_INTAKE'!==($a['status']??null)||
        true!==($a['runtime_active']??null)||
        true!==($auth['authority_single_use']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        'curia.seneschal'!==($auth['holder']??null)||
        'INTAKE_EXACT_ACTIVE_DELEGATE_MISSION_FOR_BOUNDED_CONTROL'!==($auth['purpose']??null)||
        'imperium.curia-seneschal-occupancy/v1'!==($s['schema']??null)||
        $bindingId!==($s['binding_id']??null)||
        ($a['instance_id']??null)!==($s['instance_id']??null)||
        'curia.seneschal'!==($s['seat']??null)||
        'ACTIVE'!==($s['status']??null)||
        true!==($s['delegate_mission_control_intake_disposition_authority']??null)||
        true===($s['execution_authority']??null)||
        ($a['operational_custody']['digest']??null)!==($c['record_digest']??null)||
        'DELEGATE_MISSION_DEPLOYED_BOUND'!==($c['custody_state']??null)||
        false!==($c['available']??null)||
        ($a['seat']??null)!==($c['operational_custodian']['seat']??null)||
        ($a['manifestation_id']??null)!==($c['operational_custodian']['manifestation_id']??null))throw new \RuntimeException('C255_DELEGATE_MISSION_CONTROL_INTAKE_CHAIN_INVALID');
        $accepted='ACCEPTED'===$disposition;
        $actor=['seat'=>'curia.seneschal',
        'binding_id'=>$bindingId,
        'binding_digest'=>$s['record_digest'],
        'manifestation_id'=>$s['manifestation_id'],
        'occupancy_generation'=>$s['occupancy_generation']];
        $did='delegate-mission-control-intake-disposition-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $a['record_digest'],
        $actor,
        $disposition,
        $rationale])),
        0,
        20);
        $commission=$accepted?'delegate-mission-cognition-commission-construction-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$did,
        $a['record_digest'],
        $actor])),
        0,
        20):null;
        return$this->save($did,
        ['schema'=>'imperium.curia-delegate-mission-control-intake-disposition/v1',
        'disposition_id'=>$did,
        'instance_id'=>$a['instance_id'],
        'officer_class'=>OfficerClass::Delegate->value,
        'seneschal'=>$actor,
        'source_activation'=>['id'=>$id,
        'digest'=>$a['record_digest']],
        'source_binding'=>$a['source_binding'],
        'operational_custody'=>$a['operational_custody'],
        'seat'=>$a['seat'],
        'manifestation_id'=>$a['manifestation_id'],
        'occupancy_generation'=>$a['occupancy_generation'],
        'manifestation'=>$a['manifestation'],
        'mission_use'=>$a['mission_use'],
        'intake_authority'=>['id'=>$auth['authority_id'],
        'consumed'=>true,
        'continuing_authority'=>false],
        'disposition'=>$disposition,
        'rationale'=>$rationale,
        'decided_at'=>$at->format(DATE_ATOM),
        'status'=>$accepted?'DELEGATE_MISSION_CONTROL_ACCEPTED_PENDING_BOUNDED_COGNITION_COMMISSION_CONSTRUCTION':'DELEGATE_MISSION_CONTROL_NOT_ACCEPTED',
        'mission_control_accepted'=>$accepted,
        'cognition_commission_construction_authority'=>$accepted?['authority_id'=>$commission,
        'authority_single_use'=>true,
        'authority_exercisable'=>true,
        'holder'=>'curia.seneschal',
        'purpose'=>'CONSTRUCT_ONE_EXACT_BOUNDED_DELEGATE_MISSION_COGNITION_COMMISSION',
        'consumed'=>false,
        'continuing_authority'=>false]:null,
        'operational_use_permitted'=>false,
        'cognition_authority'=>false,
        'provider_invocation_authority'=>false,
        'data_access_authority'=>false,
        'tool_use_authority'=>false,
        'credential_use_authority'=>false,
        'perimeter_crossing_authority'=>false,
        'external_action_authority'=>false,
        'execution_authority'=>false,
        'continuing_turn_authority'=>false,
        'return_authority'=>false,
        'unbinding_authority'=>false,
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
        if(!is_dir($this->d)&&
        !mkdir($this->d,
        0770,
        true)&&
        !is_dir($this->d))throw new \RuntimeException('C258_DELEGATE_MISSION_CONTROL_INTAKE_FAILED');
        $r['record_digest']=hash('sha256',
        CanonicalJson::encode($r));
        $p=$this->d.'/'.$id.'.json';
        if(is_file($p)){
            $x=$this->read($p,
            'C259_DELEGATE_MISSION_CONTROL_INTAKE_CONFLICT');
            if($x!==$r)throw new \RuntimeException('C259_DELEGATE_MISSION_CONTROL_INTAKE_CONFLICT');
            return$x;

        }
        file_put_contents($p,
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX);
        return$r;

    }

}

