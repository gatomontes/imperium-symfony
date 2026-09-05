<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ImperatorPlanningDossierReviewService
{
    private const string IMPERATOR_ID='imperator-development-root';
    private string $dossiers;private string $reviews;
    public function __construct(string$root){$this->dossiers=$root.'/var/imperium/offices/curia/planning-dossiers';$this->reviews=$root.'/var/imperium/offices/curia/planning-dossier-reviews';}

    public function review(string$dossierId,string$authorityId,string$disposition,array$citedLineNumbers,string$response,bool$acknowledgeAllLines,\DateTimeImmutable$reviewedAt,string$operatorIdentity=self::IMPERATOR_ID,bool$persist=true):array
    {
        $existing=$persist?$this->existing($authorityId):null;if(null!==$existing)return$existing;$d=$this->read($this->dossiers.'/'.$dossierId.'.json','C250_PLANNING_DOSSIER_ABSENT');$a=$d['imperator_review_authority']??[];$numbers=$this->numbers($citedLineNumbers);$approval='APPROVE_DOSSIER'===$disposition;
        if(!$this->ok($d)||'imperium.curia-planning-dossier/v1'!==($d['schema']??null)||'CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW'!==($d['status']??null)||$authorityId!==($a['authority_id']??null)||true!==($a['review_authority']??null)||true!==($a['authority_single_use']??null)||!in_array($disposition,$a['permitted_dispositions']??[],true)||''===trim($response)||($approval&&(!$acknowledgeAllLines||[]!==$numbers))||(!$approval&&($acknowledgeAllLines||[]===$numbers)))throw new \RuntimeException('C251_PLANNING_DOSSIER_REVIEW_INVALID');
        $cited=[];foreach($numbers as$n){$line=$d['lines'][$n-1]??null;if(!is_array($line)||$n!==($line['line_number']??null))throw new \RuntimeException('C252_DOSSIER_LINE_REFERENCE_INVALID');$cited[(string)$n]=['line_number'=>$n,'section'=>$line['section'],'text'=>$line['text'],'line_digest'=>$line['line_digest']];}$actor=['kind'=>'imperator','id'=>$operatorIdentity];$id='imperator-planning-dossier-review-'.substr(hash('sha256',CanonicalJson::encode([$dossierId,$d['record_digest'],$authorityId,$disposition,$cited,$response,$acknowledgeAllLines,$actor])),0,20);$nextId=($approval?'mission-authorization-derivation-authority-':'curia-planning-dossier-revision-authority-').substr(hash('sha256',CanonicalJson::encode([$id,$d['record_digest'],$cited])),0,20);$next=$approval?['authority_id'=>$nextId,'authority_single_use'=>true,'dossier'=>['id'=>$dossierId,'digest'=>$d['record_digest']],'derivation_authority'=>true,'execution_authority'=>false,'status'=>'OPEN_PENDING_MISSION_AUTHORIZATION_DERIVATION']:['authority_id'=>$nextId,'authority_single_use'=>true,'superseded_dossier'=>['id'=>$dossierId,'digest'=>$d['record_digest'],'version'=>$d['dossier_version']],'cited_lines'=>$cited,'revision_authority'=>true,'status'=>'OPEN_PENDING_CURIA_DOSSIER_REVISION'];
        return$this->save($id,['schema'=>'imperium.imperator-planning-dossier-review/v1','review_id'=>$id,'dossier'=>['id'=>$dossierId,'version'=>$d['dossier_version'],'digest'=>$d['record_digest'],'line_count'=>$d['line_count']],'actor'=>$actor,'disposition'=>$disposition,'all_lines_acknowledged'=>$acknowledgeAllLines,'cited_lines'=>$cited,'response'=>$response,'reviewed_at'=>$reviewedAt->format(DATE_ATOM),'review_authority'=>['id'=>$authorityId,'consumed'=>true,'continuing_authority'=>false],'mission_authorization_derivation_authority'=>$approval?$next:null,'curia_revision_authority'=>$approval?null:$next,'dossier_approval'=>$approval,'resource_authority'=>false,'model_binding_authority'=>false,'model_assignment_authority'=>false,'profile_mutation_authority'=>false,'credential_release_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'status'=>$approval?'IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION':'IMPERATOR_PLANNING_DOSSIER_OBJECTED_PENDING_CURIA_REVISION','sealed'=>true],$persist);
    }

    /** Exact pending bytes are authenticated before any review is persisted. */
    public function reviewAuthenticated(array $payload, string $signature, array $trust, int $now): array
    {
        \App\ProtectedMission\PublicTrust::verify($trust, $payload, $signature, $now);
        if (($payload['schema'] ?? '') !== 'imperium.protected-approval/v1' || $now >= ($payload['expires_at'] ?? 0)) {
            throw new \RuntimeException('PMA_CHALLENGE_INACTIVE');
        }
        $d=$payload['dossier']; $expected=$payload['review_preview'];
        $persisted=$this->read($this->dossiers.'/'.$d['dossier_id'].'.json','C250_PLANNING_DOSSIER_ABSENT');
        if (CanonicalJson::encode($persisted)!==CanonicalJson::encode($d)) throw new \RuntimeException('PMA_DOSSIER_CHANGED');
        $preview=$this->review($d['dossier_id'],$d['imperator_review_authority']['authority_id'],'APPROVE_DOSSIER',[],
            $expected['response'],true,new \DateTimeImmutable($expected['reviewed_at']),$trust['identity'],false);
        if (CanonicalJson::encode($preview)!==CanonicalJson::encode($expected)) throw new \RuntimeException('PMA_REVIEW_PREVIEW_CHANGED');
        if ($this->existing($d['imperator_review_authority']['authority_id'])!==null) throw new \RuntimeException('PMA_REVIEW_ALREADY_EXISTS');
        unset($preview['record_digest']);
        $preview['operator_authenticity']=['challenge_id'=>$payload['challenge_id'],'payload_digest'=>hash('sha256',CanonicalJson::encode($payload)),
            'signature'=>$signature,'trust_fingerprint'=>$trust['fingerprint'],'submitted_at'=>$now];
        return $this->save($preview['review_id'],$preview);
    }

    private function numbers(array$n):array{foreach($n as$v)if(!is_int($v)||$v<1)throw new \RuntimeException('C252_DOSSIER_LINE_REFERENCE_INVALID');$n=array_values(array_unique($n));sort($n,SORT_NUMERIC);return$n;}
    private function existing(string$a):?array{if(!is_dir($this->reviews))return null;foreach(glob($this->reviews.'/imperator-planning-dossier-review-*.json')?:[]as$p){$r=$this->read($p,'C253_PLANNING_DOSSIER_REVIEW_FAILED');if($a===($r['review_authority']['id']??null)){if(!$this->ok($r))throw new \RuntimeException('C253_PLANNING_DOSSIER_REVIEW_FAILED');return$r;}}return null;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$x=$r['record_digest']??null;unset($r['record_digest']);return is_string($x)&&hash_equals($x,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string$id,array$r,bool$persist=true):array{if(!$persist){$r['record_digest']=hash('sha256',CanonicalJson::encode($r));return$r;}if(!is_dir($this->reviews)&&!mkdir($this->reviews,0770,true)&&!is_dir($this->reviews))throw new \RuntimeException('C253_PLANNING_DOSSIER_REVIEW_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->reviews.'/'.$id.'.json';if(is_file($p))return$this->read($p,'C253_PLANNING_DOSSIER_REVIEW_FAILED');file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
