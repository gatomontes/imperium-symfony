<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\CanonicalJson;

final readonly class AugurResidentActivationService
{
    private string $assignments; private string $directory;
    public function __construct(string $projectDir, private GenericOfficerSubstrateRegistry $substrate)
    { $this->assignments=$projectDir.'/var/imperium/imperator/founding-augur-model-assignments';$this->directory=$projectDir.'/var/imperium/offices/oracle/occupancy'; }

    public function activate(string $assignmentId, array $custody, array $profileApproval, array $recruiterBinding): array
    {
        if(!preg_match('/^founding-augur-model-assignment-[a-f0-9]{20}$/',$assignmentId))throw new \InvalidArgumentException('R220_FOUNDING_ASSIGNMENT_ID_INVALID');
        $assignment=$this->read($this->assignments.'/'.$assignmentId.'.json','R221_FOUNDING_ASSIGNMENT_ABSENT');$this->validate($assignmentId,$assignment,$custody,$profileApproval,$recruiterBinding);
        $substrate=$this->substrate->current();$actor=['seat'=>'conscription.recruiter','binding_id'=>$recruiterBinding['binding_id'],'manifestation_id'=>$recruiterBinding['manifestation_id'],'occupancy_generation'=>$recruiterBinding['occupancy_generation']];
        $persona=['persona_id'=>$custody['persona_id'],'persona_version'=>$custody['persona_version'],'persona_digest'=>$custody['persona_digest'],'custody_id'=>$custody['custody_id'],'custody_digest'=>$custody['record_digest']];
        $profile=['profile_id'=>$profileApproval['profile']['profile_id'],'profile_version'=>$profileApproval['profile']['profile_version'],'profile_digest'=>$profileApproval['profile']['content_digest'],'approval_id'=>$profileApproval['approval_id'],'approval_digest'=>$profileApproval['record_digest'],'model_binding'=>$assignment['model_binding']];
        $manifestationId='oracle-augur-manifestation-'.substr(hash('sha256',CanonicalJson::encode([$assignmentId,$assignment['record_digest'],$persona,$profile,$substrate])),0,20);
        $manifestation=['manifestation_id'=>$manifestationId,'instance_id'=>$assignment['instance_id'],'persona'=>$persona,'profile'=>$profile,'officer_substrate'=>$substrate,'seat'=>'oracle.augur','status'=>'BOUND_ACTIVE','identity_from_persona'=>true,'authority_from_occupied_profile'=>true,'substrate_identity_contribution'=>false,'substrate_authority_contribution'=>false];
        $bindingId='oracle-augur-binding-'.substr(hash('sha256',CanonicalJson::encode([$manifestationId,$manifestation,$actor])),0,20);
        return$this->persist($bindingId,['schema'=>'imperium.oracle-augur-occupancy/v1','binding_id'=>$bindingId,'instance_id'=>$assignment['instance_id'],'office'=>'oracle','seat'=>'oracle.augur','manifestation_id'=>$manifestationId,'manifestation'=>$manifestation,'assembler'=>$actor,'source_founding_model_assignment'=>['id'=>$assignmentId,'digest'=>$assignment['record_digest']],'persona_custody'=>['id'=>$custody['custody_id'],'digest'=>$custody['record_digest']],'standing_profile_approval'=>['id'=>$profileApproval['approval_id'],'digest'=>$profileApproval['record_digest']],'prior_occupancy_generation'=>0,'occupancy_generation'=>1,'status'=>'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY','binding_atomic'=>true,'model_intelligence_stewardship_authority'=>true,'catalogue_snapshot_authority'=>true,'model_requirement_commission_acceptance_authority'=>true,'model_research_authority'=>false,'recommendation_authority'=>false,'selection_authority'=>false,'self_selection_authority'=>false,'model_assignment_authority'=>false,'profile_mutation_authority'=>false,'credential_disclosure_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'replacement_requires_governed_cutover'=>true,'sealed'=>true]);
    }

    private function validate(string$id,array$a,array$c,array$p,array$r):void
    {
        if(!$this->valid($a)||!$this->valid($c)||!$this->validPrefixed($p)
            ||'imperium.imperator-founding-augur-model-assignment/v1'!==($a['schema']??null)||$id!==($a['assignment_id']??null)||'oracle.augur'!==($a['target_seat']??null)||'PROVISIONAL_FOUNDING_EXCEPTION'!==($a['assignment_class']??null)||'FOUNDING_AUGUR_MODEL_ASSIGNED_PROVISIONAL_PENDING_CONSCRIPTION_ASSEMBLY'!==($a['status']??null)||true!==($a['founding_assignment_authority_consumed']??null)||true===($a['self_selection_authority']??null)||true===($a['provider_invocation_authority']??null)
            ||'imperium.garrison-persona-custody/v1'!==($c['schema']??null)||($a['instance_id']??null)!==($c['instance_id']??null)||'ADMITTED_HELD'!==($c['custody_state']??null)||true!==($c['available']??null)||true!==($c['sealed']??null)
            ||'imperium.imperator-standing-officer-profile-approval/v1'!==($p['schema']??null)||($a['instance_id']??null)!==($p['instance_id']??null)||'oracle.augur'!==($p['profile']['target_seat']??null)||'APPROVED'!==($p['disposition']??null)||'CURRENT_ACTIVE'!==($p['status']??null)||CanonicalJson::encode($a['model_binding']??null)!==CanonicalJson::encode($p['profile']['model_binding']??null)||($c['persona_id']??null)!==($p['profile']['persona_id']??null)||($c['persona_digest']??null)!==($p['profile']['persona_digest']??null)
            ||!$this->valid($r)||'imperium.conscription-recruiter-occupancy/v1'!==($r['schema']??null)||($a['instance_id']??null)!==($r['instance_id']??null)||'conscription.recruiter'!==($r['seat']??null)||'ACTIVE'!==($r['status']??null)||true!==($r['resident_manifestation_assembly_authority']??null)||true!==($r['resident_seat_binding_authority']??null)||true===($r['model_selection_authority']??null)||true===($r['execution_authority']??null)
        )throw new \RuntimeException('R222_AUGUR_ACTIVATION_CHAIN_INVALID');
    }
    private function valid(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,hash('sha256',CanonicalJson::encode($r)));}
    private function validPrefixed(array$r):bool{$d=$r['record_digest']??null;unset($r['record_digest']);return is_string($d)&&hash_equals($d,'sha256:'.hash('sha256',CanonicalJson::encode($r)));}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function persist(string$id,array$r):array{if(!is_dir($this->directory)&&!mkdir($this->directory,0770,true)&&!is_dir($this->directory))throw new \RuntimeException('R223_AUGUR_BINDING_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->directory.'/'.$id.'.json';if(is_file($p)){$x=$this->read($p,'R224_AUGUR_BINDING_CONFLICT');if(CanonicalJson::encode($x)!==CanonicalJson::encode($r))throw new \RuntimeException('R224_AUGUR_BINDING_CONFLICT');return$x;}if([]!==(glob($this->directory.'/oracle-augur-binding-*.json')?:[]))throw new \RuntimeException('R225_AUGUR_ALREADY_BOUND');if(false===file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('R223_AUGUR_BINDING_FAILED');return$r;}
}
