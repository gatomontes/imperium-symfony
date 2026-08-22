<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProfileExaminationDispositionAuthorityOpeningService
{
    private string $reconciliations;private string $findings;private string $occupancy;private string $custody;private string $openings;
    public function __construct(#[Autowire('%kernel.project_dir%')]string $root){$senate=$root.'/var/imperium/offices/senate';$this->reconciliations=$senate.'/profile-examination-reconciliations';$this->findings=$senate.'/profile-examination-senator-findings';$this->occupancy=$senate.'/occupancy';$this->custody=$root.'/var/imperium/offices/garrison/custody';$this->openings=$senate.'/profile-examination-disposition-authority-openings';}

    public function open(string $reconciliationId,string $lordSpeakerBindingId):array
    {
        if(!preg_match('/^profile-examination-reconciliation-[a-f0-9]{20}$/',$reconciliationId))throw new \InvalidArgumentException('S272_PROFILE_EXAMINATION_RECONCILIATION_ID_INVALID');
        $reconciliation=$this->read($this->reconciliations.'/'.$reconciliationId.'.json','S273_PROFILE_EXAMINATION_RECONCILIATION_ABSENT');
        $lordSpeaker=$this->read($this->occupancy.'/'.$lordSpeakerBindingId.'.json','S274_LORD_SPEAKER_UNAVAILABLE');
        $custodyId=$reconciliation['custody_lease']['custody_id']??null;$custody=is_string($custodyId)?$this->read($this->custody.'/'.$custodyId.'.json','S275_PROFILE_EXAMINATION_CUSTODY_ABSENT'):[];
        if(!$this->valid($reconciliation)||!$this->valid($lordSpeaker)||!$this->valid($custody)
            ||'imperium.senate-profile-examination-reconciliation/v1'!==($reconciliation['schema']??null)||'PROFILE_EXAMINATION_FINDINGS_RECONCILED_PENDING_DISPOSITION_AUTHORITY_OPENING'!==($reconciliation['status']??null)
            ||true!==($reconciliation['reconciliation_authority_consumed']??null)||true!==($reconciliation['deliberation_open']??null)||false!==($reconciliation['vote_authority']??null)||false!==($reconciliation['aggregation_authority']??null)||false!==($reconciliation['senate_disposition_authority']??null)
            ||($reconciliation['custody_lease']['custody_digest']??null)!==($custody['record_digest']??null)||'ADMITTED_HELD'!==($custody['custody_state']??null)||true!==($custody['available']??null)
            ||'senate.lord-speaker'!==($lordSpeaker['seat']??null)||'ACTIVE'!==($lordSpeaker['status']??null)||true!==($lordSpeaker['binding_atomic']??null)||true!==($lordSpeaker['profile_examination_disposition_phase_opening_authority']??null)||true===($lordSpeaker['execution_authority']??null)
            ||($reconciliation['lord_speaker']['binding_id']??null)!==$lordSpeakerBindingId||($reconciliation['lord_speaker']['binding_digest']??null)!==($lordSpeaker['record_digest']??null)||($reconciliation['instance_id']??null)!==($lordSpeaker['instance_id']??null)
        )throw new \RuntimeException('S276_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CHAIN_INVALID');
        $snapshots=$reconciliation['admitted_findings']??null;if(!is_array($snapshots)||3!==count($snapshots))throw new \RuntimeException('S276_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CHAIN_INVALID');$findings=[];
        foreach($snapshots as $snapshot){$id=$snapshot['finding_id']??null;$finding=is_string($id)?$this->read($this->findings.'/'.$id.'.json','S277_PROFILE_EXAMINATION_FINDING_ABSENT'):[];$jurisdiction=$finding['jurisdiction']??null;if(!$this->valid($finding)||$snapshot!==$finding||!in_array($jurisdiction,['trust','security','usability'],true)||isset($findings[$jurisdiction]))throw new \RuntimeException('S276_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CHAIN_INVALID');$findings[$jurisdiction]=$finding;}
        foreach(['trust','security','usability']as$jurisdiction)if(!isset($findings[$jurisdiction]))throw new \RuntimeException('S276_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CHAIN_INVALID');ksort($findings);
        $actor=['seat'=>'senate.lord-speaker','binding_id'=>$lordSpeakerBindingId,'binding_digest'=>$lordSpeaker['record_digest'],'manifestation_id'=>$lordSpeaker['manifestation_id'],'occupancy_generation'=>$lordSpeaker['occupancy_generation']];
        $id='profile-examination-disposition-authority-opening-'.substr(hash('sha256',CanonicalJson::encode([$reconciliationId,$reconciliation['record_digest'],$actor,array_column($findings,'record_digest')])),0,20);
        return $this->save($id,['schema'=>'imperium.senate-profile-examination-disposition-authority-opening/v1','opening_id'=>$id,'instance_id'=>$reconciliation['instance_id'],'case_id'=>$reconciliation['case_id'],'case_digest'=>$reconciliation['case_digest'],'source_reconciliation'=>['id'=>$reconciliationId,'digest'=>$reconciliation['record_digest']],'lord_speaker'=>$actor,'manifestation'=>$reconciliation['manifestation'],'profile_candidate'=>$reconciliation['profile_candidate'],'persona_identity'=>$reconciliation['persona_identity'],'custody_lease'=>$reconciliation['custody_lease'],'return_destination'=>$reconciliation['return_destination'],'defect_attribution_rubric'=>$reconciliation['defect_attribution_rubric'],'admitted_findings'=>array_values($findings),'reconciliation'=>$reconciliation['reconciliation'],'status'=>'PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENED_PENDING_LORD_SPEAKER_DISPOSITION','disposition_phase_opening_authority_consumed'=>true,'deliberation_open'=>true,'reconciliation_authority_exercisable'=>false,'vote_authority'=>false,'aggregation_authority'=>false,'senate_disposition_authority'=>true,'senate_disposition'=>null,'profile_approval_authority'=>false,'profile_installation_authority'=>false,'seat_binding_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'sealed'=>true]);
    }
    private function read(string $path,string $error):array{if(!is_file($path))throw new \RuntimeException($error);return json_decode((string)file_get_contents($path),true,512,JSON_THROW_ON_ERROR);}
    private function valid(array $record):bool{$digest=$record['record_digest']??null;unset($record['record_digest']);return is_string($digest)&&hash_equals($digest,hash('sha256',CanonicalJson::encode($record)));}
    private function save(string $id,array $record):array{if(!is_dir($this->openings)&&!mkdir($this->openings,0770,true)&&!is_dir($this->openings))throw new \RuntimeException('S278_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_FAILED');$record['record_digest']=hash('sha256',CanonicalJson::encode($record));$path=$this->openings.'/'.$id.'.json';if(is_file($path)){$existing=$this->read($path,'S279_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CONFLICT');if($existing!==$record)throw new \RuntimeException('S279_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_CONFLICT');return $existing;}$temporary=$path.'.tmp.'.bin2hex(random_bytes(6));if(false===file_put_contents($temporary,json_encode($record,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX)||!rename($temporary,$path)){@unlink($temporary);throw new \RuntimeException('S278_PROFILE_EXAMINATION_DISPOSITION_AUTHORITY_OPENING_FAILED');}return $record;}
}
