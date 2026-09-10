<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

/** Read-only observations. No result is an execution/consumption capability. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Resolver
{
    public function __construct(private AuthorityStore $store) {}
    public function original(array $ref): array {
        return $this->store->journal->inspect(function(array $frame)use($ref):array {
            $s=$this->store->state($frame['state']); $this->store->currentTrust($s);
            return $this->store->checkSource($s,$ref);
        });
    }
    public function current(array $authority,string $effect,array $termsRef): array {
        return $this->store->journal->inspect(function(array $frame)use($authority,$effect,$termsRef):array {
            $s=$this->store->state($frame['state']); $this->store->currentTrust($s);
            $mode=$authority['kind']??''; Rules::effect($effect,$mode); Rules::supported($effect);
            if ($mode==='signed_act') {
                Rules::object($authority,['kind','act_ref','admission_ref']);
                $a=$this->findAdmission($s,$authority['admission_ref']);
                Rules::require(Rules::same(Rules::reference($a['record']),$authority['act_ref']),'AUTHORITY_ACT_REF');
                $p=Act::verify($this->store,$s,$a['envelope'],$a['object']);
                Rules::require($p['effect']===$effect && Rules::same(Rules::reference($a['object']),$termsRef),'EXACT_TERMS');
                $terms=$this->store->checkSource($s,$termsRef);
                if ($p['policy_ref']!==null) { Admission::signedTerms($this->store->checkSource($s,$p['policy_ref']),$terms,$effect,$this->store->now()); }
            } else {
                Rules::object($authority,['kind','policy_ref','policy_admission_ref','slot_id','slot_digest','terms_ref','derivation_input_refs']);
                $a=$this->findAdmission($s,$authority['policy_admission_ref']);
                Rules::require($a['envelope']['payload']['effect']==='AUTHORIZE_BOOTSTRAP_POLICY' && Rules::same(Rules::reference($a['object']),$authority['policy_ref']),'POLICY_ADMISSION');
                $policy=$this->store->checkSource($s,$authority['policy_ref']);
                Rules::require($policy['schema']==='imperium.operator-bootstrap-policy/v1' && $this->store->now()<$policy['body']['expires_at'],'POLICY_CURRENT');
                $slots=array_values(array_filter($policy['body']['effect_slots'],static fn(array $slot):bool=>$slot['slot_id']===$authority['slot_id']));
                Rules::require(count($slots)===1,'SLOT_MISSING'); $slot=$slots[0];
                Rules::require($slot['effect']===$effect && $slot['authority_mode']==='policy_effect' && Rules::hash($slot)===$authority['slot_digest'] && $this->store->now()<$slot['expires_at'],'SLOT_SCOPE');
                Rules::require(Rules::same($authority['terms_ref'],$termsRef),'TERMS_REF');
                Rules::refs($authority['derivation_input_refs']);
                Rules::require($slot['terms_rule']['kind']==='exact','DYNAMIC_PREREQUISITES_MISSING');
                Rules::require($authority['derivation_input_refs']===[] && Rules::same($slot['terms_rule']['object_ref'],$termsRef),'EXACT_DERIVATION');
                $terms=$this->store->checkSource($s,$termsRef);
                // Even ordinary dependencies require future completed progression receipts.
                Rules::require($slot['depends_on']===[],'DYNAMIC_PREREQUISITES_MISSING');
            }
            if (!in_array($effect,['AUTHORIZE_BOOTSTRAP_POLICY','REVOKE_BOOTSTRAP'],true)) {
                Admission::terms($terms,$effect);
                Rules::require($terms['body']['required_completed_refs']===[],'DYNAMIC_PREREQUISITES_MISSING');
                // Holding an act is not readiness for founding, access, assessment or application.
                Rules::require($effect==='ADMIT_BOOTSTRAP_EVIDENCE' || $effect==='APPROVE_RUNTIME_BINDING_MAP','DYNAMIC_PREREQUISITES_MISSING');
            }
            return ['status'=>'CURRENT_STATIC_AUTHORITY_PENDING_EXECUTION','observed_head'=>['generation'=>$frame['generation'],'digest'=>$frame['record_digest']],
                'effect'=>$effect,'terms_ref'=>$termsRef,'execution_authority'=>false,'authority_consumed'=>false,'effect_completed'=>false];
        });
    }
    private function findAdmission(array $s,array $ref): array {
        Rules::ref($ref);
        foreach ($s['admissions'] as $key=>$receipt) {
            if (Rules::same(Rules::reference($receipt),$ref)) { return Admission::retained($s,$key); }
        }
        throw new \RuntimeException('O2_ADMISSION_MISSING');
    }
}
