<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\AugurResidentActivationService;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Imperator\FoundingAugurModelAssignmentService;
use PHPUnit\Framework\TestCase;

final class AugurResidentActivationServiceTest extends TestCase
{
    public function testImperatorProvisionalAssignmentEnablesOneGovernedAugurBindingWithoutSelfSelection(): void
    {
        $root=sys_get_temp_dir().'/imperium-augur-activation-'.bin2hex(random_bytes(6));
        try {
            [$assignment,$custody,$profile,$recruiter]=$this->chain($root);
            $service=new AugurResidentActivationService($root,new GenericOfficerSubstrateRegistry(dirname(__DIR__,3)));
            $binding=$service->activate($assignment['assignment_id'],$custody,$profile,$recruiter);

            self::assertSame($binding,$service->activate($assignment['assignment_id'],$custody,$profile,$recruiter));
            self::assertSame('imperium.oracle-augur-occupancy/v1',$binding['schema']);
            self::assertSame('oracle',$binding['office']);self::assertSame('oracle.augur',$binding['seat']);
            self::assertSame('ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY',$binding['status']);
            self::assertTrue($binding['binding_atomic']);self::assertSame(1,$binding['occupancy_generation']);
            self::assertTrue($binding['model_intelligence_stewardship_authority']);self::assertTrue($binding['catalogue_snapshot_authority']);
            self::assertTrue($binding['model_requirement_commission_acceptance_authority']);
            foreach(['model_research_authority','recommendation_authority','selection_authority','self_selection_authority','model_assignment_authority','profile_mutation_authority','credential_disclosure_authority','provider_invocation_authority','deployment_authority','execution_authority']as$field)self::assertFalse($binding[$field]);
            self::assertSame('PROVISIONAL_FOUNDING_EXCEPTION',$assignment['assignment_class']);
            self::assertTrue($assignment['replacement_requires_governed_oracle_evaluation']);self::assertFalse($assignment['silent_substitution_permitted']);
            self::assertSame($assignment['model_binding'],$binding['manifestation']['profile']['model_binding']);
            self::assertSame('generic-officer',$binding['manifestation']['officer_substrate']['id']);
            self::assertFalse($binding['manifestation']['substrate_identity_contribution']);self::assertFalse($binding['manifestation']['substrate_authority_contribution']);
            self::assertFileExists($root.'/var/imperium/offices/oracle/occupancy/'.$binding['binding_id'].'.json');
        } finally {$this->removeTree($root);}
    }

    public function testProfileCannotSubstituteAnotherModelDuringActivation(): void
    {
        $root=sys_get_temp_dir().'/imperium-augur-substitution-'.bin2hex(random_bytes(6));
        try {
            [$assignment,$custody,$profile,$recruiter]=$this->chain($root);
            $profile['profile']['model_binding']['model_id']='silently-substituted';$profile=$this->sealPrefixed($profile);
            $this->expectException(\RuntimeException::class);$this->expectExceptionMessage('R222_AUGUR_ACTIVATION_CHAIN_INVALID');
            (new AugurResidentActivationService($root,new GenericOfficerSubstrateRegistry(dirname(__DIR__,3))))->activate($assignment['assignment_id'],$custody,$profile,$recruiter);
        } finally {$this->removeTree($root);}
    }

    private function chain(string $root): array
    {
        $assertion=$this->sealPrefixed(['schema'=>'imperium.clavium-provider-access-assertion/v1','assertion_id'=>'clavium-openai-access-1','issuer'=>['office'=>'clavium','officer'=>'locksmith'],'provider'=>'openai','credential_ref'=>'clavium://providers/openai/default','scope'=>['model.invoke'],'status'=>'ACCESS_AVAILABLE']);
        $request=['target_seat'=>'oracle.augur','provider'=>'openai','model_id'=>'gpt-founding','model_version'=>'2026-08-01','configuration'=>['temperature'=>0.1,'maximum_output_tokens'=>4096],'clavium_access_assertion'=>$assertion];
        $act=$this->seal(['schema'=>'imperium.imperator-founding-augur-model-act/v1','act_id'=>'imperator-founding-augur-model-act-1','instance_id'=>'imperium-test','actor'=>['kind'=>'imperator','id'=>'imperator-development-root'],'charter_ref'=>['id'=>'primordial-charter','digest'=>'sha256:'.str_repeat('a',64)],'request'=>$request,'disposition'=>'APPROVED','founding_augur_model_assignment_authority'=>true]);
        $assignment=(new FoundingAugurModelAssignmentService($root))->authorize('imperium-test',$request,$act);
        $custody=$this->seal(['schema'=>'imperium.garrison-persona-custody/v1','custody_id'=>'garrison-custody-augur-1','instance_id'=>'imperium-test','persona_id'=>'persona-augur','persona_version'=>'1.0.0','persona_digest'=>'sha256:'.str_repeat('b',64),'custody_state'=>'ADMITTED_HELD','available'=>true,'sealed'=>true]);
        $profile=$this->sealPrefixed(['schema'=>'imperium.imperator-standing-officer-profile-approval/v1','approval_id'=>'standing-profile-approval-augur-1','instance_id'=>'imperium-test','profile'=>['profile_id'=>'profile-oracle-augur','profile_version'=>'1.0.0','content_digest'=>'sha256:'.str_repeat('c',64),'target_seat'=>'oracle.augur','persona_id'=>$custody['persona_id'],'persona_digest'=>$custody['persona_digest'],'model_binding'=>$assignment['model_binding']],'disposition'=>'APPROVED','status'=>'CURRENT_ACTIVE']);
        $recruiter=$this->seal(['schema'=>'imperium.conscription-recruiter-occupancy/v1','binding_id'=>'conscription-recruiter-binding-1','instance_id'=>'imperium-test','seat'=>'conscription.recruiter','manifestation_id'=>'manifestation-recruiter','occupancy_generation'=>1,'status'=>'ACTIVE','resident_manifestation_assembly_authority'=>true,'resident_seat_binding_authority'=>true,'model_selection_authority'=>false,'execution_authority'=>false]);
        return[$assignment,$custody,$profile,$recruiter];
    }
    private function seal(array$r):array{unset($r['record_digest']);$r['record_digest']=hash('sha256',CanonicalJson::encode($r));return$r;}
    private function sealPrefixed(array$r):array{unset($r['record_digest']);$r['record_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));return$r;}
    private function removeTree(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->removeTree($c):unlink($c);}rmdir($p);}
}
