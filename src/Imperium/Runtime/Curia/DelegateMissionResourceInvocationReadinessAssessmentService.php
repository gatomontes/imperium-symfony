<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Identity\OfficerClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionResourceInvocationReadinessAssessmentService
{
    private string$c;
    private string$i;
    private string$o;
    private string$g;
    private string$a;
    private DelegateMissionCommissionReadinessRecordMechanics$r;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root,
    ?DelegateMissionCommissionReadinessRecordMechanics$records=null){
        $this->c=$root.'/var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions';
        $this->i=$root.'/var/imperium/offices/curia/delegate-mission-control-intake-dispositions';
        $this->o=$root.'/var/imperium/offices/curia/occupancy';
        $this->g=$root.'/var/imperium/offices/garrison/custody';
        $this->a=$root.'/var/imperium/offices/curia/delegate-mission-resource-invocation-readiness-assessments';
        $this->r=$records??new DelegateMissionCommissionReadinessRecordMechanics($root);

    }

 public function assess(string$id,
    string$bindingId,
    \DateTimeImmutable$at):array{
        if(!preg_match('/^delegate-mission-bounded-cognition-commission-[a-f0-9]{20}$/',
        $id))throw new \InvalidArgumentException('C270_DELEGATE_MISSION_COGNITION_COMMISSION_ID_INVALID');
        $c=$this->read($this->c.'/'.$id.'.json',
        'C271_DELEGATE_MISSION_COGNITION_COMMISSION_ABSENT');
        $i=$this->read($this->i.'/'.($c['source_control_intake']['id']??'').'.json',
        'C272_DELEGATE_MISSION_CONTROL_INTAKE_ABSENT');
        $s=$this->read($this->o.'/'.$bindingId.'.json',
        'C273_DELEGATE_MISSION_SENESCHAL_ABSENT');
        $g=$this->read($this->g.'/'.($c['operational_custody']['id']??'').'.json',
        'C274_DELEGATE_MISSION_CUSTODY_ABSENT');
        foreach(glob($this->a.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'C279_DELEGATE_MISSION_READINESS_CONFLICT');
            if(($x['source_commission']['id']??null)===$id){
                if(!$this->ok($x)||
                ($x['source_commission']['digest']??null)!==($c['record_digest']??null)||
                ($x['assessor']['binding_id']??null)!==$bindingId)throw new \RuntimeException('C279_DELEGATE_MISSION_READINESS_CONFLICT');
                return$x;

            }

        }
        $auth=$c['resource_and_invocation_authorization_request_authority']??[];
        $profile=$i['manifestation']['profile']??[];
        $scope=$profile['profile_scope']??[];
        $requirements=['required_inputs'=>$c['commission_contract']['required_inputs']??null,
        'data'=>$scope['data_requirements']??null,
        'tools'=>$scope['tool_requirements']??null,
        'credentials'=>$scope['credential_requirements']??null,
        'perimeter'=>$scope['perimeter_requirements']??null];
        $modelBinding=$profile['model_binding']??null;
        if(!$this->ok($c)||
        !$this->ok($i)||
        !$this->ok($s)||
        !$this->ok($g)||
        'imperium.curia-delegate-mission-bounded-cognition-commission/v1'!==($c['schema']??null)||
        $id!==($c['commission_id']??null)||
        OfficerClass::Delegate->value!==($c['officer_class']??null)||
        'DELEGATE_MISSION_BOUNDED_COGNITION_COMMISSION_CONSTRUCTED_PENDING_RESOURCE_AND_INVOCATION_AUTHORIZATION'!==($c['status']??null)||
        true!==($c['commission_constructed']??null)||
        true!==($auth['authority_single_use']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        'curia.seneschal'!==($auth['holder']??null)||
        'PRESENT_EXACT_COMMISSION_RESOURCE_AND_INVOCATION_REQUIREMENTS'!==($auth['purpose']??null)||
        ($c['source_control_intake']['digest']??null)!==($i['record_digest']??null)||
        'imperium.curia-delegate-mission-control-intake-disposition/v1'!==($i['schema']??null)||
        'imperium.curia-seneschal-occupancy/v1'!==($s['schema']??null)||
        $bindingId!==($s['binding_id']??null)||
        ($c['instance_id']??null)!==($s['instance_id']??null)||
        'curia.seneschal'!==($s['seat']??null)||
        'ACTIVE'!==($s['status']??null)||
        true!==($s['delegate_mission_resource_invocation_readiness_assessment_authority']??null)||
        true===($s['execution_authority']??null)||
        ($c['operational_custody']['digest']??null)!==($g['record_digest']??null)||
        'DELEGATE_MISSION_DEPLOYED_BOUND'!==($g['custody_state']??null)||
        false!==($g['available']??null)||
        ($c['manifestation_id']??null)!==($g['operational_custodian']['manifestation_id']??null)||
        !is_array($requirements['required_inputs'])||
        !is_array($requirements['data'])||
        !is_array($requirements['tools'])||
        !is_array($requirements['credentials'])||
        !is_array($requirements['perimeter'])||
        null!==$modelBinding)throw new \RuntimeException('C275_DELEGATE_MISSION_READINESS_CHAIN_INVALID');
        $actor=['seat'=>'curia.seneschal',
        'binding_id'=>$bindingId,
        'binding_digest'=>$s['record_digest'],
        'manifestation_id'=>$s['manifestation_id'],
        'occupancy_generation'=>$s['occupancy_generation']];
        $aid='delegate-mission-resource-invocation-readiness-assessment-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $c['record_digest'],
        $i['record_digest'],
        $g['record_digest'],
        $actor,
        $requirements,
        'MODEL_BINDING_ABSENT'])),
        0,
        20);
        $oracle='delegate-mission-oracle-model-requirement-commission-authority-'.substr(hash('sha256',
        CanonicalJson::encode([$aid,
        $c['record_digest'],
        $requirements])),
        0,
        20);
        return$this->save($aid,
        ['schema'=>'imperium.curia-delegate-mission-resource-invocation-readiness-assessment/v1',
        'assessment_id'=>$aid,
        'instance_id'=>$c['instance_id'],
        'officer_class'=>OfficerClass::Delegate->value,
        'assessor'=>$actor,
        'source_commission'=>['id'=>$id,
        'digest'=>$c['record_digest']],
        'source_control_intake'=>['id'=>$i['disposition_id'],
        'digest'=>$i['record_digest']],
        'source_binding'=>$c['source_binding'],
        'operational_custody'=>['id'=>$g['custody_id'],
        'digest'=>$g['record_digest'],
        'state'=>$g['custody_state'],
        'available'=>$g['available'],
        'custodian'=>$g['operational_custodian']],
        'seat'=>$c['seat'],
        'manifestation_id'=>$c['manifestation_id'],
        'commission_contract'=>$c['commission_contract'],
        'resource_requirements'=>$requirements,
        'model_binding_status'=>'MODEL_BINDING_ABSENT',
        'provider_invocation_readiness'=>'BLOCKED_PENDING_EXACT_MODEL_BINDING',
        'request_presentation_authority'=>['id'=>$auth['authority_id'],
        'consumed'=>true,
        'continuing_authority'=>false],
        'assessed_at'=>$at->format(DATE_ATOM),
        'status'=>'DELEGATE_MISSION_RESOURCE_REQUIREMENTS_ASSESSED_PENDING_ORACLE_MODEL_REQUIREMENT_COMMISSION',
        'oracle_model_requirement_commission_authority'=>['authority_id'=>$oracle,
        'authority_single_use'=>true,
        'authority_exercisable'=>true,
        'holder'=>'curia.seneschal',
        'destination'=>'oracle.augur',
        'purpose'=>'COMMISSION_EXACT_MODEL_REQUIREMENT_RESOLUTION_FOR_DELEGATE_MISSION_TURN_ONE',
        'consumed'=>false,
        'continuing_authority'=>false],
        'resource_authorization_request_authority'=>false,
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
        return$this->r->read($p,
        $e);

    }
    private function ok(array$r):bool{
        return$this->r->isIntact($r);

    }
    private function save($id,
    array$r):array{
        return$this->r->saveReadiness($id,
        $r);

    }

}

