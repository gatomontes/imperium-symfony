<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Authorship;
use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class SubordinateAuthorshipCommissionAcceptanceService{
private string$officeRoot;
private string$specificationDirectory;
private string$caseDirectory;
public function __construct(#[Autowire('%kernel.project_dir%')]string$projectDir){
$this->officeRoot=$projectDir.'/var/imperium/offices';
$this->specificationDirectory=$projectDir.'/var/imperium/offices/foundry/subordinate-persona-specifications';
$this->caseDirectory=$projectDir.'/var/imperium/offices/foundry/subordinate-construction-cases';

}
public function accept(string$office,string$commissionId,string$bindingId):array{
[$role,$seat]=match($office){
'hagiography'=>['sanctographer','hagiography.sanctographer'],'studium'=>['chancellor','studium.chancellor'],default=>throw new \InvalidArgumentException('A93_AUTHORSHIP_OFFICE_INVALID')
}
;
if(!preg_match('/^subordinate-authorship-'.$office.'-[a-f0-9]{20}$/',$commissionId))throw new \InvalidArgumentException('A94_SUBORDINATE_COMMISSION_ID_INVALID');
if(!preg_match('/^'.$office.'-'.$role.'-binding-[a-f0-9]{20}$/',$bindingId))throw new \InvalidArgumentException('A95_RESIDENT_BINDING_ID_INVALID');
$c=$this->read($this->officeRoot.'/'.$office.'/inbox/'.$commissionId.'.json','A96_SUBORDINATE_COMMISSION_ABSENT');
if(!$this->digestMatches($c)||'imperium.subordinate-persona-authorship-commission/v1'!==($c['schema']??null)||$commissionId!==($c['commission_id']??null)||$office!==($c['office']??null)||$seat!==($c['target_seat']??null)||'ISSUED_PENDING_RECIPIENT'!==($c['status']??null)||true!==($c['authorship_authority']??null)||null!==($c['recipient_acceptance']??null)||true===($c['persona_assembly_authority']??null)||true===($c['persona_approval_authority']??null)||true===($c['profile_approval_authority']??null)||true===($c['spawning_authority']??null)||true===($c['admission_authority']??null)||true===($c['seat_binding_authority']??null)||true===($c['execution_authority']??null))throw new \RuntimeException('A97_SUBORDINATE_COMMISSION_INVALID');
$b=$this->read($this->officeRoot.'/'.$office.'/occupancy/'.$bindingId.'.json','A98_RESIDENT_BINDING_ABSENT');
if(!$this->digestMatches($b)||'imperium.authorship-resident-occupancy/v1'!==($b['schema']??null)||$bindingId!==($b['binding_id']??null)||$office!==($b['office']??null)||$seat!==($b['seat']??null)||'ACTIVE'!==($b['status']??null)||true!==($b['binding_atomic']??null)||true!==($b['authorship_authority']??null)||true===($b['execution_authority']??null)||($c['instance_id']??null)!==($b['instance_id']??null))throw new \RuntimeException('A99_RESIDENT_BINDING_INVALID');
$specId=$c['persona_specification_id']??null;
$s=is_string($specId)?$this->read($this->specificationDirectory.'/'.$specId.'.json','A100_SUBORDINATE_COMMISSION_CHAIN_INVALID'):[];
$caseId=$c['subordinate_construction_case_id']??null;
$case=is_string($caseId)?$this->read($this->caseDirectory.'/'.$caseId.'.json','A100_SUBORDINATE_COMMISSION_CHAIN_INVALID'):[];
if(!$this->digestMatches($s)||!$this->digestMatches($case)||'imperium.foundry-subordinate-persona-specification/v1'!==($s['schema']??null)||'SEALED_PENDING_PERSONA_CONSTRUCTION'!==($s['status']??null)||true!==($s['sealed']??null)||'imperium.foundry-subordinate-construction-case/v1'!==($case['schema']??null)||'OPEN_PENDING_PERSONA_SPECIFICATION'!==($case['status']??null)||true!==($case['construction_authority']??null)||($c['persona_specification_digest']??null)!==($s['record_digest']??null)||($c['subordinate_construction_case_digest']??null)!==($case['record_digest']??null)||($s['case_id']??null)!==$caseId||($s['case_digest']??null)!==($case['record_digest']??null)||($c['source_resolution_id']??null)!==($s['source_resolution_id']??null)||($c['source_resolution_digest']??null)!==($s['source_resolution_digest']??null)||CanonicalJson::encode($c['persona_specification']??null)!==CanonicalJson::encode($s['specification']??null)||CanonicalJson::encode($c['inherited_requirements']??null)!==CanonicalJson::encode($s['inherited_requirements']??null))throw new \RuntimeException('A100_SUBORDINATE_COMMISSION_CHAIN_INVALID');
$id=$office.'-subordinate-acceptance-'.substr(hash('sha256',CanonicalJson::encode([$commissionId,$c['record_digest'],$bindingId,$b['record_digest']])),0,20);
return$this->persist($office,$id,['schema'=>'imperium.subordinate-authorship-commission-acceptance/v1','acceptance_id'=>$id,'instance_id'=>$b['instance_id'],'office'=>$office,'commission_id'=>$commissionId,'commission_digest'=>$c['record_digest'],'binding_id'=>$bindingId,'binding_digest'=>$b['record_digest'],'persona_specification_id'=>$specId,'persona_specification_digest'=>$s['record_digest'],'subordinate_construction_case_id'=>$caseId,'subordinate_construction_case_digest'=>$case['record_digest'],'source_resolution_id'=>$c['source_resolution_id'],'source_resolution_digest'=>$c['source_resolution_digest'],'actor'=>['seat'=>$seat,'manifestation_id'=>$b['manifestation_id'],'occupancy_generation'=>$b['occupancy_generation']],'authorship_class'=>$c['authorship_class'],'required_product'=>$c['required_product'],'forbidden_authorship'=>$c['forbidden_authorship'],'disposition'=>'ACCEPTED_FOR_EXACT_SUBORDINATE_AUTHORSHIP','recipient_acceptance'=>true,'authorship_authority'=>true,'authorship_authority_exercisable'=>true,'persona_assembly_authority'=>false,'persona_approval_authority'=>false,'profile_approval_authority'=>false,'spawning_authority'=>false,'admission_authority'=>false,'seat_binding_authority'=>false,'execution_authority'=>false]);

}
private function read(string$p,string$e):array{
if(!is_file($p))throw new \RuntimeException($e);
return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);

}
private function digestMatches(array$r):bool{
$d=$r['record_digest']??null;
unset($r['record_digest']);
return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));

}
private function persist(string$office,string$id,array$r):array{
$dir=$this->officeRoot.'/'.$office.'/subordinate-acceptances';
if(!is_dir($dir)&&!mkdir($dir,0770,true)&&!is_dir($dir))throw new \RuntimeException('Subordinate authorship acceptance directory cannot be created.');
$r['record_digest']=hash('sha256',CanonicalJson::encode($r));
$p=$dir.'/'.$id.'.json';
if(is_file($p)){
$old=$this->read($p,'A101_SUBORDINATE_ACCEPTANCE_REPLAY_CONFLICT');
if(CanonicalJson::encode($old)!==CanonicalJson::encode($r))throw new \RuntimeException('A101_SUBORDINATE_ACCEPTANCE_REPLAY_CONFLICT');
return$old;

}
foreach(glob($dir.'/'.$office.'-subordinate-acceptance-*.json')?:[]as$ep){
$old=$this->read($ep,'A101_SUBORDINATE_ACCEPTANCE_REPLAY_CONFLICT');
if($r['commission_id']===($old['commission_id']??null))throw new \RuntimeException('A102_SUBORDINATE_COMMISSION_ALREADY_DISPOSED');

}
$tmp=$p.'.tmp.'.bin2hex(random_bytes(6));
if(false===file_put_contents($tmp,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($tmp,$p)){
@unlink($tmp);
throw new \RuntimeException('Subordinate authorship acceptance cannot be committed atomically.');

}
return$r;

}

}
