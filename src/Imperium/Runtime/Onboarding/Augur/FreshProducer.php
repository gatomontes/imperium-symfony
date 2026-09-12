<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\Augur;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore,Rules as R,Policy};
use App\Imperium\Runtime\Onboarding\Ledger\LedgerState;
use App\Imperium\Runtime\Bootstrap\OperatorRootOwnership;

/** Co-publishes the holder in CommandLedger's authority_consumed transaction. No nested locking or network I/O. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FreshProducer
{
    public function __construct(private OperatorRootOwnership $owner,private BaseProjection $base,private ConstitutionEvidence $evidence=new MissingConstitutionEvidence()){}
    public function current(AuthorityStore $store,array $s,array $ref):array
    {
        $this->owner->assertStore($store);
        $holder=Holder::current($store,$s,$ref);$b=$holder['body'];
        R::require($b['root_identity']===$this->owner->identity(),'ROOT_OWNER_MISMATCH');
        $originals=[];foreach($holder['sources'] as $source){$originals[R::key($source)]=$store->checkSource($s,$source);}
        $this->evidence->verify($originals[R::key($b['constitution_ref'])],$originals);
        $policy=$store->checkSource($s,$b['policy_ref']);$step=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']==='select-base'))[0];
        $proposal=$this->base->propose($store,['onboarding'=>$s],$policy,$step);
        R::require($proposal->status==='PROPOSED_BASE' && R::same($proposal->proposedBinding,$b['binding']),'HOLDER_BASE_CURRENT');return $holder;
    }
    public function publish(AuthorityStore $store,array &$s,array $policy,array $slot,array $terms,array $command,array $head):array
    {
        R::require($s['schema']==='imperium.onboarding-authority-state/v3' && $s['bindings']===[],'ROOT_ALREADY_CONSUMED');
        $this->owner->vacant($store);
        $originals=[];$load=function(array $ref)use($store,$s,&$originals):array{$h=$store->checkSource($s,$ref);$originals[R::key($ref)]=$h;return $h;};
        $derived=FoundingRule::derive($s,$policy,$slot,$load);R::require(R::same(R::reference($terms),$derived['terms_ref']),'FOUNDING_TERMS');
        $intent=Policy::content($load($terms['body']['terms']),'augur-founding-intent');$constitution=$load($intent['constitution_ref']);
        $grant=Policy::content($constitution,'augur-constitution');
        R::object($grant,['schema','instance_id','operator_id','root_identity','seat','charter_ref','persona_ref','profile_ref','not_before','expires_at']);
        R::require($grant['schema']==='imperium.augur-constitution/v1' && $grant['instance_id']===$store->instance && $grant['operator_id']===$store->operator
            && $grant['root_identity']===$this->owner->identity() && $grant['seat']==='oracle.augur' && R::time($grant['not_before'])<=$store->now() && $store->now()<R::time($grant['expires_at']),'CONSTITUTION_SCOPE');
        foreach(['charter_ref','persona_ref','profile_ref'] as $k){$load(R::ref($grant[$k]));}$this->evidence->verify($constitution,$originals);
        $base=LedgerState::step($s,$policy,'select-base');$step=array_values(array_filter($policy['body']['steps'],static fn(array $v):bool=>$v['step_id']==='select-base'))[0];
        $current=$this->base->propose($store,['onboarding'=>$s],$policy,$step);
        $snapshot=null;$frozen=$this->base->propose($store,['onboarding'=>$s],$policy,$step,$base['completion']['body']['completed_at'],$snapshot);
        R::require($current->status==='PROPOSED_BASE' && $frozen->status==='PROPOSED_BASE' && R::same($current->proposedBinding,$frozen->proposedBinding)
            && R::same($intent['binding_ref'],$frozen->proposedBinding['binding_ref']) && R::same($grant['profile_ref'],$frozen->input->context['profile_ref']),'FOUNDING_BASE_CHANGED');
        R::require(R::same(LedgerState::step($s,$policy,'map-base')['completion']['body']['result_refs'],[$frozen->proposedBinding['mapping_ref']]),'FOUNDING_MAPPING_COMPLETION');
        $body=['policy_ref'=>R::reference($policy),'command_ref'=>$command,'terms_ref'=>R::reference($terms),'constitution_ref'=>R::reference($constitution),
            'root_identity'=>$this->owner->identity(),'seat'=>'oracle.augur','generation'=>$head['generation']+1,'binding'=>$frozen->proposedBinding,
            'profile_ref'=>$grant['profile_ref'],'persona_ref'=>$grant['persona_ref'],'charter_ref'=>$grant['charter_ref'],'base_completion_ref'=>R::reference($base['completion']),
            'base_proposal'=>$snapshot,'expires_at'=>min($grant['expires_at'],$policy['body']['expires_at'],$slot['expires_at'])];
        $holder=$store->make('imperium.bootstrap-augur-holder/v1','augur-'.substr(R::hash($body),7,24),$body,Holder::sources($body,$terms));
        $key=[$body['root_identity']];$s['bindings'][LedgerState::key('binding',$key)]=['key'=>$key,'record'=>$holder];return [R::reference($holder)];
    }
}
