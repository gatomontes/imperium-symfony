<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Governance;
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalGovernanceInterruptionDispositionService
{
 private const CLAIMS='var/imperium/runtime/governance-cognition-invocation-claims',JOURNAL='var/imperium/runtime/provider-invocation-journal',OCCUPANCY='var/imperium/operational/occupancy',DISPOSITIONS='var/imperium/runtime/continuous-governance-revocation-dispositions';
 private RecordReferenceValidator $validator;private ImmutableRecordStore $records;private AtomicTransition $atomic;
 public function __construct(#[Autowire('%kernel.project_dir%')] private string $root,?RecordReferenceValidator $validator=null,?ImmutableRecordStore $records=null,?AtomicTransition $atomic=null){$this->validator=$validator??new RecordReferenceValidator($root);$this->atomic=$atomic??new AtomicTransition($root);$this->records=$records??new ImmutableRecordStore($root,$this->atomic);}
 public function interrupt(string $claimId,string $seneschalBindingId,string $reason,\DateTimeImmutable $effectiveAt):array
 {
  $reason=trim($reason);if(!preg_match('/^governance-cognition-invocation-claim-[a-f0-9]{20}$/',$claimId)||!preg_match('/^operational-seat-binding-[a-f0-9]{20}$/',$seneschalBindingId)||''===$reason)throw new \InvalidArgumentException('CAG700_INTERNAL_INTERRUPTION_INPUT_INVALID');
  $journalPath=self::JOURNAL.'/'.$claimId.'.json';
  return $this->atomic->run('mutable:'.hash('sha256',$journalPath),function()use($claimId,$seneschalBindingId,$reason,$effectiveAt):array{
   $claim=$this->validator->read($this->root.'/'.self::CLAIMS.'/'.$claimId.'.json','CAG701_GOVERNANCE_CLAIM_ABSENT');$seneschal=$this->validator->read($this->root.'/'.self::OCCUPANCY.'/'.$seneschalBindingId.'.json','CAG702_SENESCHAL_OCCUPANCY_ABSENT');$this->assertClaimIsPreIo($claimId,$claim);$this->assertCurrentSeneschal($seneschalBindingId,$seneschal,(string)($claim['instance_id']??''));
   $actor=['seat'=>'curia.seneschal','binding_id'=>$seneschalBindingId,'binding_digest'=>$seneschal['record_digest'],'manifestation_id'=>$seneschal['manifestation_id'],'occupancy_generation'=>$seneschal['occupancy_generation']];$id='revocation-disposition-'.substr(hash('sha256',CanonicalJson::encode([$claimId,$claim['record_digest'],$actor,$reason])),0,20);$path=$this->root.'/'.self::DISPOSITIONS.'/'.$id.'.json';
   if(is_file($path)){$prior=$this->validator->read($path,'CAG706_INTERRUPTION_DISPOSITION_CONFLICT');if(!$this->validator->isIntact($prior)||($prior['affected_scope']['claim']['digest']??null)!==$claim['record_digest']||($prior['competent_actor']??null)!==$actor||($prior['reason']??null)!==$reason)throw new \RuntimeException('CAG706_INTERRUPTION_DISPOSITION_CONFLICT');return $prior;}
   return $this->records->put(self::DISPOSITIONS,$id,['schema'=>'imperium.continuous-governance-revocation-disposition/v1','disposition_id'=>$id,'instance_id'=>$claim['instance_id'],'disposition'=>'INTERRUPT','competent_actor'=>$actor,'authority_basis'=>['contract'=>'imperium.continuous-governance-revocation-authority-design/v1','jurisdiction'=>'SENESCHAL_ACTIVE_INTERNAL_MISSION_ITERATION','source_occupancy'=>['id'=>$seneschalBindingId,'digest'=>$seneschal['record_digest']]],'affected_scope'=>['kind'=>'INTERNAL_GOVERNANCE_COGNITION_INVOCATION','claim'=>['id'=>$claimId,'digest'=>$claim['record_digest']],'case_id'=>$claim['case_id'],'cluster'=>$claim['cluster'],'target'=>$claim['target'],'external_io_started'=>false],'reason'=>$reason,'effective_at'=>$effectiveAt->format(DATE_ATOM),'prior_references'=>[['id'=>$claimId,'digest'=>$claim['record_digest']]],'enforcement_required'=>true,'enforcement_authority_opened'=>false,'state_mutated'=>false,'authority_granted'=>false,'continuation_authority'=>false,'sealed'=>true]);
  });
 }
 private function assertClaimIsPreIo(string $id,array $c):void
  {if(!$this->validator->isIntact($c)||'imperium.clavium-governance-cognition-invocation-claim/v1'!==($c['schema']??null)||$id!==($c['claim_id']??null)||'GOVERNANCE_INVOCATION_CLAIMED_DURABLE_PRE_IO'!==($c['status']??null)||false!==($c['provider_request']['external_io_started']??null)||true!==($c['sealed']??null))throw new \RuntimeException('CAG703_GOVERNANCE_CLAIM_NOT_INTERRUPTIBLE_PRE_IO');foreach(glob($this->root.'/'.self::JOURNAL.'/*.json')?:[]as$p){$j=$this->validator->read($p,'CAG704_PROVIDER_JOURNAL_UNREADABLE');if(($j['claim']['id']??null)===$id)throw new \RuntimeException('CAG703_GOVERNANCE_CLAIM_NOT_INTERRUPTIBLE_PRE_IO');}}
 private function assertCurrentSeneschal(string $id,array $s,string $instance):void
 {if(!$this->validator->isIntact($s)||$id!==($s['binding_id']??null)||$instance!==($s['instance_id']??null)||'curia.seneschal'!==($s['seat']??null)||'ACTIVE'!==($s['status']??null)||true!==($s['sealed']??null)||!is_string($s['manifestation_id']??null)||!is_int($s['occupancy_generation']??null))throw new \RuntimeException('CAG705_SENESCHAL_NOT_COMPETENT_CURRENT_OCCUPANT');foreach(glob($this->root.'/'.self::OCCUPANCY.'/*.json')?:[]as$p){$o=$this->validator->read($p,'CAG705_SENESCHAL_NOT_COMPETENT_CURRENT_OCCUPANT');if('curia.seneschal'===($o['seat']??null)&&'ACTIVE'===($o['status']??null)&&($o['instance_id']??null)===$instance&&($o['binding_id']??null)!==$id)throw new \RuntimeException('CAG705_SENESCHAL_NOT_COMPETENT_CURRENT_OCCUPANT');}}
}
