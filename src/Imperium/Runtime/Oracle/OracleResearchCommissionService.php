<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\OutboundExecutionMode;
use App\Imperium\Runtime\LaCortine\OutboundRequest;

final readonly class OracleResearchCommissionService
{
    private string $directory;
    public function __construct(string $projectDir){$this->directory=$projectDir.'/var/imperium/offices/oracle/research-commissions';}

    public function issue(string$instanceId,array$scope,\DateTimeImmutable$issuedAt,\DateTimeImmutable$expiresAt,array$authorization,array$augur):array
    {
        $this->assertAuthorization($instanceId,$authorization);$this->assertAugur($instanceId,$augur);
        if(array_keys($scope)!==['providers','destinations','claim_subjects','tool_ids','capability_ids','max_sources','budget_units']
            ||!$this->strings($scope['providers'])||!$this->strings($scope['destinations'])||!$this->strings($scope['claim_subjects'])
            ||!$this->strings($scope['tool_ids'])||!$this->strings($scope['capability_ids'])||!is_int($scope['max_sources'])||$scope['max_sources']<1
            ||!is_int($scope['budget_units'])||$scope['budget_units']<1||$expiresAt<=$issuedAt
        )throw new \InvalidArgumentException('OR25_RESEARCH_SCOPE_INVALID');
        $id='oracle-research-'.substr(hash('sha256',CanonicalJson::encode([$instanceId,$scope,$authorization['authorization_id'],$augur['binding_id'],$issuedAt->format(DATE_ATOM),$expiresAt->format(DATE_ATOM)])),0,20);
        return$this->persist($id,['schema'=>'imperium.oracle-research-commission/v1','commission_id'=>$id,'instance_id'=>$instanceId,
            'authorization'=>['id'=>$authorization['authorization_id'],'digest'=>$authorization['record_digest'],'issuer'=>'imperator'],
            'recipient'=>['office'=>'oracle','seat'=>'oracle.augur','binding_id'=>$augur['binding_id'],'manifestation_id'=>$augur['manifestation_id'],'occupancy_generation'=>$augur['occupancy_generation']],
            'scope'=>$scope,'issued_at'=>$issuedAt->format(DATE_ATOM),'expires_at'=>$expiresAt->format(DATE_ATOM),'expected_return_contract'=>'imperium.oracle-research-return/v1',
            'external_research_authority'=>true,'external_research_authority_exercisable'=>true,'authority_single_use'=>true,
            'credential_use_authority'=>false,'provider_invocation_authority'=>false,'eligibility_authority'=>false,'recommendation_authority'=>false,'selection_authority'=>false,'model_assignment_authority'=>false,'deployment_authority'=>false,
            'status'=>'ORACLE_RESEARCH_COMMISSION_ISSUED_PENDING_LA_CORTINE_RETURN','sealed'=>true]);
    }

    public function outboundRequest(array$commission):OutboundRequest
    {
        if(!$this->digestMatches($commission)||'imperium.oracle-research-commission/v1'!==($commission['schema']??null)||true!==($commission['external_research_authority_exercisable']??null))throw new \RuntimeException('OR26_RESEARCH_COMMISSION_INVALID');
        return new OutboundRequest('oracle-research-request-'.$commission['commission_id'],$commission['authorization']['id'],$commission['authorization']['digest'],$commission['commission_id'],'external.research','Acquire exact model evidence for Oracle',OutboundExecutionMode::Sortie,$commission['scope']['destinations'],$commission['scope']['tool_ids'],$commission['scope']['capability_ids'],hash('sha256',CanonicalJson::encode($commission['scope'])),$commission['expected_return_contract'],new \DateTimeImmutable($commission['expires_at']));
    }

    private function assertAuthorization(string$i,array$a):void{if(!$this->digestMatches($a)||'imperium.oracle-research-authorization/v1'!==($a['schema']??null)||$i!==($a['instance_id']??null)||'imperator'!==($a['issuer']??null)||true!==($a['oracle_research_commission_authority']??null)||true===($a['model_selection_authority']??null)||true!==($a['sealed']??null))throw new \RuntimeException('OR27_RESEARCH_AUTHORIZATION_INVALID');}
    private function assertAugur(string$i,array$a):void{if($i!==($a['instance_id']??null)||'oracle.augur'!==($a['seat']??null)||!in_array($a['status']??null,['ACTIVE','ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY'],true)||true!==($a['model_intelligence_stewardship_authority']??null)||true===($a['model_research_authority']??null)||true===($a['model_selection_authority']??null))throw new \RuntimeException('OR28_AUGUR_RESEARCH_RECIPIENT_INVALID');}
    private function strings(mixed$v):bool{return is_array($v)&&[]!==$v&&[]===array_filter($v,static fn($x)=>!is_string($x)||''===trim($x));}
    private function digestMatches(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,'sha256:'.hash('sha256',CanonicalJson::encode($r)));}
    private function persist(string$id,array$r):array{$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));if(!is_dir($this->directory)&&!mkdir($this->directory,0770,true)&&!is_dir($this->directory))throw new \RuntimeException('OR29_RESEARCH_COMMISSION_PERSISTENCE_FAILED');$p=$this->directory.'/'.$id.'.json';if(is_file($p)){return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
