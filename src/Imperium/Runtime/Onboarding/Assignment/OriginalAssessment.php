<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Assignment;

use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,StrictJson};
use App\Imperium\Runtime\Onboarding\Augur\AugurAdapter;
use App\Imperium\Runtime\Onboarding\Ledger\{AssessmentGroups,LedgerState,ResponseEvidence,CompletionResolver};

/** Private bounded verification shared only by journal-owning services. */
trait OriginalAssessment
{
    private function originals(array $state, array $policyRef): array
    {
        $s=$this->store->state($state);
        $this->store->currentTrust($s);
        $policy=$this->store->checkSource($s,$policyRef);
        R::require($policy['schema']==='imperium.operator-bootstrap-policy/v1'
            && in_array($s['schema'],['imperium.onboarding-authority-state/v3','imperium.onboarding-authority-state/v4'],true),'ASSIGNMENT_ASSESSMENT_POLICY');
        R::require($s['source_fences']===[],'OUTCOME_UNKNOWN');
        $groups=[];$lineage=[];$sequence=null;
        foreach(['W1','W2','W3'] as $id){
            $definition=AssessmentGroups::group($policy,$id);
            R::require(count($definition['attempt_step_ids'])===4,'ASSIGNMENT_ASSESSMENT_BOUNDS');
            foreach($definition['attempt_step_ids'] as $step){
                $attempt=AssessmentGroups::outcome($s,$policy,$step);
                if($attempt!==null){$lineage[]=R::reference($attempt);}
            }
            $outcome=AssessmentGroups::success($s,$policy,$id);
            $sequence??=$outcome['sequence_ref'];
            R::require(R::same($sequence,$outcome['sequence_ref']),'ASSIGNMENT_ASSESSMENT_SEQUENCE');
            $matches=array_values(array_filter($s['claims'],static fn(array $claim):bool =>
                R::same(R::reference($claim['record']),$outcome['claim_ref'])));
            R::require(count($matches)===1,'OUTCOME_ORIGINAL_MISSING');
            $claim=$matches[0];$checkpoint=$claim['custody'][4];
            $envelope=ResponseEvidence::envelope($checkpoint['body']['response_envelope'],$claim,
                $claim['custody'][3]['body']['response_metadata']);
            R::require(R::same($outcome['response_refs'],[R::reference($checkpoint)])
                && R::same($outcome['usage_ref'],R::reference($checkpoint))
                && R::same($claim['settled'],$envelope['metadata']['usage']),'ASSIGNMENT_ASSESSMENT_CUSTODY');
            CompletionResolver::step($this->store,$s,$policy,$outcome['attempt_step_id']);
            $key=LedgerState::key('group',[$this->store->instance,$policy['record_digest'],$id]);
            $frozen=$s['group_inputs'][$key]??null;
            R::require(is_array($frozen),'GROUP_INPUT_MISSING');
            $parsed=$this->assessmentAdapter()->verifiedAssessment($this->store,$state,$policy,
                $claim['operation']['prepared'],$envelope);
            R::require($parsed->groupId===$id,'ASSIGNMENT_ASSESSMENT_GROUP');
            $groups[]=['outcome'=>$outcome,'claim'=>$claim['record'],'checkpoint'=>$checkpoint,
                'frozen_input'=>$frozen,'response'=>$parsed];
        }
        return ['policy'=>$policy,'groups'=>$groups,'lineage'=>$lineage];
    }
}
