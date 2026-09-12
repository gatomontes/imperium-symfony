<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;
/** Internal verification only. Callers own the journal lock; facts are never a capability. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class CurrentAuthority
{
    public static function verify(AuthorityStore $store,array $s,array $authority,string $effect,array $termsRef): array {
            $store->currentTrust($s); $obligations=[]; $policy=null; $slot=null;
            $mode=$authority['kind']??''; Rules::effect($effect,$mode); Rules::supported($effect);
            if ($mode==='signed_act') {
                Rules::object($authority,['kind','act_ref','admission_ref']);
                $a=self::findAdmission($s,$authority['admission_ref']);
                Rules::require(Rules::same(Rules::reference($a['record']),$authority['act_ref']),'AUTHORITY_ACT_REF');
                $p=Act::verify($store,$s,$a['envelope'],$a['object']);
                Rules::require($p['effect']===$effect && Rules::same(Rules::reference($a['object']),$termsRef),'EXACT_TERMS');
                $terms=$store->checkSource($s,$termsRef);
                if ($p['policy_ref']!==null) { Admission::signedTerms($store->checkSource($s,$p['policy_ref']),$terms,$effect,$store->now(),fn(array $ref):array=>$store->checkSource($s,$ref)); }
            } else {
                Rules::object($authority,['kind','policy_ref','policy_admission_ref','slot_id','slot_digest','terms_ref','derivation_input_refs']);
                $a=self::findAdmission($s,$authority['policy_admission_ref']);
                Rules::require($a['envelope']['payload']['effect']==='AUTHORIZE_BOOTSTRAP_POLICY' && Rules::same(Rules::reference($a['object']),$authority['policy_ref']),'POLICY_ADMISSION');
                $policy=$store->checkSource($s,$authority['policy_ref']);
                Rules::require($policy['schema']==='imperium.operator-bootstrap-policy/v1' && $store->now()<$policy['body']['expires_at'],'POLICY_CURRENT');
                $slots=array_values(array_filter($policy['body']['effect_slots'],static fn(array $slot):bool=>$slot['slot_id']===$authority['slot_id']));
                Rules::require(count($slots)===1,'SLOT_MISSING'); $slot=$slots[0];
                Rules::require($slot['effect']===$effect && $slot['authority_mode']==='policy_effect' && Rules::hash($slot)===$authority['slot_digest'] && $store->now()<$slot['expires_at'],'SLOT_SCOPE');
                Rules::require(Rules::same($authority['terms_ref'],$termsRef),'TERMS_REF');
                Rules::refs($authority['derivation_input_refs']);
                 $derived=\App\Imperium\Runtime\Onboarding\Assignment\TermsDerivation::derive($s,$policy,$slot,fn(array $ref):array=>$store->checkSource($s,$ref));
                Rules::require(Rules::same($authority['derivation_input_refs'],$derived['derivation_input_refs']) && Rules::same($derived['terms_ref'],$termsRef),'EXACT_DERIVATION');
                $terms=$store->checkSource($s,$termsRef);
                // Even ordinary dependencies require future completed progression receipts.
                foreach ($slot['depends_on'] as $step) { $obligations[]=['kind'=>'completed_step','step_id'=>$step]; }
            }
            if (!in_array($effect,['AUTHORIZE_BOOTSTRAP_POLICY','REVOKE_BOOTSTRAP'],true)) {
                Admission::terms($terms,$effect);
                foreach ($terms['body']['required_completed_refs'] as $ref) { $obligations[]=['kind'=>'completed_ref','ref'=>$ref]; }
                // Holding an act is not readiness for founding, access, assessment or application.
                if (!in_array($effect,['ADMIT_BOOTSTRAP_EVIDENCE','APPROVE_RUNTIME_BINDING_MAP'],true)) { $obligations[]=['kind'=>'source_effect','effect'=>$effect]; }
            }
        return ['terms'=>$terms,'admission'=>$a,'obligations'=>$obligations];
    }
    private static function findAdmission(array $s,array $ref): array {
        Rules::ref($ref);
        foreach ($s['admissions'] as $key=>$receipt) {
            if (Rules::same(Rules::reference($receipt),$ref)) { return Admission::retained($s,$key); }
        }
        throw new \RuntimeException('O2_ADMISSION_MISSING');
    }
}
