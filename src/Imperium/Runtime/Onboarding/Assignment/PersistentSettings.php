<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R};
use App\Imperium\Runtime\Onboarding\Augur\AugurAdapter;

/** Historical settings and current usability are separate. No dispatch authority. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class PersistentSettings
{
    use OriginalAssessment;
    public function __construct(private AuthorityStore $store,private ?AugurAdapter $adapter=null,private AssignmentEvidence $evidence=new MissingAssignmentEvidence()){}
    private function assessmentAdapter():AugurAdapter{R::require($this->adapter!==null,'ASSIGNMENT_ADAPTER_REQUIRED');return $this->adapter;}
    public function snapshot():array
    {
        return $this->store->journal->inspect(function(array $frame):array{
            $s=$this->store->state($frame['state']);$a=ApplicationHistory::latest($s);R::require($a!==null,'MODEL_SETTINGS_ABSENT');
            return ['application_ref'=>R::reference($a['receipt']),'assignments'=>$a['receipt']['body']['next_assignments'],'dispatch_authority'=>false];
        });
    }
    /** Revalidation is read-only and checks the exact supplied historical head. */
    public function revalidate(array $expectedApplication):array
    {
        R::ref($expectedApplication);
        return $this->store->journal->inspect(fn(array $frame):array=>\App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::within(fn():array=>$this->current($frame['state'],$expectedApplication,null)));
    }
    public function resolve(string $role):array
    {
        R::require(in_array($role,AssignmentRule::ROLES,true),'SETTINGS_ROLE');
        return $this->store->journal->inspect(fn(array $frame):array=>\App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::within(fn():array=>$this->current($frame['state'],null,$role)));
    }
    public function assertFormationOwner(\App\Imperium\Runtime\Citadel\Formation\FormationJournal $journal, array $state):void
    {
        R::require($this->store->journal->sameOwner($journal),'SETTINGS_FOREIGN_AGGREGATE');
        R::require(($state['parent_instance_id']??null)===$this->store->instance,'SETTINGS_FOREIGN_INSTANCE');
    }
    /** Internal owner-frame validation: no nested journal acquisition. The native
     * Profile envelope digest and O4 H reference belong to different schemas;
     * the policy-admitted mapping explicitly joins them, including original bytes.
     */
    public function verifyFormation(\App\Imperium\Runtime\Citadel\Formation\FormationJournal $journal,
        array $state,string $role,array $request,array $terms,array $configuration):void
    {
        $this->assertFormationOwner($journal,$state);
        \App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::within(function()use($state,$role,$request,$terms,$configuration):void{
            R::require(in_array($role,AssignmentRule::ROLES,true),'SETTINGS_ROLE');
            $tuple=$this->current($state,null,$role);
            R::require(R::same($terms['model_settings']??null,$tuple),'SETTINGS_TRANSPORT_GENERATION');
            R::require(($terms['provider']??null)===$tuple['provider'] && ($terms['model']??null)===$tuple['model_id'],'SETTINGS_TRANSPORT_IDENTITY');
            $s=$this->store->state($state);
            $profile=$this->store->checkSource($s,$tuple['profile_ref']);
            $mapping=\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Policy::content($profile,'formation-profile-mapping');
            R::object($mapping,['schema','instance_id','formation_citadel_id','seat','holder_generation','profile_evidence_digest','profile_artifact']);
            R::require($mapping['schema']==='imperium.formation-profile-mapping/v1'
                && $mapping['instance_id']===$this->store->instance && $mapping['formation_citadel_id']===($state['citadel_id']??null)
                && $mapping['seat']===$role,'SETTINGS_PROFILE_SCOPE');
            $holder=$request['holder']??[];
            $original=$state['personnel_evidence'][$mapping['profile_evidence_digest']]??null;
            R::require(is_array($original) && \App\Imperium\Runtime\Citadel\Formation\FormationJournal::digest($original)===$mapping['profile_evidence_digest']
                && ($original['payload']['kind']??null)==='DERIVED_PROFILE'
                && ($original['payload']['scope']??null)===$mapping['formation_citadel_id']
                && ($original['payload']['content']['seat']??null)===$role
                && R::same($original['payload']['content']['artifact']??null,$mapping['profile_artifact'])
                && ($holder['candidate']['profile']??null)===$mapping['profile_evidence_digest']
                && ($holder['terms']['seat']??null)===$role
                && ($holder['generation']??null)===$mapping['holder_generation']
                && $mapping['holder_generation']===$tuple['profile_generation']
                && R::same($holder,$state[$role==='courtyard.courtthane'?'courtthane':'locksmith']??null)
                && R::same($holder['profile_artifact']??null,$mapping['profile_artifact']),'SETTINGS_EXECUTING_PROFILE');
            $originalConfiguration=$this->store->checkSource($s,$tuple['configuration_ref']);
            R::require(R::same($configuration,\App\Imperium\Runtime\Onboarding\AuthorityAdmission\Policy::content($originalConfiguration,'request-configuration')),'SETTINGS_EFFECTIVE_CONFIGURATION');
        });
    }
    private function current(array $state,?array $expectedApplication,?string $role):array
    {
        $s=$this->store->state($state);$a=ApplicationHistory::latest($s);R::require($a!==null,'MODEL_SETTINGS_ABSENT');$receipt=$a['receipt'];
        if($expectedApplication!==null){R::require(R::same($expectedApplication,R::reference($receipt)),'STALE_ASSIGNMENT_PREDECESSOR');}
        \App\Imperium\Runtime\Onboarding\AuthorityAdmission\CurrentAuthority::verify($this->store,$s,$receipt['body']['authority'],'APPLY_BOOTSTRAP_ASSIGNMENTS',$a['terms_ref']);
        $originals=$this->originals($state,$receipt['body']['policy_ref']);$policy=$originals['policy'];$retained=[];
        $load=function(array $ref)use($s,&$retained):array{$h=$this->store->checkSource($s,$ref);$retained[R::key($ref)]=$h;return $h;};
        $sets=AssignmentRule::sets($policy,$load);$set=$sets[R::key($a['selected_set_ref'])];AssignmentRule::profileEvidence($policy,$set,$load);ApplicationHistory::eligible($set,$originals);
        foreach($policy['body']['candidate_bindings'] as $b){foreach(['binding_ref','configuration_ref','adapter_ref','mapping_ref'] as $f){$load($b[$f]);}}$load($policy['body']['credential_ref']);
        $tuples=$set['assignments'];if($role!==null){$tuples=array_values(array_filter($tuples,static fn(array $t):bool=>$t['role']===$role));}
        $this->evidence->verify($policy,$tuples,$retained,$originals['groups']);
        if($role!==null){return array_values(array_filter($receipt['body']['next_assignments'],static fn(array $t):bool=>$t['role']===$role))[0];}
        return ['application_ref'=>R::reference($receipt),'assignments'=>$receipt['body']['next_assignments'],'dispatch_authority'=>false];
    }
}
