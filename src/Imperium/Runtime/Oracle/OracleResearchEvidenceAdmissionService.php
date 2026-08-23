<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\AdmittedExternalArtifact;

final readonly class OracleResearchEvidenceAdmissionService
{
    private string$commissions;private string$evidence;private string$receipts;
    public function __construct(string$projectDir){$this->commissions=$projectDir.'/var/imperium/offices/oracle/research-commissions';$this->evidence=$projectDir.'/var/imperium/offices/oracle/admitted-model-evidence';$this->receipts=$projectDir.'/var/imperium/offices/oracle/research-receipts';}

    public function admit(string$commissionId,AdmittedExternalArtifact$artifact,array$augur):array
    {
        $existing=$this->receiptFor($commissionId);
        if(null!==$existing){if($artifact->artifactId===($existing['lazaretto_artifact_id']??null))return$existing;throw new \RuntimeException('OR36_RESEARCH_AUTHORITY_ALREADY_CONSUMED');}
        $commission=$this->read($this->commissions.'/'.$commissionId.'.json','OR30_RESEARCH_COMMISSION_ABSENT');
        if(!$this->digestMatches($commission)||$commissionId!==($commission['commission_id']??null)||$commission['instance_id']!==($augur['instance_id']??null)
            ||$commission['recipient']['binding_id']!==($augur['binding_id']??null)||'oracle.augur'!==($augur['seat']??null)
            ||!in_array($augur['status']??null,['ACTIVE','ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY'],true)
            ||true!==($augur['model_intelligence_stewardship_authority']??null)||true===($augur['model_research_authority']??null)||true===($augur['model_selection_authority']??null)
            ||true!==($commission['external_research_authority_exercisable']??null)
            ||new \DateTimeImmutable($commission['expires_at'])<=$artifact->admittedAt
        )throw new \RuntimeException('OR31_RESEARCH_COMMISSION_NOT_EXERCISABLE');
        $p=$artifact->provenance;
        if($commissionId!==($p['commission_id']??null)||$commission['authorization']['id']!==($p['authorization_id']??null)
            ||$commission['expected_return_contract']!==($p['expected_return_contract']??null)||null===($p['sortie_id']??null)||null===($p['manifestation_id']??null)
            ||[]!==array_diff($p['source_ids']??[],$commission['scope']['destinations'])
        )throw new \RuntimeException('OR32_LAZARETTO_RESEARCH_LINEAGE_INVALID');
        $body=json_decode($artifact->content,true,512,JSON_THROW_ON_ERROR);
        if(!is_array($body)||array_keys($body)!==['provider','model_id','model_version','knowledge_sources','claims','admissibility']
            ||!in_array($body['provider']??null,$commission['scope']['providers'],true)||!is_array($body['knowledge_sources'])||[]===$body['knowledge_sources']||count($body['knowledge_sources'])>$commission['scope']['max_sources']||!is_array($body['claims'])
            ||!is_array($body['admissibility'])||array_keys($body['admissibility'])!==['status','policy_refs','evidence_source_ids','reasons']
        )throw new \RuntimeException('OR33_RESEARCH_RETURN_INVALID');
        $sourceIds=[];foreach($body['knowledge_sources']as$s){if(!is_array($s)||array_keys($s)!==['source_id','source_type','locator','observed_at','content_digest']||!$this->identifier($s['source_id']??null)||isset($sourceIds[$s['source_id']])||!preg_match('/^sha256:[a-f0-9]{64}$/',$s['content_digest']??'')||false===\DateTimeImmutable::createFromFormat(DATE_ATOM,$s['observed_at']??''))throw new \RuntimeException('OR33_RESEARCH_RETURN_INVALID');$sourceIds[$s['source_id']]=true;}
        foreach($body['claims']??[]as$c){if(!in_array($c['subject']??null,$commission['scope']['claim_subjects'],true))throw new \RuntimeException('OR34_RESEARCH_CLAIM_OUT_OF_SCOPE');}
        foreach($body['claims']as$c){if(!is_array($c)||array_keys($c)!==['claim_id','subject','value','evidence_source_ids']||!$this->identifier($c['claim_id']??null)||!is_array($c['evidence_source_ids'])||[]!==array_diff($c['evidence_source_ids'],array_keys($sourceIds)))throw new \RuntimeException('OR33_RESEARCH_RETURN_INVALID');}
        $id='oracle-evidence-'.substr(hash('sha256',CanonicalJson::encode([$commissionId,$artifact->artifactId,$artifact->rawPayloadDigest,$body])),0,20);
        $evidence=['schema'=>'imperium.oracle-admitted-model-evidence/v1','evidence_id'=>$id,'instance_id'=>$commission['instance_id'],'provider'=>$body['provider'],'model_id'=>$body['model_id'],'model_version'=>$body['model_version'],'knowledge_sources'=>$body['knowledge_sources'],'claims'=>$body['claims'],'admissibility'=>$body['admissibility'],
            'research_lineage'=>['commission_id'=>$commissionId,'commission_digest'=>$commission['record_digest'],'authorization_id'=>$commission['authorization']['id'],'authorization_digest'=>$commission['authorization']['digest'],'lazaretto_artifact_id'=>$artifact->artifactId,'raw_payload_id'=>$artifact->rawPayloadId,'raw_payload_digest'=>$artifact->rawPayloadDigest,'sortie_id'=>$p['sortie_id'],'manifestation_id'=>$p['manifestation_id'],'transformation'=>$p['transformation']],
            'status'=>'EVIDENCE_ADMITTED','admitted_by'=>['office'=>'oracle','process'=>'governed-research-evidence-admission'],'model_research_authority'=>false,'sealed'=>true];
        $evidence=$this->persist($this->evidence,$id,$evidence);
        $receiptId='oracle-research-receipt-'.substr(hash('sha256',CanonicalJson::encode([$commissionId,$id,$evidence['record_digest']])),0,20);
        return$this->persist($this->receipts,$receiptId,['schema'=>'imperium.oracle-research-evidence-admission/v1','receipt_id'=>$receiptId,'instance_id'=>$commission['instance_id'],'commission'=>['id'=>$commissionId,'digest'=>$commission['record_digest']],'evidence'=>['id'=>$id,'digest'=>$evidence['record_digest']],'lazaretto_artifact_id'=>$artifact->artifactId,'external_research_authority_consumed'=>true,'external_research_authority_exercisable'=>false,'credential_use_authority'=>false,'provider_invocation_authority'=>false,'eligibility_authority'=>false,'recommendation_authority'=>false,'selection_authority'=>false,'model_assignment_authority'=>false,'deployment_authority'=>false,'status'=>'ORACLE_RESEARCH_EVIDENCE_ADMITTED_AUTHORITY_CONSUMED','sealed'=>true]);
    }
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function receiptFor(string$c):?array{if(!is_dir($this->receipts))return null;foreach(glob($this->receipts.'/*.json')?:[]as$p){$r=$this->read($p,'OR36_RESEARCH_AUTHORITY_ALREADY_CONSUMED');if($c===($r['commission']['id']??null))return$r;}return null;}
    private function identifier(mixed$v):bool{return is_string($v)&&1===preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:@\/-]*$/',$v);}
    private function digestMatches(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,'sha256:'.hash('sha256',CanonicalJson::encode($r)));}
    private function persist(string$d,string$id,array$r):array{$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));if(!is_dir($d)&&!mkdir($d,0770,true)&&!is_dir($d))throw new \RuntimeException('OR35_RESEARCH_EVIDENCE_PERSISTENCE_FAILED');$p=$d.'/'.$id.'.json';if(is_file($p)){return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
