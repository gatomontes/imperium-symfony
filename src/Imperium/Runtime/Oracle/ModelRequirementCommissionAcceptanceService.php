<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;

final readonly class ModelRequirementCommissionAcceptanceService
{
    private string$inbox;private string$occupancy;private string$snapshots;private string$acceptances;
    public function __construct(string$root){$this->inbox=$root.'/var/imperium/offices/oracle/model-requirement-inbox';$this->occupancy=$root.'/var/imperium/offices/oracle/occupancy';$this->snapshots=$root.'/var/imperium/offices/oracle/model-intelligence-snapshots';$this->acceptances=$root.'/var/imperium/offices/oracle/model-requirement-acceptances';}
    public function accept(string$commissionId,string$augurBindingId,\DateTimeImmutable$acceptedAt):array
    {
        $existing=$this->existing($commissionId);if(null!==$existing)return$existing;
        $c=$this->read($this->inbox.'/'.$commissionId.'.json','OR40_MODEL_REQUIREMENT_COMMISSION_ABSENT');$a=$this->read($this->occupancy.'/'.$augurBindingId.'.json','OR41_AUGUR_OCCUPANCY_ABSENT');$snapshotId=$c['catalogue_snapshot']['id']??'';$s=$this->read($this->snapshots.'/'.$snapshotId.'.json','OR42_REQUIREMENT_SNAPSHOT_ABSENT');
        if(!$this->ok($c)||'imperium.curia-model-requirement-commission/v1'!==($c['schema']??null)||$commissionId!==($c['commission_id']??null)||'ISSUED_PENDING_ORACLE_ACCEPTANCE'!==($c['status']??null)||new \DateTimeImmutable($c['expires_at'])<=$acceptedAt
            ||!$this->ok($a)||'imperium.oracle-augur-occupancy/v1'!==($a['schema']??null)||$augurBindingId!==($a['binding_id']??null)||($c['instance_id']??null)!==($a['instance_id']??null)||'oracle.augur'!==($a['seat']??null)||'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY'!==($a['status']??null)||true!==($a['model_requirement_commission_acceptance_authority']??null)||true===($a['selection_authority']??null)
            ||!$this->ok($s)||($c['catalogue_snapshot']['digest']??null)!==($s['record_digest']??null)||'ORACLE_CANONICAL_CATALOGUE_SNAPSHOT_SEALED_NO_SELECTION_AUTHORITY'!==($s['status']??null)
        )throw new \RuntimeException('OR43_MODEL_REQUIREMENT_ACCEPTANCE_CHAIN_INVALID');
        $actor=['office'=>'oracle','seat'=>'oracle.augur','binding_id'=>$augurBindingId,'manifestation_id'=>$a['manifestation_id'],'occupancy_generation'=>$a['occupancy_generation']];$id='model-requirement-acceptance-'.substr(hash('sha256',CanonicalJson::encode([$commissionId,$c['record_digest'],$actor,$acceptedAt->format(DATE_ATOM)])),0,20);
        return$this->save($id,['schema'=>'imperium.oracle-model-requirement-acceptance/v1','acceptance_id'=>$id,'instance_id'=>$c['instance_id'],'commission'=>['id'=>$commissionId,'digest'=>$c['record_digest']],'catalogue_snapshot'=>$c['catalogue_snapshot'],'target'=>$c['target'],'criteria'=>$c['criteria'],'actor'=>$actor,'accepted_at'=>$acceptedAt->format(DATE_ATOM),'expires_at'=>$c['expires_at'],'status'=>'CURIA_MODEL_REQUIREMENT_COMMISSION_ACCEPTED_PENDING_ORACLE_EVALUATION','evaluation_case_opening_authority'=>true,'criteria_reinterpretation_authority'=>false,'scope_expansion_authority'=>false,'evaluation_authority'=>false,'research_authority'=>false,'recommendation_authority'=>false,'selection_authority'=>false,'model_assignment_authority'=>false,'profile_mutation_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'sealed'=>true]);
    }
    private function existing(string$c):?array{if(!is_dir($this->acceptances))return null;foreach(glob($this->acceptances.'/model-requirement-acceptance-*.json')?:[]as$p){$r=$this->read($p,'OR44_MODEL_REQUIREMENT_ACCEPTANCE_FAILED');if($c===($r['commission']['id']??null)){if(!$this->ok($r))throw new \RuntimeException('OR44_MODEL_REQUIREMENT_ACCEPTANCE_FAILED');return$r;}}return null;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string$id,array$r):array{if(!is_dir($this->acceptances)&&!mkdir($this->acceptances,0770,true)&&!is_dir($this->acceptances))throw new \RuntimeException('OR44_MODEL_REQUIREMENT_ACCEPTANCE_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->acceptances.'/'.$id.'.json';if(is_file($p))return$this->read($p,'OR44_MODEL_REQUIREMENT_ACCEPTANCE_FAILED');file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
