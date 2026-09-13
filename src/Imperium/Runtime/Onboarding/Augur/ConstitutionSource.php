<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{Admission,AuthorityStore,Rules as R,StrictJson};

/** Prepare public originals for the existing FRESH admission path. Preparation
 * grants no competence and publishes no holder. Approval is the exact admitted
 * CONSTITUTE_FOUNDING_AUGUR terms, verified at the consuming owner boundary. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ConstitutionSource
{
    public static function prepare(AuthorityStore $store,OperatorRootOwnership $owner,
        array $charter,array $persona,array $profile,int $notBefore,int $expiresAt):array
    {
        $owner->assertStore($store);
        R::require(R::time($notBefore)<R::time($expiresAt),'CONSTITUTION_WINDOW');
        $refs=[];
        foreach([$charter,$persona,$profile] as $original){
            R::record($original);$store->identity($original);
            R::require($original['schema']==='imperium.bootstrap-source/v1','CONSTITUTION_ARTIFACT_SOURCE');
            Admission::support($original);
            $refs[]=R::reference($original);
        }
        R::refs($refs);
        $content=StrictJson::canonical(['schema'=>'imperium.augur-constitution/v1',
            'instance_id'=>$store->instance,'operator_id'=>$store->operator,'root_identity'=>$owner->identity(),
            'seat'=>'oracle.augur','charter_ref'=>$refs[0],'persona_ref'=>$refs[1],'profile_ref'=>$refs[2],
            'not_before'=>$notBefore,'expires_at'=>$expiresAt]);
        return $store->make('imperium.bootstrap-source/v1','constitution-'.substr(hash('sha256',$content),0,24),
            ['kind'=>'augur-constitution','content'=>$content,'content_digest'=>'sha256:'.hash('sha256',$content),
                'limitations'=>'Prepared exact FRESH artifacts. No admission, completed constitution, Profile fit or operational authority.'],$refs);
    }
}
