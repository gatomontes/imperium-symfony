<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Imperium\Runtime\Citadel\Formation\{FormationOwnerFrame,FormationProfileDesignation,FormationPersonnel,FormationModelPreparation};
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\ResponseValidation\CandidateClaim;

/** Explicit dormant construction. Unknown substantive predicates refuse. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class NativeAssignmentEvidence implements OwnerAssignmentEvidence
{
    public function __construct(private AuthorityStore $store,private FormationProfileDesignation $designations,
        private FormationPersonnel $personnel,private FormationModelPreparation $models) {}

    public function verify(array $policy,array $assignments,array $originals,array $responses):void
    { throw new \RuntimeException('PPC6_LIVE_ASSIGNMENT_OWNER_REQUIRED'); }

    public function verifyInOwner(AuthorityStore $store,FormationOwnerFrame $owner,
        array $policy,array $assignments,array $originals,array $responses):void
    {
        // Direct native callers need the same bounded pure-parse lifetime that
        // application/settings already establish. No authority result is cached.
        \App\Imperium\Runtime\Onboarding\AuthorityAdmission\StrictJson::within(function()use($store,$owner,$policy,$assignments,$originals,$responses):void {
            $this->verifyCurrentInOwner($store,$owner,$policy,$assignments,$originals,$responses);
        });
    }

    private function verifyCurrentInOwner(AuthorityStore $store,FormationOwnerFrame $owner,
        array $policy,array $assignments,array $originals,array $responses):void
    {
        R::require($store===$this->store,'PPC6_FIXED_ASSIGNMENT_STORE');
        $owner->assertOwner($store->journal); $state=$owner->frame()['state'];
        R::require(($state['parent_instance_id']??null)===$store->instance && ($state['citadel_id']??null)===$store->citadel,'PPC6_ASSIGNMENT_IDENTITY');
        $s=$store->state($state);
        $load=function(array $ref)use($store,$s,$originals):array {
            $h=$store->checkSource($s,$ref);
            R::require(R::same($h,$originals[R::key($ref)]??null),'PPC6_ADMITTED_ORIGINAL'); return $h;
        };
        AssignmentRule::tuples($assignments,$policy,$load);
        foreach($assignments as $tuple) {
            $role=$tuple['role']; $current=$this->designations->currentInOwner($owner,$role);
            $event=$current['event']; $p=$event['envelope']['payload']; $assembly=$current['assembly'];
            $holder=$role==='courtyard.courtthane'?$this->personnel->currentCourtthaneInOwner($owner):$this->personnel->currentLocksmithInOwner($owner);
            $this->personnel->authorizedModelCandidateInOwner($owner,$holder['candidate'],$store->citadel,$role);
            R::require(R::same($holder['candidate'],$event['candidate']) && R::same($holder['profile_artifact'],$assembly['profile_artifact'])
                && $holder['generation']===$tuple['profile_generation'],'PPC6_INDEPENDENT_HOLDER');
            $mapping=Policy::content($load($tuple['profile_ref']),'formation-profile-mapping');
            R::object($mapping,['schema','instance_id','formation_citadel_id','seat','holder_generation','profile_evidence_digest','profile_artifact']);
            R::require(R::same($mapping,['schema'=>'imperium.formation-profile-mapping/v1','instance_id'=>$store->instance,
                'formation_citadel_id'=>$store->citadel,'seat'=>$role,'holder_generation'=>$holder['generation'],
                'profile_evidence_digest'=>$holder['candidate']['profile'],'profile_artifact'=>$holder['profile_artifact']]),'PPC6_FULL_PROFILE_MAPPING');
            $this->models->verifyInOwner($owner,$holder['profile_artifact'],$role,$p['model_binding']);
            foreach(['binding_ref','configuration_ref','binding_generation'] as $field) {
                R::require(R::same($tuple[$field],$p['model_binding'][$field]),'PPC6_EXACT_NATIVE_BINDING');
            }
            $seal=$state['model_preparation']['seals'][$p['model_binding']['seal']['id']];
            $authorization=$state['model_preparation']['authorizations'][$seal['body']['envelope']['payload']['authorization']['id']];
            $binding=$state['model_preparation']['bindings'][$authorization['body']['terms']['binding']['id']];
            foreach(['provider','model_id','model_version'] as $field) { R::require($tuple[$field]===$binding['body']['specification'][$field],'PPC6_MODEL_IDENTITY'); }
            R::require(R::same($load($tuple['binding_ref']),$binding['body']['binding_original'])
                && R::same($load($tuple['configuration_ref']),$binding['body']['configuration_original']),'PPC6_NATIVE_ORIGINAL_BYTES');
        }
        $requirements=Policy::content($load($policy['body']['requirements_ref']),'requirements');
        foreach(array_diff($requirements['required_predicates'],CandidateClaim::PREDICATES) as $predicate) {
            // These three facts were independently proved for both members above.
            // Arbitrary admitted PASS declarations (including historical profile.fits)
            // cannot define or satisfy additional substantive predicates.
            R::require(in_array($predicate,['profile.current_active','profile.exact_model_binding','profile.independent_appointment'],true),
                'PPC6_SUBSTANTIVE_PROFILE_PREDICATE_UNAVAILABLE');
        }
    }
}
