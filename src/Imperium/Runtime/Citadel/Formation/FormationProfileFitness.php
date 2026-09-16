<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\{AuthorityStore, Rules as R, StrictJson};

/** C2 originals, under the existing Formation fence. No signer or key is learned from ingress. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationProfileFitness
{
    public function __construct(private AuthorityStore $store, private FormationSignatures $signatures,
        private FormationInstitution $institutions, private FormationPersonnel $personnel) {}

    /** Preparation retains no authority. All original dependencies must already exist. */
    public function prepare(string $id, array $candidate, string $seat, array $policyRef, array $binding): array
    {
        return StrictJson::within(fn():array=>$this->prepareOriginal($id,$candidate,$seat,$policyRef,$binding));
    }

    private function prepareOriginal(string $id, array $candidate, string $seat, array $policyRef, array $binding): array
    {
        return $this->store->journal->inspect(function(array $frame, FormationOwnerFrame $owner) use($id,$candidate,$seat,$policyRef,$binding): array {
            $state=$frame['state']; $this->identity($owner,$state); $s=$this->store->state($state);
            $policy=$this->store->checkSource($s,$policyRef);
            ProfileFitnessContract::need($policy['schema']==='imperium.operator-bootstrap-policy/v1','FOUNDING_POLICY');
            $assembly=$this->personnel->authorizedModelCandidateInOwner($owner,$candidate,$this->store->citadel,$seat);
            $profile=$assembly['profile_artifact'];
            ProfileFitnessContract::need(R::same($binding,$state['personnel_evidence'][$candidate['profile']]['payload']['content']['correspondence']),'MODEL_CORRESPONDENCE');
            $body=['fitness_version'=>'ppc10-officer-fit-v1','seat'=>$seat,'profile_evidence_digest'=>$candidate['profile'],
                'profile_artifact_digest'=>$profile['content_digest'],'model_binding'=>$binding,
                'requirements_ref'=>$policy['body']['requirements_ref'],'workload_ref'=>$policy['body']['workload_ref'],
                'qualification_contract_digest'=>R::hash($profile['qualification_contract']),'obligations'=>ProfileFitnessContract::OBLIGATIONS];
            $sources=$this->dependencies($state,$candidate,$body);
            return ProfileFitnessContract::contract($this->store->make(ProfileFitnessContract::CONTRACT,$id,$body,$sources));
        });
    }

    public function delegate(string $contractRaw, array $candidate, array $terms, array $decision): array
    {
        return StrictJson::within(fn():array=>$this->delegateOriginal($contractRaw,$candidate,$terms,$decision));
    }

    private function delegateOriginal(string $contractRaw, array $candidate, array $terms, array $decision): array
    {
        ProfileFitnessContract::need(strlen($contractRaw)<=262144,'BYTE_LIMIT');
        $contract=ProfileFitnessContract::contract(StrictJson::decode($contractRaw)); ProfileFitnessContract::delegation($terms);
        ProfileFitnessContract::bounded($decision);
        return $this->store->journal->changeAtHead(function(array &$state,array $head,FormationOwnerFrame $owner)use($contractRaw,$contract,$candidate,$terms,$decision):array {
            $this->identity($owner,$state); $x=self::history($state); $ref=ProfileFitnessContract::nativeRef(ProfileFitnessContract::DELEGATION,$terms);
            $entry=['terms'=>$terms,'decision'=>$decision,'contract_ref'=>R::reference($contract),'candidate'=>$candidate];
            if(isset($x['delegations'][R::key($ref)])) {
                ProfileFitnessContract::need(R::same($entry,$x['delegations'][R::key($ref)])
                    && ($x['contracts'][R::key(R::reference($contract))]['raw']??null)===$contractRaw,'REPLAY_CONFLICT');
                return ['status'=>'HISTORICAL_RECOGNITION','reference'=>$ref,'current_authority'=>false];
            }
            $this->contractCurrent($owner,$state,$contract,$candidate);
            $this->signatures->verify($state,$decision,'DELEGATE_PROFILE_FITNESS',$terms);
            $this->delegationCurrent($owner,$state,$terms,$decision,$contract);
            foreach($x['delegations'] as $old) { ProfileFitnessContract::need($old['terms']['nonce']!==$terms['nonce'],'NONCE_CONFLICT'); }
            $key=R::key(R::reference($contract));
            if(isset($x['contracts'][$key])) { ProfileFitnessContract::need($x['contracts'][$key]['raw']===$contractRaw,'ORIGINAL_BYTES'); }
            else { self::room($x['contracts']); $x['contracts'][$key]=['record'=>$contract,'raw'=>$contractRaw]; }
            self::room($x['delegations']); $x['delegations'][R::key($ref)]=$entry;
            self::publish($state,$x); return ['status'=>'DELEGATED','reference'=>$ref,'current_authority'=>false];
        });
    }

    /** Public exercise bytes are inputs to the practice signer, never capability facts. */
    public function record(string $raw, ?string $exerciseRaw=null): array
    {
        return StrictJson::within(fn():array=>$this->recordOriginal($raw,$exerciseRaw));
    }

    private function recordOriginal(string $raw, ?string $exerciseRaw=null): array
    {
        ProfileFitnessContract::need(strlen($raw)<=262144 && ($exerciseRaw===null || strlen($exerciseRaw)<=262144),'BYTE_LIMIT');
        $e=ProfileFitnessContract::judgment(StrictJson::decode($raw));
        $exercise=$exerciseRaw===null?null:ProfileFitnessContract::exercise(StrictJson::decode($exerciseRaw));
        return $this->store->journal->changeAtHead(function(array &$state,array $head,FormationOwnerFrame $owner)use($raw,$e,$exerciseRaw,$exercise):array {
            $this->identity($owner,$state); $x=self::history($state); $ref=ProfileFitnessContract::nativeRef(ProfileFitnessContract::JUDGMENT,$e);
            $key=R::key($ref);
            if(isset($x['judgments'][$key])) {
                ProfileFitnessContract::need($x['judgments'][$key]['raw']===$raw,'REPLAY_CONFLICT');
                if($exercise!==null) { ProfileFitnessContract::need(($x['exercises'][R::key(R::reference($exercise))]['raw']??null)===$exerciseRaw,'REPLAY_CONFLICT'); }
                return ['status'=>'HISTORICAL_RECOGNITION','reference'=>$ref,'current_authority'=>false];
            }
            if($exercise!==null) {
                ProfileFitnessContract::need($e['payload']['obligation_id']==='workload_practice','UNRELATED_EXERCISE');
                $ek=R::key(R::reference($exercise));
                if(isset($x['exercises'][$ek])) { ProfileFitnessContract::need($x['exercises'][$ek]['raw']===$exerciseRaw,'ORIGINAL_BYTES'); }
                else { self::room($x['exercises']); $x['exercises'][$ek]=['record'=>$exercise,'raw'=>$exerciseRaw]; }
            }
            foreach($x['judgments'] as $old) {
                $p=$old['envelope']['payload']; ProfileFitnessContract::need($p['nonce']!==$e['payload']['nonce'],'NONCE_CONFLICT');
                // A different delegation cannot introduce a latest-wins choice for this contract.
                ProfileFitnessContract::need(!R::same([$p['fitness_contract_ref'],$p['obligation_id']],[$e['payload']['fitness_contract_ref'],$e['payload']['obligation_id']]),'DUPLICATE_OBLIGATION');
            }
            $this->judgmentCurrent($owner,$state,$x,$e);
            self::room($x['judgments']); $x['judgments'][$key]=['envelope'=>$e,'raw'=>$raw]; self::publish($state,$x);
            return ['status'=>'JUDGMENT_RETAINED','reference'=>$ref,'current_authority'=>false];
        });
    }

    public function revoke(array $terms,array $decision): array
    {
        return StrictJson::within(fn():array=>$this->revokeOriginal($terms,$decision));
    }

    private function revokeOriginal(array $terms,array $decision): array
    {
        ProfileFitnessContract::bounded($terms); ProfileFitnessContract::bounded($decision);
        ProfileFitnessContract::shape($terms,['delegation_digest','expected_head']); R::digest($terms['delegation_digest']); R::head($terms['expected_head']);
        return $this->store->journal->changeAtHead(function(array &$state,array $head,FormationOwnerFrame $owner)use($terms,$decision):array {
            $this->identity($owner,$state); $x=self::history($state); $key=$terms['delegation_digest'];
            if(isset($x['revocations'][$key])) {
                ProfileFitnessContract::need(R::same($x['revocations'][$key],['terms'=>$terms,'decision'=>$decision]),'REPLAY_CONFLICT');
                return ['status'=>'HISTORICAL_RECOGNITION','current_authority'=>false];
            }
            ProfileFitnessContract::need(R::same($head,$terms['expected_head']),'STALE_HEAD');
            ProfileFitnessContract::need(count(array_filter($x['delegations'],static fn(array $d):bool=>'sha256:'.FormationJournal::digest($d['terms'])===$key))===1,'DELEGATION_MISSING');
            $this->signatures->verify($state,$decision,'REVOKE_PROFILE_FITNESS_DELEGATION',$terms);
            self::room($x['revocations']); $x['revocations'][$key]=['terms'=>$terms,'decision'=>$decision]; self::publish($state,$x);
            return ['status'=>'DELEGATION_REVOKED','current_authority'=>false];
        });
    }

    /** This verifies C2 in addition to the complete original native candidate chain.
     * Mapping/designation/independent appointment must also be checked by the C1 consumer. */
    public function verifyInOwner(AuthorityStore $store,FormationOwnerFrame $owner,array $contract,array $candidate,array $judgmentRefs): void
    {
        ProfileFitnessContract::need($store===$this->store,'FIXED_STORE');
        StrictJson::within(function()use($owner,$contract,$candidate,$judgmentRefs):void {
            $state=$owner->frame()['state']; $this->identity($owner,$state); $x=self::history($state);
            $key=R::key(R::reference($contract)); ProfileFitnessContract::need(R::same($x['contracts'][$key]['record']??null,$contract),'CONTRACT_ORIGINAL');
            $this->contractCurrent($owner,$state,$contract,$candidate);
            ProfileFitnessContract::need(count($judgmentRefs)===7 && array_is_list($judgmentRefs),'COVERAGE'); R::refs($judgmentRefs); $seen=[];
            foreach($judgmentRefs as $ref) {
                $entry=$x['judgments'][R::key($ref)]??null; ProfileFitnessContract::need(is_array($entry),'JUDGMENT_MISSING'); $e=$entry['envelope']; $p=$e['payload'];
                ProfileFitnessContract::need(R::same($p['fitness_contract_ref'],R::reference($contract)) && !isset($seen[$p['obligation_id']]),'COVERAGE');
                $this->judgmentCurrent($owner,$state,$x,$e,$candidate); ProfileFitnessContract::need($p['result']==='PASS','NOT_PASS'); $seen[$p['obligation_id']]=true;
            }
            ProfileFitnessContract::need(count($seen)===7,'COVERAGE');
        });
    }

    private function identity(FormationOwnerFrame $owner,array $state): void
    {
        $owner->assertOwner($this->store->journal);
        ProfileFitnessContract::need(($state['parent_instance_id']??null)===$this->store->instance && ($state['citadel_id']??null)===$this->store->citadel,'IDENTITY');
    }
    private function dependencies(array $state,array $candidate,array $b): array
    {
        $seal=$b['model_binding']['seal']; $seal['digest']='sha256:'.$seal['digest'];
        return R::refs([$b['requirements_ref'],$b['workload_ref'],$b['model_binding']['binding_ref'],$b['model_binding']['configuration_ref'],$seal,
            ProfileFitnessContract::nativeRef(FormationModelPreparation::EVIDENCE,$state['personnel_evidence'][$candidate['profile']]),
            ProfileFitnessContract::nativeRef('imperium.formation-personnel-evidence/v1',$state['personnel_evidence'][$candidate['qualification']])]);
    }
    private function contractCurrent(FormationOwnerFrame $owner,array $state,array $contract,array $candidate): void
    {
        ProfileFitnessContract::contract($contract); $this->store->identity($contract); $b=$contract['body'];
        ProfileFitnessContract::need($contract['created_at']<=$this->store->now() && $b['profile_evidence_digest']===$candidate['profile'],'PROFILE');
        $assembly=$this->personnel->authorizedModelCandidateInOwner($owner,$candidate,$this->store->citadel,$b['seat']); $profile=$assembly['profile_artifact'];
        ProfileFitnessContract::need($b['profile_artifact_digest']===$profile['content_digest'] && $b['qualification_contract_digest']===R::hash($profile['qualification_contract']),'PROFILE');
        // authorizedModelCandidateInOwner just authenticated this exact correspondence
        // and all model originals. Equality avoids repeating that same lifecycle read.
        ProfileFitnessContract::need(R::same($b['model_binding'],$state['personnel_evidence'][$candidate['profile']]['payload']['content']['correspondence']),'MODEL_CORRESPONDENCE');
        ProfileFitnessContract::need(R::same($contract['sources'],$this->dependencies($state,$candidate,$b)),'CONTRACT_SOURCES');
        $s=$this->store->state($state); $this->store->checkSource($s,$b['requirements_ref']); $this->store->checkSource($s,$b['workload_ref']);
        ProfileFitnessContract::need(count($s['bindings'])===1,'FOUNDING_POLICY'); $holder=array_values($s['bindings'])[0]['record'];
        $policy=$this->store->checkSource($s,$holder['body']['policy_ref']);
        foreach(['requirements_ref','workload_ref'] as $field) { ProfileFitnessContract::need(R::same($b[$field],$policy['body'][$field]),'FOUNDING_INPUT'); }
    }
    private function delegationCurrent(FormationOwnerFrame $owner,array $state,array $d,array $decision,array $contract): void
    {
        ProfileFitnessContract::delegation($d); $p=$this->signatures->verify($state,$decision,'DELEGATE_PROFILE_FITNESS',$d); $b=$contract['body'];
        ProfileFitnessContract::need($d['instance_id']===$this->store->instance && $d['citadel_id']===$this->store->citadel
            && $d['seat']===$b['seat'] && $d['profile_evidence_digest']===$b['profile_evidence_digest']
            && R::same($d['fitness_contract_ref'],R::reference($contract)),'DELEGATION_SCOPE');
        ProfileFitnessContract::need(R::same($d['actor'],$this->institutions->actorInOwner($owner,$d['role'])),'CURRENT_ACTOR');
        ProfileFitnessContract::need($state['trust']['not_before']<=$d['not_before'] && $p['issued_at']<=$d['not_before']
            && $d['not_before']<=$this->store->now() && $this->store->now()<$d['expires_at'] && $d['expires_at']<=$p['expires_at'],'DELEGATION_CURRENT');
    }
    private function judgmentCurrent(FormationOwnerFrame $owner,array $state,array $x,array $e,?array $checkedCandidate=null): void
    {
        ProfileFitnessContract::judgment($e); $p=$e['payload']; $d=$x['delegations'][R::key($p['delegation_ref'])]??null;
        ProfileFitnessContract::need(is_array($d),'DELEGATION_MISSING');
        ProfileFitnessContract::need(!isset($x['revocations'][$p['delegation_ref']['digest']]),'DELEGATION_REVOKED');
        $contract=$x['contracts'][R::key($p['fitness_contract_ref'])]['record']??null; ProfileFitnessContract::need(is_array($contract),'CONTRACT_ORIGINAL');
        foreach(['instance_id','citadel_id','seat','profile_evidence_digest','fitness_contract_ref'] as $field) { ProfileFitnessContract::need(R::same($p[$field],$d['terms'][$field]),'JUDGMENT_SCOPE'); }
        $roles=array_column(ProfileFitnessContract::OBLIGATIONS,'producer_role','id'); ProfileFitnessContract::need($d['terms']['role']===$roles[$p['obligation_id']],'ROLE');
        ProfileFitnessContract::need($d['terms']['not_before']<=$p['issued_at'] && $p['issued_at']<=$this->store->now() && $this->store->now()<$p['expires_at'] && $p['expires_at']<=$d['terms']['expires_at'],'JUDGMENT_CURRENT');
        ProfileFitnessContract::need(sodium_crypto_sign_verify_detached(R::bytes($e['signature'],64),CanonicalJson::encode($p),R::bytes($d['terms']['public_key'],32)),'SIGNATURE');
        if($checkedCandidate===null) { $this->contractCurrent($owner,$state,$contract,$d['candidate']); }
        else { ProfileFitnessContract::need(R::same($checkedCandidate,$d['candidate']),'CANDIDATE'); }
        $this->delegationCurrent($owner,$state,$d['terms'],$d['decision'],$contract);
        $required=$contract['sources']; $allowed=[]; foreach($required as $ref) { $allowed[R::key($ref)]=true; }
        $practice=null;
        foreach($p['evidence_refs'] as $ref) {
            if(isset($allowed[R::key($ref)])) { continue; }
            $record=$x['exercises'][R::key($ref)]['record']??null;
            ProfileFitnessContract::need($p['obligation_id']==='workload_practice' && $practice===null && is_array($record),'FOREIGN_EVIDENCE');
            $practice=$record; $eb=$record['body']; $b=$contract['body']; $this->store->identity($record);
            ProfileFitnessContract::need($record['created_at']<=$p['issued_at'] && R::same($eb['workload_ref'],$b['workload_ref'])
                && R::same($eb['binding_ref'],$b['model_binding']['binding_ref'])
                && R::same($eb['profile_ref'],ProfileFitnessContract::nativeRef(FormationModelPreparation::EVIDENCE,$state['personnel_evidence'][$b['profile_evidence_digest']])),'EXERCISE_SCOPE');
            ProfileFitnessContract::need(R::same($record['sources'],$required),'EXERCISE_SOURCES');
            foreach($eb['duties'] as $duty) {
                foreach($duty['evidence_refs'] as $source) { ProfileFitnessContract::need(isset($allowed[R::key($source)]),'FOREIGN_EVIDENCE'); }
                if($p['result']==='PASS') { ProfileFitnessContract::need($duty['result']==='PASS','EXERCISE_NOT_PASS'); }
            }
        }
        // Every judgment explicitly cites the original Profile, model, qualification and workload, not just prose.
        foreach($required as $ref) { ProfileFitnessContract::need(in_array(R::key($ref),array_map(R::key(...),$p['evidence_refs']),true),'EVIDENCE_MISSING'); }
        ProfileFitnessContract::need($p['obligation_id']!=='workload_practice' || $practice!==null,'EXERCISE_MISSING');
    }
    public static function history(array $state): array
    {
        $x=$state['profile_fitness']??['schema'=>'imperium.profile-fitness-state/v1','contracts'=>[],'delegations'=>[],'judgments'=>[],'exercises'=>[],'revocations'=>[]];
        ProfileFitnessContract::shape($x,['schema','contracts','delegations','judgments','exercises','revocations']);
        ProfileFitnessContract::need($x['schema']==='imperium.profile-fitness-state/v1','STATE_SCHEMA');
        foreach(['contracts','delegations','judgments','exercises','revocations'] as $map) {
            ProfileFitnessContract::need(is_array($x[$map]) && ($x[$map]===[] || !array_is_list($x[$map])) && count($x[$map])<=256,'HISTORY_LIMIT');
        }
        foreach(['contracts','exercises'] as $map) { foreach($x[$map] as $key=>$v) {
            ProfileFitnessContract::shape($v,['record','raw']); ProfileFitnessContract::need(is_string($v['raw']) && strlen($v['raw'])<=262144,'BYTE_LIMIT');
            ProfileFitnessContract::need(R::same(StrictJson::decode($v['raw']),$v['record']) && R::key(R::reference($v['record']))===$key,'ORIGINAL_BYTES');
            $map==='contracts'?ProfileFitnessContract::contract($v['record']):ProfileFitnessContract::exercise($v['record']);
        }}
        foreach($x['delegations'] as $key=>$v) {
            ProfileFitnessContract::shape($v,['terms','decision','contract_ref','candidate']); ProfileFitnessContract::delegation($v['terms']); ProfileFitnessContract::bounded($v['decision']);
            ProfileFitnessContract::need($key===R::key(ProfileFitnessContract::nativeRef(ProfileFitnessContract::DELEGATION,$v['terms'])) && isset($x['contracts'][R::key($v['contract_ref'])]),'DELEGATION_ORIGINAL');
            self::historicalDecision($state,$v['decision'],'DELEGATE_PROFILE_FITNESS',$v['terms']);
        }
        foreach($x['judgments'] as $key=>$v) {
            ProfileFitnessContract::shape($v,['envelope','raw']); ProfileFitnessContract::need(is_string($v['raw']) && strlen($v['raw'])<=262144,'BYTE_LIMIT');
            ProfileFitnessContract::judgment($v['envelope']); ProfileFitnessContract::need(R::same(StrictJson::decode($v['raw']),$v['envelope']) && $key===R::key(ProfileFitnessContract::nativeRef(ProfileFitnessContract::JUDGMENT,$v['envelope'])),'ORIGINAL_BYTES');
            $p=$v['envelope']['payload']; $d=$x['delegations'][R::key($p['delegation_ref'])]??null;
            ProfileFitnessContract::need(is_array($d) && sodium_crypto_sign_verify_detached(R::bytes($v['envelope']['signature'],64),CanonicalJson::encode($p),R::bytes($d['terms']['public_key'],32)),'ORIGINAL_SIGNATURE');
        }
        foreach($x['revocations'] as $key=>$v) {
            ProfileFitnessContract::shape($v,['terms','decision']); ProfileFitnessContract::shape($v['terms'],['delegation_digest','expected_head']); R::head($v['terms']['expected_head']);
            ProfileFitnessContract::need($key===$v['terms']['delegation_digest'],'REVOCATION_ORIGINAL');
            self::historicalDecision($state,$v['decision'],'REVOKE_PROFILE_FITNESS_DELEGATION',$v['terms']);
        }
        self::historyBound($state,$x); return $x;
    }
    private static function historicalDecision(array $state,array $e,string $effect,array $terms): void
    {
        ProfileFitnessContract::bounded($e); ProfileFitnessContract::shape($e,['payload','signature']); $p=$e['payload'];
        ProfileFitnessContract::shape($p,['schema','citadel_id','trust_fingerprint','effect','object_digest','issued_at','expires_at','nonce']);
        ProfileFitnessContract::nonce($p['nonce']);
        ProfileFitnessContract::need($p['schema']==='imperium.citadel-owner-decision/v1' && $p['effect']===$effect
            && $p['object_digest']===FormationJournal::digest($terms) && $p['citadel_id']===($state['citadel_id']??null)
            && $p['trust_fingerprint']===($state['trust']['fingerprint']??null) && R::time($p['issued_at'])<R::time($p['expires_at'])
            && $state['trust']['not_before']<=$p['issued_at'] && $p['expires_at']<=$state['trust']['expires_at']
            && sodium_crypto_sign_verify_detached(R::bytes($e['signature'],64),CanonicalJson::encode($p),R::bytes($state['trust']['public_key']??null,32)),'DECISION_ORIGINAL');
    }
    private static function historyBound(array $state,array $x): void
    {
        $bytes=strlen(CanonicalJson::encode($x));
        foreach(['personnel_evidence','personnel_delegations','profile_designations','model_preparation'] as $field) { $bytes+=strlen(CanonicalJson::encode($state[$field]??[])); }
        ProfileFitnessContract::need($bytes<=33554432,'REFERENCED_HISTORY_LIMIT');
    }
    private static function room(array $map): void { ProfileFitnessContract::need(count($map)<256,'HISTORY_LIMIT'); }
    private static function publish(array &$state,array $x): void { self::historyBound($state,$x); $state['profile_fitness']=$x; }
}
