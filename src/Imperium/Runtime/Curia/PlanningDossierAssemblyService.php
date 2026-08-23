<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class PlanningDossierAssemblyService
{
    private const array DISCLOSURES=['material_facts','assumptions','unknowns','dependencies','personnel','tools_credentials_data','external_operations','cost_time_retention_limits','risks_contingencies_fallbacks','evidence_provenance_reporting','expiry_revocation_reauthorization'];
    private string $decisions;private string $dossiers;
    public function __construct(private ProceedingStore$store,string$root){$this->decisions=$root.'/var/imperium/offices/curia/model-selection-planning-decisions';$this->dossiers=$root.'/var/imperium/offices/curia/planning-dossiers';}

    public function assemble(string$proceedingId,int$turnSequence,array$modelDecisionIds,array$disclosures,\DateTimeImmutable$assembledAt):array
    {
        $existing=$this->existing($proceedingId,$turnSequence);if(null!==$existing)return$existing;$proceeding=$this->store->find($proceedingId);$turn=$this->store->turn($proceedingId,$turnSequence);
        if(null===$proceeding||null===$turn||'MISSION_PLAN_DRAFTED'!==($turn['seneschal']['disposition']??null)||!is_array($turn['seneschal']['mission_plan']??null)||array_keys($disclosures)!==self::DISCLOSURES||!$this->disclosures($disclosures)||!$this->strings($modelDecisionIds,true)||array_values(array_unique($modelDecisionIds))!==$modelDecisionIds)throw new \RuntimeException('C246_PLANNING_DOSSIER_INPUT_INVALID');
        $bindings=[];$decisionRefs=[];foreach($modelDecisionIds as$id){$d=$this->read($this->decisions.'/'.$id.'.json','C247_MODEL_SELECTION_DECISION_ABSENT');if(!$this->ok($d)||'imperium.curia-model-selection-planning-decision/v1'!==($d['schema']??null)||'CURIA_PROPOSED_MODEL_BINDING_PENDING_PLANNING_DOSSIER_ASSEMBLY'!==($d['status']??null)||true!==($d['planning_dossier_inclusion_required']??null)||true!==($d['proposed_model_binding']['planning_only']??null)||'PROPOSED_MODEL_BINDING_PENDING_PLAN_AUTHORIZATION'!==($d['proposed_model_binding']['status']??null))throw new \RuntimeException('C248_MODEL_SELECTION_DECISION_INVALID');$decisionRefs[$id]=['id'=>$id,'digest'=>$d['record_digest']];$bindings[$id]=$d['proposed_model_binding'];}ksort($decisionRefs,SORT_STRING);ksort($bindings,SORT_STRING);
        $source=['proceeding_id'=>$proceedingId,'instance_id'=>$proceeding['instance_id'],'turn_sequence'=>$turnSequence,'turn_digest'=>$turn['record_digest']];$id='curia-planning-dossier-'.substr(hash('sha256',CanonicalJson::encode([$source,$turn['seneschal']['mission_plan'],$turn['resource_demands']??[],$decisionRefs,$bindings,$disclosures])),0,20);$reviewId='imperator-planning-dossier-review-authority-'.substr(hash('sha256',CanonicalJson::encode([$id,$source,$decisionRefs,$disclosures])),0,20);
        return$this->save($id,['schema'=>'imperium.curia-planning-dossier/v1','dossier_id'=>$id,'instance_id'=>$proceeding['instance_id'],'source_plan'=>$source,'mission_plan'=>$turn['seneschal']['mission_plan'],'resource_demands'=>$turn['resource_demands']??[],'model_selection_decisions'=>$decisionRefs,'proposed_model_bindings'=>$bindings,'disclosures'=>$disclosures,'assembled_at'=>$assembledAt->format(DATE_ATOM),'assembled_by'=>['kind'=>'mechanical-service','office'=>'curia','service'=>'planning-dossier-assembly'],'imperator_review_authority'=>['authority_id'=>$reviewId,'authority_single_use'=>true,'permitted_dispositions'=>['APPROVE_DOSSIER','OBJECT_RETURN_FOR_REVISION'],'review_authority'=>true,'status'=>'OPEN_PENDING_IMPERATOR_REVIEW'],'legacy_plan_turn_approval_sufficient'=>false,'dossier_approval'=>false,'resource_authority'=>false,'model_binding_authority'=>false,'model_assignment_authority'=>false,'profile_mutation_authority'=>false,'credential_release_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'status'=>'CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW','sealed'=>true]);
    }

    private function disclosures(array$d):bool{foreach($d as$v)if(!$this->strings($v,true))return false;return true;}
    private function strings(mixed$v,bool$empty=false):bool{if(!is_array($v)||(!$empty&&[]===$v))return false;foreach($v as$x)if(!is_string($x)||''===trim($x))return false;return array_values(array_unique($v))===$v;}
    private function existing(string$p,int$t):?array{if(!is_dir($this->dossiers))return null;foreach(glob($this->dossiers.'/curia-planning-dossier-*.json')?:[]as$f){$r=$this->read($f,'C249_PLANNING_DOSSIER_FAILED');if($p===($r['source_plan']['proceeding_id']??null)&&$t===($r['source_plan']['turn_sequence']??null)){if(!$this->ok($r))throw new \RuntimeException('C249_PLANNING_DOSSIER_FAILED');return$r;}}return null;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string$id,array$r):array{if(!is_dir($this->dossiers)&&!mkdir($this->dossiers,0770,true)&&!is_dir($this->dossiers))throw new \RuntimeException('C249_PLANNING_DOSSIER_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->dossiers.'/'.$id.'.json';if(is_file($p))return$this->read($p,'C249_PLANNING_DOSSIER_FAILED');file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
