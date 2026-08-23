<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;

final readonly class ProfileModelAccessAttestationService
{
    private string $authorizations;
    private string $bindings;
    private string $assertions;
    private string $attestations;

    public function __construct(string $root)
    {
        $this->authorizations=$root.'/var/imperium/authorizations/missions';
        $this->bindings=$root.'/var/imperium/offices/conscription/profile-model-bindings';
        $this->assertions=$root.'/var/imperium/offices/clavium/provider-access-assertions';
        $this->attestations=$root.'/var/imperium/offices/clavium/profile-model-access-attestations';
    }

    public function attest(string $authorizationId,string $authorityId,string $bindingId,string $assertionId,\DateTimeImmutable $attestedAt):array
    {
        $existing=$this->existing($authorityId);
        if(null!==$existing)return$existing;
        $authorization=$this->read($this->authorizations.'/'.$authorizationId.'.json','CL20_MISSION_AUTHORIZATION_ABSENT');
        $binding=$this->read($this->bindings.'/'.$bindingId.'.json','CL21_PROFILE_MODEL_BINDING_ABSENT');
        $assertion=$this->read($this->assertions.'/'.$assertionId.'.json','CL22_PROVIDER_ACCESS_ASSERTION_ABSENT');
        $authority=$this->authority($authorization,$authorityId);
        $profile=$binding['sealed_profile']??[];
        $model=$profile['model_binding']??[];
        $providerModel=$model['provider_model_version']??'';
        $provider=is_string($providerModel)?strstr($providerModel,'/',true):false;
        $expires=$assertion['revalidation']['expires_at']??null;

        if(!$this->recordOk($authorization,false)||'imperium.mission-authorization/v1'!==($authorization['schema']??null)
            ||'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION'!==($authorization['status']??null)
            ||true!==($authority['clavium_profile_access_attestation_authority']??null)||true!==($authority['authority_single_use']??null)
            ||true===($authority['credential_release_authority']??null)||true===($authority['provider_invocation_authority']??null)||true===($authority['execution_authority']??null)
            ||!$this->recordOk($binding,false)||'imperium.conscription-profile-model-binding/v1'!==($binding['schema']??null)
            ||'PROFILE_MODEL_BINDING_SEALED_PENDING_ACCESS_AND_ACTIVATION_PREPARATION'!==($binding['status']??null)||true!==($binding['sealed']??null)
            ||($authority['source_binding_authority_id']??null)!==($binding['binding_authority']['id']??null)
            ||($authority['target']??null)!==($profile['target']??null)||($authority['provider_model_version']??null)!==$providerModel
            ||!$this->profileOk($profile)||false===$provider
            ||!$this->recordOk($assertion,true)||'imperium.clavium-provider-access-assertion/v1'!==($assertion['schema']??null)
            ||'CLAVIUM_PROVIDER_ACCESS_ASSERTION_SEALED_NO_USE_AUTHORITY'!==($assertion['checkpoint']??null)||true!==($assertion['sealed']??null)
            ||$provider!==($assertion['provider']??null)||!in_array('model.invoke',$assertion['scope']??[],true)
            ||!is_string($expires)||new \DateTimeImmutable($expires)<=$attestedAt
            ||true===($assertion['credential_possession_transferred']??null)||true===($assertion['credential_use_authority']??null)
            ||true===($assertion['credential_disclosure_authority']??null)||true===($assertion['provider_invocation_authority']??null)||true===($assertion['execution_authority']??null)
        )throw new \RuntimeException('CL23_PROFILE_MODEL_ACCESS_CHAIN_INVALID');

        $status=match($assertion['status']??null){
            'ACCESS_AVAILABLE'=>'ACCESS_AVAILABLE',
            'ACCESS_UNAVAILABLE'=>'ACCESS_UNAVAILABLE',
            'ACCESS_RESTRICTED'=>'ACCESS_INDETERMINATE',
            'ACCESS_UNVERIFIED'=>'ACCESS_INDETERMINATE',
            default=>throw new \RuntimeException('CL23_PROFILE_MODEL_ACCESS_CHAIN_INVALID'),
        };
        $id='profile-model-access-attestation-'.substr(hash('sha256',CanonicalJson::encode([$authorizationId,$authorityId,$bindingId,$binding['record_digest'],$assertionId,$assertion['record_digest'],$attestedAt->format(DATE_ATOM)])),0,20);
        return $this->save($id,[
            'schema'=>'imperium.clavium-profile-model-access-attestation/v1','attestation_id'=>$id,'instance_id'=>$authorization['instance_id'],
            'mission_authorization'=>['id'=>$authorizationId,'digest'=>$authorization['record_digest']],
            'access_attestation_authority'=>['id'=>$authorityId,'consumed'=>true,'continuing_authority'=>false],
            'sealed_profile'=>['profile_id'=>$profile['profile_id'],'profile_version'=>$profile['profile_version'],'content_digest'=>$profile['content_digest'],'target'=>$profile['target']],
            'model_binding'=>['binding_id'=>$bindingId,'binding_digest'=>$binding['record_digest'],'provider_model_version'=>$providerModel],
            'provider_access_evidence'=>['assertion_id'=>$assertionId,'assertion_digest'=>$assertion['record_digest'],'provider'=>$provider,'issuer'=>$assertion['issuer'],'observed_at'=>$assertion['observation']['observed_at']??null,'expires_at'=>$expires],
            'status'=>$status,'restrictions'=>array_values(array_unique($assertion['restrictions']??[])),
            'attested_at'=>$attestedAt->format(DATE_ATOM),'issuer'=>['office'=>'clavium','officer'=>'locksmith','seat'=>'clavium.locksmith'],
            'credential_reference_disclosed'=>false,'credential_released'=>false,'credential_use_authority'=>false,'profile_approval_authority'=>false,
            'profile_activation_authority'=>false,'provider_invocation_authority'=>false,'manifestation_assembly_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,
            'fallback_activated'=>false,'sealed'=>true,
            'checkpoint'=>'PROFILE_MODEL_ACCESS_ATTESTED_PENDING_APPROVAL_AND_ACTIVATION',
        ]);
    }

    private function authority(array $a,string $id):array{foreach($a['preparation_authorities']['profile_model_access_attestation']??[]as$x)if($id===($x['authority_id']??null))return$x;throw new \RuntimeException('CL23_PROFILE_MODEL_ACCESS_CHAIN_INVALID');}
    private function profileOk(array $p):bool{$d=$p['content_digest']??null;unset($p['content_digest']);return is_string($d)&&hash_equals($d,'sha256:'.hash('sha256',CanonicalJson::encode($p)));}
    private function recordOk(array $r,bool $prefixed):bool{$d=$r['record_digest']??null;unset($r['record_digest']);$expected=($prefixed?'sha256:':'').hash('sha256',CanonicalJson::encode($r));return is_string($d)&&hash_equals($d,$expected);}
    private function existing(string $authorityId):?array{if(!is_dir($this->attestations))return null;foreach(glob($this->attestations.'/profile-model-access-attestation-*.json')?:[]as$p){$r=$this->read($p,'CL24_PROFILE_MODEL_ACCESS_ATTESTATION_FAILED');if($authorityId===($r['access_attestation_authority']['id']??null)){if(!$this->recordOk($r,false))throw new \RuntimeException('CL24_PROFILE_MODEL_ACCESS_ATTESTATION_FAILED');return$r;}}return null;}
    private function read(string $p,string $e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}
    private function save(string $id,array $r):array{if(!is_dir($this->attestations)&&!mkdir($this->attestations,0770,true)&&!is_dir($this->attestations))throw new \RuntimeException('CL24_PROFILE_MODEL_ACCESS_ATTESTATION_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->attestations.'/'.$id.'.json';if(is_file($p))return$this->read($p,'CL24_PROFILE_MODEL_ACCESS_ATTESTATION_FAILED');if(false===file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX))throw new \RuntimeException('CL24_PROFILE_MODEL_ACCESS_ATTESTATION_FAILED');return$r;}
}
