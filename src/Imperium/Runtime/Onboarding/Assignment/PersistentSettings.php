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
