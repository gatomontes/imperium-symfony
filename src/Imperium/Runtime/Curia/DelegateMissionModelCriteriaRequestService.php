<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Curia;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionModelCriteriaRequestService
{
    private string$a;
    private string$o;
    private string$r;
    public function __construct(#[Autowire('%kernel.project_dir%')]string$root){
        $this->a=$root.'/var/imperium/offices/curia/delegate-mission-resource-invocation-readiness-assessments';
        $this->o=$root.'/var/imperium/offices/curia/occupancy';
        $this->r=$root.'/var/imperium/offices/curia/delegate-mission-model-criteria-requests';

    }
    public function present(string$id,
    string$bindingId,
    array$criteria,
    \DateTimeImmutable$at):array{
        if(!preg_match('/^delegate-mission-resource-invocation-readiness-assessment-[a-f0-9]{20}$/',
        $id))throw new \InvalidArgumentException('C280_DELEGATE_MODEL_READINESS_ID_INVALID');
        $a=$this->read($this->a.'/'.$id.'.json',
        'C281_DELEGATE_MODEL_READINESS_ABSENT');
        $s=$this->read($this->o.'/'.$bindingId.'.json',
        'C282_DELEGATE_MODEL_SENESCHAL_ABSENT');
        $criteria=$this->criteria($criteria);
        foreach(glob($this->r.'/*.json')?:[]as$p){
            $x=$this->read($p,
            'C289_DELEGATE_MODEL_CRITERIA_REQUEST_CONFLICT');
            if(($x['source_readiness']['id']??null)===$id)return$x;

        }
        $auth=$a['oracle_model_requirement_commission_authority']??[];
        if(!$this->ok($a)||
        !$this->ok($s)||
        'imperium.curia-delegate-mission-resource-invocation-readiness-assessment/v1'!==($a['schema']??null)||
        'DELEGATE_MISSION_RESOURCE_REQUIREMENTS_ASSESSED_PENDING_ORACLE_MODEL_REQUIREMENT_COMMISSION'!==($a['status']??null)||
        'MODEL_BINDING_ABSENT'!==($a['model_binding_status']??null)||
        true!==($auth['authority_exercisable']??null)||
        false!==($auth['consumed']??null)||
        'curia.seneschal'!==($auth['holder']??null)||
        'imperium.curia-seneschal-occupancy/v1'!==($s['schema']??null)||
        $bindingId!==($s['binding_id']??null)||
        ($a['instance_id']??null)!==($s['instance_id']??null)||
        'ACTIVE'!==($s['status']??null)||
        true!==($s['delegate_mission_model_criteria_request_authority']??null)||
        true===($s['execution_authority']??null))throw new \RuntimeException('C283_DELEGATE_MODEL_CRITERIA_REQUEST_CHAIN_INVALID');
        $actor=['seat'=>'curia.seneschal',
        'binding_id'=>$bindingId,
        'binding_digest'=>$s['record_digest'],
        'manifestation_id'=>$s['manifestation_id'],
        'occupancy_generation'=>$s['occupancy_generation']];
        $rid='delegate-mission-model-criteria-request-'.substr(hash('sha256',
        CanonicalJson::encode([$id,
        $a['record_digest'],
        $actor,
        $criteria])),
        0,
        20);
        return$this->save($rid,
        ['schema'=>'imperium.curia-delegate-mission-model-criteria-request/v1',
        'request_id'=>$rid,
        'instance_id'=>$a['instance_id'],
        'requester'=>$actor,
        'recipient'=>['kind'=>'imperator',
        'id'=>'imperator-development-root'],
        'source_readiness'=>['id'=>$id,
        'digest'=>$a['record_digest']],
        'source_commission'=>$a['source_commission'],
        'source_binding'=>$a['source_binding'],
        'operational_custody'=>$a['operational_custody'],
        'target'=>['type'=>'MISSION_FUNCTION',
        'id'=>$a['seat'],
        'mission_id'=>$a['source_commission']['id']],
        'commission_contract'=>$a['commission_contract'],
        'resource_requirements'=>$a['resource_requirements'],
        'proposed_criteria'=>$criteria,
        'criteria_proposal_authority'=>['id'=>$auth['authority_id'],
        'consumed'=>true,
        'continuing_authority'=>false],
        'presented_at'=>$at->format(DATE_ATOM),
        'status'=>'DELEGATE_MISSION_MODEL_CRITERIA_PRESENTED_PENDING_IMPERATOR_DECISION',
        'model_selection_authority'=>false,
        'model_assignment_authority'=>false,
        'provider_invocation_authority'=>false,
        'resource_authority'=>false,
        'execution_authority'=>false,
        'sealed'=>true]);

    }
    private function criteria(array$c):array{
        $k=['cognitive_task',
        'required_capabilities',
        'prohibited_capabilities',
        'required_tools',
        'minimum_context_tokens',
        'data_classification',
        'data_residency',
        'permitted_providers',
        'max_cost_per_million_tokens',
        'max_latency_ms',
        'minimum_reliability',
        'fallback_policy',
        'substitution_policy',
        'evaluation_rubric',
        'minimum_evidence_sources'];
        if(array_keys($c)!==$k||
        !is_string($c['cognitive_task'])||
        ''===trim($c['cognitive_task'])||
        !is_array($c['required_capabilities'])||
        []===$c['required_capabilities']||
        !is_array($c['permitted_providers'])||
        []===$c['permitted_providers']||
        !is_int($c['minimum_context_tokens'])||
        $c['minimum_context_tokens']<1||
        !is_int($c['max_cost_per_million_tokens'])||
        $c['max_cost_per_million_tokens']<0||
        !is_int($c['max_latency_ms'])||
        $c['max_latency_ms']<1||
        !is_float($c['minimum_reliability'])||
        $c['minimum_reliability']<0||
        $c['minimum_reliability']>1||
        'SILENT_SUBSTITUTION_PROHIBITED'!==$c['substitution_policy']||
        !is_array($c['evaluation_rubric'])||
        []===$c['evaluation_rubric']||
        !is_int($c['minimum_evidence_sources'])||
        $c['minimum_evidence_sources']<1)throw new \InvalidArgumentException('C284_DELEGATE_MODEL_CRITERIA_INVALID');
        return$c;

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
        if(!is_dir($this->r))mkdir($this->r,
        0770,
        true);
        $r['record_digest']=hash('sha256',
        CanonicalJson::encode($r));
        file_put_contents($this->r.'/'.$id.'.json',
        json_encode($r,
        JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",
        LOCK_EX);
        return$r;

    }

}

