<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProfileModelAccessAttestationService;
use PHPUnit\Framework\TestCase;

final class ProfileModelAccessAttestationServiceTest extends TestCase
{
    public function testLocksmithAttestsExactSealedBindingWithoutDisclosingOrReleasingCredential():void
    {
        $root=sys_get_temp_dir().'/imperium-profile-model-access-'.bin2hex(random_bytes(6));
        $authorizationId='mission-authorization-'.str_repeat('a',20);
        $sealingAuthorityId='model-binding-sealing-authority-'.str_repeat('b',20);
        $authorityId='profile-model-access-attestation-authority-'.str_repeat('c',20);
        $bindingId='profile-model-binding-'.str_repeat('d',20);
        $assertionId='clavium-provider-access-'.str_repeat('e',20);
        $profile=$this->profile(['contract_version'=>'1.0.0','profile_id'=>'profile-foundry-artificer','profile_version'=>'1.1.0','artifact_class'=>'officer','source_persona'=>['persona_id'=>'persona-artificer','persona_version'=>'1.0.0','persona_digest'=>'sha256:'.str_repeat('f',64),'admission_state'=>'admitted','evidence_record'=>'evidence-artificer'],'steward'=>['kind'=>'office','id'=>'foundry'],'target'=>['kind'=>'seat','id'=>'foundry.artificer'],'transformation'=>['case_id'=>'case-artificer','specification_version'=>'1.0.0','alchemist_disposition_id'=>'disposition-artificer'],'cognitive_payload'=>['role'=>'Artificer'],'model_binding'=>['provider_model_version'=>'openai/gpt-test@2026-08-01','authorization_id'=>$authorizationId,'source_line'=>['line_number'=>5,'line_digest'=>str_repeat('1',64)],'configuration'=>['temperature'=>0.2],'constraints'=>['No silent substitution.'],'fallbacks'=>['Return to Curia.'],'access_assertion_required'=>true],'qualification_contract'=>['contract_id'=>'qualification-artificer','criteria'=>['Preserve provenance.']],'limitations'=>['No execution authority.'],'lineage'=>['derived_from'=>'persona-artificer@1.0.0','supersedes'=>['profile_id'=>'profile-foundry-artificer','profile_version'=>'1.0.0','content_digest'=>'sha256:'.str_repeat('2',64)]],'digest_spec'=>['algorithm'=>'sha256','canonicalization'=>'rfc8785','omitted_fields'=>['content_digest']]]);
        $authorization=$this->record(['schema'=>'imperium.mission-authorization/v1','authorization_id'=>$authorizationId,'instance_id'=>'imperium-test','preparation_authorities'=>['profile_model_access_attestation'=>['decision-test'=>['authority_id'=>$authorityId,'authority_single_use'=>true,'source_binding_authority_id'=>$sealingAuthorityId,'target'=>['kind'=>'seat','id'=>'foundry.artificer'],'provider_model_version'=>'openai/gpt-test@2026-08-01','clavium_profile_access_attestation_authority'=>true,'credential_release_authority'=>false,'provider_invocation_authority'=>false,'execution_authority'=>false]]],'status'=>'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION']);
        $binding=$this->record(['schema'=>'imperium.conscription-profile-model-binding/v1','binding_id'=>$bindingId,'binding_authority'=>['id'=>$sealingAuthorityId,'consumed'=>true,'continuing_authority'=>false],'sealed_profile'=>$profile,'status'=>'PROFILE_MODEL_BINDING_SEALED_PENDING_ACCESS_AND_ACTIVATION_PREPARATION','sealed'=>true]);
        $assertion=$this->record(['schema'=>'imperium.clavium-provider-access-assertion/v1','assertion_id'=>$assertionId,'issuer'=>['office'=>'clavium','officer'=>'locksmith','seat'=>'clavium.locksmith','binding_id'=>'locksmith-binding','manifestation_id'=>'locksmith-manifestation','occupancy_generation'=>1],'provider'=>'openai','credential_ref'=>'clavium://providers/openai/default','scope'=>['model.invoke'],'observation'=>['method'=>'sterile-test-presence','observed_at'=>'2026-08-23T18:00:00+00:00','evidence'=>['configured'=>true]],'status'=>'ACCESS_AVAILABLE','checkpoint'=>'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY','restrictions'=>['region:us'],'revalidation'=>['expires_at'=>'2026-08-24T18:00:00+00:00','conditions'=>['expiry']],'credential_possession_transferred'=>false,'credential_use_authority'=>false,'credential_disclosure_authority'=>false,'provider_invocation_authority'=>false,'execution_authority'=>false,'sealed'=>true],true);
        $this->write($root.'/var/imperium/authorizations/missions/'.$authorizationId.'.json',$authorization);
        $this->write($root.'/var/imperium/offices/conscription/profile-model-bindings/'.$bindingId.'.json',$binding);
        $this->write($root.'/var/imperium/offices/clavium/provider-access-assertions/'.$assertionId.'.json',$assertion);
        try{
            $service=new ProfileModelAccessAttestationService($root);$at=new \DateTimeImmutable('2026-08-23T19:00:00+00:00');
            $result=$service->attest($authorizationId,$authorityId,$bindingId,$assertionId,$at);
            self::assertSame($result,$service->attest($authorizationId,$authorityId,$bindingId,$assertionId,$at->modify('+1 minute')));
            self::assertSame('ACCESS_AVAILABLE',$result['status']);
            self::assertSame('PROFILE_MODEL_ACCESS_ATTESTED_PENDING_APPROVAL_AND_ACTIVATION',$result['checkpoint']);
            self::assertSame($profile['content_digest'],$result['sealed_profile']['content_digest']);
            self::assertSame('openai/gpt-test@2026-08-01',$result['model_binding']['provider_model_version']);
            self::assertSame($assertionId,$result['provider_access_evidence']['assertion_id']);
            self::assertSame(['region:us'],$result['restrictions']);
            self::assertTrue($result['access_attestation_authority']['consumed']);
            self::assertArrayNotHasKey('credential_ref',$result['provider_access_evidence']);
            self::assertStringNotContainsString('clavium://',json_encode($result,JSON_THROW_ON_ERROR));
            foreach(['credential_reference_disclosed','credential_released','credential_use_authority','profile_approval_authority','profile_activation_authority','provider_invocation_authority','manifestation_assembly_authority','deployment_authority','execution_authority','fallback_activated']as$f)self::assertFalse($result[$f]);
        }finally{$this->remove($root);}
    }

    private function record(array$r,bool$prefixed=false):array{$r['record_digest']=($prefixed?'sha256:':'').hash('sha256',CanonicalJson::encode($r));return$r;}
    private function profile(array$r):array{$r['content_digest']='sha256:'.hash('sha256',CanonicalJson::encode($r));return$r;}
    private function write(string$p,array$r):void{if(!is_dir(dirname($p)))mkdir(dirname($p),0770,true);file_put_contents($p,json_encode($r,JSON_THROW_ON_ERROR));}
    private function remove(string$p):void{if(!is_dir($p))return;foreach(array_diff(scandir($p)?:[],['.','..'])as$e){$c=$p.'/'.$e;is_dir($c)?$this->remove($c):unlink($c);}rmdir($p);}
}
