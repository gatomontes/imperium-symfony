<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;

use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\{CommandLedger,CompletionResolver};

/** Competent exact artifact approval before founding, current owner publication
 * afterward. Generic source admission alone never supplies founding authority. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class NativeConstitutionEvidence implements OwnerConstitutionEvidence
{
    public function verify(array $constitution,array $originals):void
    {
        throw new \RuntimeException('O2_CONSTITUTION_OWNER_FRAME_REQUIRED');
    }

    public function verifyInFrame(AuthorityStore $store,OperatorRootOwnership $owner,array $state,
        array $policy,array $constitution,array $originals,?array $holder):void
    {
        $owner->assertStore($store);
        R::require(R::same($policy,$store->checkSource($state,R::reference($policy)))
            && $policy['body']['installation_mode']==='FRESH','CONSTITUTION_POLICY');
        R::require(R::same($constitution,$store->checkSource($state,R::reference($constitution))),'CONSTITUTION_ORIGINAL');
        $grant=Policy::content($constitution,'augur-constitution');
        R::object($grant,['schema','instance_id','operator_id','root_identity','seat','charter_ref','persona_ref','profile_ref','not_before','expires_at']);
        R::require($grant['schema']==='imperium.augur-constitution/v1' && $grant['instance_id']===$store->instance
            && $grant['operator_id']===$store->operator && $grant['root_identity']===$owner->identity()
            && $grant['seat']==='oracle.augur' && R::time($grant['not_before'])<=$store->now()
            && $store->now()<R::time($grant['expires_at']),'CONSTITUTION_SCOPE');
        $refs=[];
        foreach(['charter_ref','persona_ref','profile_ref'] as $field){
            $ref=R::ref($grant[$field]);$original=$store->checkSource($state,$ref);$refs[]=$ref;
            R::require($original['schema']==='imperium.bootstrap-source/v1'
                && isset($originals[R::key($ref)]) && R::same($original,$originals[R::key($ref)]),'CONSTITUTION_ARTIFACT_ORIGINAL');
        }
        R::require(R::same(R::refs($refs),$constitution['sources']),'CONSTITUTION_ARTIFACT_CHAIN');
        $steps=array_values(array_filter($policy['body']['steps'],static fn(array $s):bool=>$s['step_id']==='found-augur'));
        R::require(count($steps)===1,'CONSTITUTION_STEP');
        // Reuse original admission, competent effect, finite terms derivation,
        // current trust/issuer/policy/act revocation and completion obligations.
        [, $slot,$facts]=(new CommandLedger($store))->authority($state,$policy,$steps[0]);
        R::require($slot['effect']==='CONSTITUTE_FOUNDING_AUGUR','CONSTITUTION_COMPETENCE');
        CompletionResolver::resolve($store,$state,$policy,$facts['obligations']);
        $intent=Policy::content($store->checkSource($state,$facts['terms']['body']['terms']),'augur-founding-intent');
        R::require(R::same($intent['constitution_ref'],R::reference($constitution)),'CONSTITUTION_APPROVED_ARTIFACTS');
        if($holder===null){
            R::require($state['bindings']===[],'ROOT_ALREADY_CONSUMED');$owner->vacant($store);
            return; // Approved for FRESH; deliberately no claim of publication.
        }
        $current=Holder::current($store,$state,R::reference($holder));
        R::require(R::same($current,$holder) && R::same($holder['body']['policy_ref'],R::reference($policy))
            && R::same($holder['body']['terms_ref'],R::reference($facts['terms']))
            && R::same($holder['body']['constitution_ref'],R::reference($constitution))
            && R::same($holder['body']['binding']['binding_ref'],$intent['binding_ref']),'CONSTITUTION_PUBLICATION');
        foreach(['charter_ref','persona_ref','profile_ref'] as $field){
            R::require(R::same($holder['body'][$field],$grant[$field]),'CONSTITUTION_RESIDENT_ARTIFACT');
        }
    }
}
