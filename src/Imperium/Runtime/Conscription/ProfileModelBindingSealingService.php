<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\CanonicalJson;

final readonly class ProfileModelBindingSealingService
{
    private string $authorizations;private string $profiles;private string $bindings;
    public function __construct(string$root){$this->authorizations=$root.'/var/imperium/authorizations/missions';$this->profiles=$root.'/var/imperium/profiles/current';$this->bindings=$root.'/var/imperium/offices/conscription/profile-model-bindings';}

    public function seal(string$authorizationId,string$authorityId,string$sourceProfileId,\DateTimeImmutable$sealedAt):array
    {
        $existing=$this->existing($authorityId);if(null!==$existing)return$existing;$a=$this->read($this->authorizations.'/'.$authorizationId.'.json','R180_MISSION_AUTHORIZATION_ABSENT');$p=$this->read($this->profiles.'/'.$sourceProfileId.'.json','R181_SOURCE_PROFILE_ABSENT');$authority=$this->authority($a,$authorityId);$target=$authority['target']??[];
        if(!$this->ok($a)||'imperium.mission-authorization/v1'!==($a['schema']??null)||'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION'!==($a['status']??null)||true!==($authority['conscription_profile_sealing_authority']??null)||true!==($authority['authority_single_use']??null)||true===($authority['provider_invocation_authority']??null)||true===($authority['execution_authority']??null)
            ||!$this->profileOk($p)||$sourceProfileId!==($p['profile_id']??null)||'officer'!==($p['artifact_class']??null)||'seat'!==($p['target']['kind']??null)||($target['id']??null)!==($p['target']['id']??null)||!is_array($authority['configuration']??null)||[]===$authority['configuration']||array_is_list($authority['configuration'])||$this->secret($authority['configuration'])
        )throw new \RuntimeException('R182_PROFILE_MODEL_BINDING_CHAIN_INVALID');
        $model=['provider_model_version'=>$authority['provider_model_version'],'authorization_id'=>$authorizationId,'source_line'=>$authority['source_line'],'configuration'=>$authority['configuration'],'constraints'=>array_values(array_unique(array_merge($a['mission_plan']['constraints']??[],$a['authorized_disclosures']['cost_time_retention_limits']??[]))),'fallbacks'=>$a['authorized_disclosures']['risks_contingencies_fallbacks']??[],'access_assertion_required'=>true];$profile=$p;unset($profile['content_digest']);$profile['profile_version']=$this->nextVersion($p['profile_version']);$profile['model_binding']=$model;$profile['lineage']['supersedes']=['profile_id'=>$p['profile_id'],'profile_version'=>$p['profile_version'],'content_digest'=>$p['content_digest']];$profile['content_digest']='sha256:'.hash('sha256',CanonicalJson::encode($profile));$id='profile-model-binding-'.substr(hash('sha256',CanonicalJson::encode([$authorizationId,$a['record_digest'],$authorityId,$p['content_digest'],$profile,$sealedAt->format(DATE_ATOM)])),0,20);
        return$this->save($id,['schema'=>'imperium.conscription-profile-model-binding/v1','binding_id'=>$id,'instance_id'=>$a['instance_id'],'mission_authorization'=>['id'=>$authorizationId,'digest'=>$a['record_digest']],'binding_authority'=>['id'=>$authorityId,'consumed'=>true,'continuing_authority'=>false],'source_profile'=>['profile_id'=>$p['profile_id'],'profile_version'=>$p['profile_version'],'content_digest'=>$p['content_digest']],'sealed_profile'=>$profile,'sealed_at'=>$sealedAt->format(DATE_ATOM),'sealer'=>['kind'=>'mechanical-service','office'=>'conscription','service'=>'profile-model-binding-sealing'],'profile_revision_created'=>true,'profile_designated_current_active'=>false,'access_assertion_attached'=>false,'credential_released'=>false,'provider_invoked'=>false,'manifestation_assembled'=>false,'deployed'=>false,'executed'=>false,'profile_activation_authority'=>false,'provider_invocation_authority'=>false,'deployment_authority'=>false,'execution_authority'=>false,'status'=>'PROFILE_MODEL_BINDING_SEALED_PENDING_ACCESS_AND_ACTIVATION_PREPARATION','sealed'=>true]);
    }

    private function authority(array$a,string$id):array{foreach($a['preparation_authorities']['model_binding_sealing']??[]as$x)if($id===($x['authority_id']??null))return$x;throw new \RuntimeException('R182_PROFILE_MODEL_BINDING_CHAIN_INVALID');}
    private function profileOk(array$p):bool{$digest=$p['content_digest']??null;unset($p['content_digest']);return is_string($digest)&&hash_equals($digest,'sha256:'.hash('sha256',CanonicalJson::encode($p)));}
    private function secret(array$v):bool{foreach($v as$k=>$x){if(is_string($k)&&in_array(strtolower($k),['secret','token','api_key','apikey','password','credential'],true))return true;if(is_array($x)&&$this->secret($x))return true;}return false;}
    private function nextVersion(mixed$v):string{if(!is_string($v)||1!==preg_match('/^(\d+)\.(\d+)\.(\d+)$/',$v,$m))throw new \RuntimeException('R182_PROFILE_MODEL_BINDING_CHAIN_INVALID');return$m[1].'.'.((int)$m[2]+1).'.0';}
    private function existing(string$a):?array{if(!is_dir($this->bindings))return null;foreach(glob($this->bindings.'/profile-model-binding-*.json')?:[]as$p){$r=$this->read($p,'R183_PROFILE_MODEL_BINDING_FAILED');if($a===($r['binding_authority']['id']??null)){if(!$this->ok($r))throw new \RuntimeException('R183_PROFILE_MODEL_BINDING_FAILED');return$r;}}return null;}
    private function read(string$p,string$e):array{if(!is_file($p))throw new \RuntimeException($e);return json_decode((string)file_get_contents($p),true,512,JSON_THROW_ON_ERROR);}private function ok(array$r):bool{$x=$r['record_digest']??null;unset($r['record_digest']);return is_string($x)&&hash_equals($x,hash('sha256',CanonicalJson::encode($r)));}
    private function save(string$id,array$r):array{if(!is_dir($this->bindings)&&!mkdir($this->bindings,0770,true)&&!is_dir($this->bindings))throw new \RuntimeException('R183_PROFILE_MODEL_BINDING_FAILED');$r['record_digest']=hash('sha256',CanonicalJson::encode($r));$p=$this->bindings.'/'.$id.'.json';if(is_file($p))return$this->read($p,'R183_PROFILE_MODEL_BINDING_FAILED');file_put_contents($p,json_encode($r,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR)."\n",LOCK_EX);return$r;}
}
