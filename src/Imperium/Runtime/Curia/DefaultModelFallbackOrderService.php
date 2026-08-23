<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class DefaultModelFallbackOrderService
{
    private string $phases;private string $cases;private string $occupancy;private string $orders;
    public function __construct(string$root){$this->phases=$root.'/var/imperium/offices/oracle/model-eligibility-phases';$this->cases=$root.'/var/imperium/offices/oracle/model-evaluation-cases';$this->occupancy=$root.'/var/imperium/offices/curia/occupancy';$this->orders=$root.'/var/imperium/offices/curia/default-model-fallback-orders';}
    public function issue(string$phaseId,string$seneschalBindingId,string$modelRef,string$necessity,array$acknowledgedReasonCodes,\DateTimeImmutable$issuedAt):array
    {
        $existing=$this->existing($phaseId);if(null!==$existing)return$existing;$phase=$this->read($this->phases.'/'.$phaseId.'.json','C236_ELIGIBILITY_PHASE_ABSENT');$caseId=$phase['case']['id']??'';$case=$this->read($this->cases.'/'.$caseId.'.json','C237_EVALUATION_CASE_ABSENT');$o=$this->read($this->occupancy.'/'.$seneschalBindingId.'.json','C238_SENESCHAL_OCCUPANCY_ABSENT');$finding=$phase['findings'][$modelRef]??null;
        if(!$this->ok($phase)||'ORACLE_NO_ELIGIBLE_MODEL_PENDING_CURIA_FALLBACK_ORDER'!==($phase['status']??null)||true!==($phase['no_eligible_model']??null)||!$this->ok($case)||($phase['case']['digest']??null)!==($case['record_digest']??null)||!in_array($modelRef,$case['included_candidates']??[],true)||!is_array($finding)||'ELIGIBLE'===($finding['disposition']??null)||$acknowledgedReasonCodes!==($finding['reason_codes']??null)||''===trim($necessity)
            ||!$this->ok($o)||$seneschalBindingId!==($o['binding_id']??null)||($case['instance_id']??null)!==($o['instance_id']??null)||'curia.seneschal'!==($o['seat']??null)||'ACTIVE'!==($o['status']??null)||true!==($o['model_requirement_commission_authority']??null)
        )throw new \RuntimeException('C239_DEFAULT_MODEL_FALLBACK_ORDER_INVALID');
        $actor=['office'=>'curia','seat'=>'curia.seneschal','binding_id'=>$seneschalBindingId,'manifestation_id'=>$o['manifestation_id'],'occupancy_generation'=>$o['occupancy_generation']];$id='curia-default-model-fallback-order-'.substr(hash('sha256',CanonicalJson::encode([$phaseId,$phase['record_digest'],$modelRef,$necessity,$acknowledgedReasonCodes,$actor])),0,20);
        return$this->save($id,['schema'=>'imperium.curia-default-model-fallback-order/v1','order_id'=>$id,'eligibility_phase'=>['id'=>$phaseId,'digest'=>$phase['record_digest']],'case'=>$phase['case'],'model_ref'=>$modelRef,'oracle_disposition'=>$finding['disposition'],'acknowledged_reason_codes'=>$acknowledgedReasonCodes,'necessity'=>$necessity,'actor'=>$actor,'issued_at'=>$issuedAt->format(DATE_ATOM),'fallback_verification_authority'=>true,'eligibility_override'=>false,'ranking_authority'=>false,'recommendation_authority'=>false,'selection_authority'=>false,'model_assignment_authority'=>false,'profile_mutation_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'status'=>'CURIA_DEFAULT_MODEL_FALLBACK_ORDER_ISSUED_PENDING_ORACLE_VERIFICATION','sealed'=>true]);
    }
    private function existing(string$p):?array{if(!is_dir($this->orders))return null;foreach(glob($this->orders.'/curia-default-model-fallback-order-*.json')?:[]as$f){$r=$this->read($f,'C240_DEFAULT_MODEL_FALLBACK_ORDER_FAILED');if($p===($r['eligibility_phase']['id']??null)){if(!$this->ok($r))throw new \RuntimeException('C240_DEFAULT_MODEL_FALLBACK_ORDER_FAILED');return$r;}}return null;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string$id,array$r):array{if(!is_dir($this->orders)&&!mkdir($this->orders,0770,true)&&!is_dir($this->orders))throw new \RuntimeException('C240_DEFAULT_MODEL_FALLBACK_ORDER_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->orders.'/'.$id.'.json';if(is_file($p))return$this->read($p,'C240_DEFAULT_MODEL_FALLBACK_ORDER_FAILED');file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
