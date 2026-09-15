<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;
use App\Imperium\Runtime\Onboarding\AuthorityAdmission\Rules as R;

/** Bounded designation custody. Never appoints, applies settings or dispatches. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class FormationProfileDesignation
{
    public const ACT = 'imperium.formation-profile-designation-act/v1';
    public const DELEGATION = 'imperium.formation-profile-designation-delegation/v1';
    public const EVENT = 'imperium.formation-profile-designation-event/v1';
    public const DESIGNATE = 'DESIGNATE_FORMATION_PROFILE';
    public const REVOKE = 'REVOKE_FORMATION_PROFILE_DESIGNATION';
    public const MAX = 256;
    public const MAX_BYTES = 16777216;
    public const MAX_PERSONNEL_BYTES = 262144;
    public const MAX_NATIVE_BYTES = 1048576;
    public const MAX_REFERENCED_BYTES = 33554432;
    public const TARGETS = ['courtyard.courtthane', 'clavium.locksmith'];

    public function __construct(private FormationJournal $journal, private FormationSignatures $signatures,
        private Clock $clock, private FormationInstitution $institution, private FormationPersonnel $personnel) {}

    public function delegate(array $terms, array $decision): array
    {
        return $this->journal->changeAtHead(function(array &$state, array $head, FormationOwnerFrame $owner)use($terms,$decision):array {
            $s=$this->history($state);
            self::shape($terms,['schema','instance_id','citadel_id','steward','target','purpose','actor','public_key','not_before','expires_at','expected_head']);
            $record=['terms'=>$terms,'decision'=>$decision]; $id=FormationJournal::digest($record);
            if(isset($s['delegations'][$id])) { return self::ref(self::DELEGATION,$id,$record); }
            self::need(count($s['delegations'])<self::MAX && R::same($terms['expected_head'],$head),'DELEGATION_HEAD_BOUND');
            self::need($terms['schema']===self::DELEGATION,'DELEGATION_SCHEMA');
            $this->scope($state,$terms); self::need(in_array($terms['purpose'],[self::DESIGNATE,self::REVOKE],true),'PURPOSE');
            self::need(R::same($terms['actor'],$this->institution->actorInOwner($owner,'laboratorium')),'ACTOR');
            $this->signatures->verify($state,$decision,'DELEGATE_FORMATION_PROFILE_DESIGNATION',$terms);
            foreach($s['delegations'] as $prior) { self::need($prior['decision']['payload']['nonce']!==$decision['payload']['nonce'],'DELEGATION_NONCE_CONFLICT'); }
            self::need(is_int($terms['not_before']) && is_int($terms['expires_at']) && $terms['not_before']<=$this->now()
                && $this->now()<$terms['expires_at'] && $terms['expires_at']<=$decision['payload']['expires_at'],'DELEGATION_TIME');
            self::key($terms['public_key']);
            $s['delegations'][$id]=$record; $state['profile_designations']=$s;
            $this->history($state);
            return self::ref(self::DELEGATION,$id,$record);
        });
    }

    /** Candidate originals are retained separately; every member is bound by the signed chain. */
    public function publish(array $envelope, array $candidate): array
    {
        return $this->journal->changeAtHead(function(array &$state,array $head,FormationOwnerFrame $owner)use($envelope,$candidate):array {
            self::need(strlen(CanonicalJson::encode([$envelope,$candidate]))<=1048576,'ACT_BYTE_BOUND');
            $s=$this->history($state); self::shape($envelope,['payload','signature']);
            $p=$envelope['payload']; $this->payloadShape($state,$p);
            foreach($s['events'] as $event) {
                $old=$event['envelope']['payload'];
                if(R::same([$old['actor'],$old['delegation_ref'],$old['nonce']],[$p['actor'],$p['delegation_ref'],$p['nonce']])) {
                    self::need(R::same($envelope,$event['envelope']) && R::same($candidate,$event['candidate']),'NONCE_CONFLICT');
                    return $event; // Historical recognition before time/head checks.
                }
            }
            self::need(count($s['events'])<self::MAX && R::same($p['expected_head'],$head),'HEAD_BOUND');
            $d=$this->delegation($s,$p); $this->currentDelegation($owner,$state,$d,$p);
            self::signature($envelope,$d['terms']['public_key']);
            self::need($p['not_before']<=$p['issued_at'] && $p['issued_at']<=$this->now() && $this->now()<$p['expires_at']
                && $p['not_before']>=$d['terms']['not_before'] && $p['expires_at']<=$d['terms']['expires_at'],'ACT_TIME');
            $previous=$this->last($s,$p['target']);
            if($previous!==null && $previous['envelope']['payload']['expires_at']<=$this->now()) { self::need($p['issued_at']>=$previous['envelope']['payload']['expires_at'],'TERMINAL_OBSERVATION_RESIGN'); }
            self::need(R::same($p['predecessor_ref'],$previous===null?null:self::eventRef($previous))
                && $p['designation_generation']===($previous===null?1:$previous['envelope']['payload']['designation_generation']+1),'PREDECESSOR_GENERATION');
            if($p['effect']===self::DESIGNATE) {
                $this->originals($state,$p,$candidate);
                $assembly=$this->personnel->authorizedModelCandidateInOwner($owner,$candidate,$p['citadel_id'],$p['target']);
                $this->continuingBounds($state,$p,$candidate);
                if($previous!==null) {
                    self::need(R::same($assembly['profile_artifact']['lineage']['supersedes']??null,$previous['envelope']['payload']['profile_ref']),'SUPERSEDES');
                }
                foreach($s['events'] as $prior) {
                    $q=$prior['envelope']['payload'];
                    if($q['effect']===self::DESIGNATE) { self::need(!R::same(array_intersect_key($q['profile_ref'],array_flip(['profile_id','profile_version'])),array_intersect_key($p['profile_ref'],array_flip(['profile_id','profile_version']))),'PROFILE_REUSE'); }
                }
            } else {
                self::need($previous!==null && $previous['envelope']['payload']['effect']===self::DESIGNATE,'REVOCATION_PREDECESSOR');
                $q=$previous['envelope']['payload'];
                foreach(['profile_ref','profile_evidence_ref','approval_ref','examination_ref','qualification_ref','model_binding','binding_generation'] as $field) {
                    self::need(R::same($q[$field],$p[$field]),'REVOCATION_ORIGINAL');
                }
                self::need(R::same($candidate,$previous['candidate']),'REVOCATION_CANDIDATE');
                // A terminal transition authenticates history, not renewed model authority.
                $this->originals($state,$p,$candidate);
            }
            $event=['envelope'=>$envelope,'candidate'=>$candidate,'attestations'=>$this->attestations($p,$previous,$candidate)];
            $id=FormationJournal::digest($envelope); $s['events'][$id]=$event;
            $s['current']=$this->index($s['events']); $state['profile_designations']=$s;
            $this->history($state); return $event;
        });
    }

    public function currentInOwner(FormationOwnerFrame $owner,string $target): array
    {
        $state=$this->journal->readInOwner($owner)['state']; $s=$this->history($state);
        self::need(in_array($target,self::TARGETS,true),'TARGET'); $event=$this->last($s,$target);
        self::need($event!==null,'CURRENT_ABSENT'); $p=$event['envelope']['payload'];
        self::need($p['effect']===self::DESIGNATE && $p['not_before']<=$this->now() && $this->now()<$p['expires_at'],'CURRENT_TERMINAL');
        $this->currentDelegation($owner,$state,$this->delegation($s,$p),$p);
        $assembly=$this->personnel->authorizedModelCandidateInOwner($owner,$event['candidate'],$p['citadel_id'],$target);
        $this->originals($state,$p,$event['candidate']);
        return ['event'=>$event,'assembly'=>$assembly];
    }

    /** Deterministic signed-history reconstruction. No clock-dependent index or pruning. */
    private function history(array $state): array
    {
        $s=$state['profile_designations']??[];
        self::shape($s,['schema','initialization','delegations','events','current']);
        self::need($s['schema']===FormationProfileDesignationInitialization::SCHEMA,'STATE_SCHEMA');
        self::shape($s['initialization'],['terms','decision']);
        $initial=$s['initialization']; self::shape($initial['terms'],['schema','citadel_id','expected_head']);
        self::need($initial['terms']['schema']===$s['schema'] && $initial['terms']['citadel_id']===$state['citadel_id'],'INITIALIZATION');
        $this->historicalDecision($state,$initial['decision'],'INITIALIZE_FORMATION_PROFILE_DESIGNATIONS',$initial['terms']);
        foreach(['delegations','events','current'] as $field) { self::need(is_array($s[$field]) && count($s[$field])<=self::MAX,'HISTORY_BOUND'); }
        self::need(strlen(CanonicalJson::encode($s))<=self::MAX_BYTES,'BYTE_BOUND');
        foreach($s['delegations'] as $id=>$d) {
            self::shape($d,['terms','decision']);
            self::shape($d['terms'],['schema','instance_id','citadel_id','steward','target','purpose','actor','public_key','not_before','expires_at','expected_head']);
            $this->scope($state,$d['terms']);
            self::need($d['terms']['schema']===self::DELEGATION && in_array($d['terms']['purpose'],[self::DESIGNATE,self::REVOKE],true),'DELEGATION_SCHEMA');
            self::key($d['terms']['public_key']);
            self::need(is_int($d['terms']['not_before']) && is_int($d['terms']['expires_at']) && $d['terms']['not_before']<$d['terms']['expires_at'] && $d['terms']['expires_at']<=$d['decision']['payload']['expires_at'],'DELEGATION_TIME');
            self::need($id===FormationJournal::digest($d),'DELEGATION_DIGEST');
            $this->historicalDecision($state,$d['decision'],'DELEGATE_FORMATION_PROFILE_DESIGNATION',$d['terms']);
        }
        $seen=[]; $nonces=[]; $profiles=[]; $lastHead=-1; $referencedBytes=0;
        foreach(self::ordered($s['events']) as $id=>$event) {
            self::shape($event,['envelope','candidate','attestations']); self::shape($event['envelope'],['payload','signature']);
            self::need($id===FormationJournal::digest($event['envelope']),'EVENT_DIGEST');
            $p=$event['envelope']['payload']; $this->payloadShape($state,$p);
            self::need(is_int($p['expected_head']['generation']) && $p['expected_head']['generation']>$lastHead,'HISTORY_HEAD'); $lastHead=$p['expected_head']['generation'];
            $d=$this->delegation($s,$p); self::signature($event['envelope'],$d['terms']['public_key']);
            self::need($p['not_before']<=$p['issued_at'] && $p['issued_at']<$p['expires_at']
                && $p['not_before']>=$d['terms']['not_before'] && $p['expires_at']<=$d['terms']['expires_at'],'HISTORY_TIME');
            $previous=$seen[$p['target']]??null;
            self::need(R::same($p['predecessor_ref'],$previous===null?null:self::eventRef($previous))
                && $p['designation_generation']===($previous===null?1:$previous['envelope']['payload']['designation_generation']+1),'HISTORY_GENERATION');
            $nonce=FormationJournal::digest([$p['actor'],$p['delegation_ref'],$p['nonce']]);
            self::need(!isset($nonces[$nonce]),'HISTORY_NONCE'); $nonces[$nonce]=true;
            $referencedBytes+=$this->originals($state,$p,$event['candidate']);
            self::need($referencedBytes<=self::MAX_REFERENCED_BYTES,'REFERENCED_HISTORY_BOUND');
            if($p['effect']===self::DESIGNATE) {
                $identity=FormationJournal::digest([$p['profile_ref']['profile_id'],$p['profile_ref']['profile_version']]); self::need(!isset($profiles[$identity]),'PROFILE_REUSE'); $profiles[$identity]=true;
                if($previous!==null) {
                    $artifact=$state['personnel_evidence'][$event['candidate']['profile']]['payload']['content']['artifact'];
                    self::need(R::same($artifact['lineage']['supersedes']??null,$previous['envelope']['payload']['profile_ref']),'HISTORY_LINEAGE');
                }
            } else {
                self::need($previous!==null && $previous['envelope']['payload']['effect']===self::DESIGNATE,'HISTORY_REVOCATION');
                foreach(['profile_ref','profile_evidence_ref','approval_ref','examination_ref','qualification_ref','model_binding','binding_generation'] as $field) {
                    self::need(R::same($p[$field],$previous['envelope']['payload'][$field]),'HISTORY_REVOCATION');
                }
                self::need(R::same($event['candidate'],$previous['candidate']),'HISTORY_REVOCATION');
            }
            self::need(R::same($event['attestations'],$this->attestations($p,$previous,$event['candidate'])),'ATTESTATIONS');
            $seen[$p['target']]=$event;
        }
        self::need(R::same($s['current'],$this->index($s['events'])),'INDEX'); return $s;
    }

    private function originals(array $state,array $p,array $candidate): int
    {
        self::shape($candidate,['persona','suitability','profile','examination','qualification','profile_approval']);
        $findings=$state['personnel_evidence'][$candidate['examination']]['payload']['content']['findings']??[];
        self::shape($findings,['consistency','governance','practice','security']);
        $ids=[$candidate['persona'],$candidate['suitability'],$candidate['profile'],$candidate['examination'],$candidate['qualification'],...array_values($findings)];
        $bytes=0;
        foreach($ids as $id) {
            $e=$state['personnel_evidence'][$id]??[];
            $bytes+=self::boundedOriginal($e,self::MAX_PERSONNEL_BYTES);
            self::need(FormationJournal::digest($e)===$id,'ORIGINAL');
            $d=$state['personnel_delegations'][$e['payload']['delegation']??'']??[];
            $bytes+=self::boundedOriginal($d,self::MAX_PERSONNEL_BYTES);
            self::need(FormationJournal::digest($d['terms']??[])===($e['payload']['delegation']??null),'ORIGINAL_DELEGATION');
            $this->historicalDecision($state,$d['decision']??[],'DELEGATE_PERSONNEL_EVIDENCE',$d['terms']??[]);
            self::signature($e,$d['terms']['public_key']);
        }
        $e=$state['personnel_evidence'][$candidate['profile']]; $content=$e['payload']['content'];
        self::need(($e['payload']['schema']??null)===FormationModelPreparation::EVIDENCE,'STRICT_V2');
        self::need(R::same($p['profile_ref'],array_intersect_key($content['artifact'],array_flip(['profile_id','profile_version','content_digest'])))
            && R::same($p['model_binding'],$content['correspondence']) && $p['binding_generation']===$content['correspondence']['binding_generation'],'MODEL_ORIGINAL');
        $chain=[];
        foreach(['persona','suitability','profile','examination','qualification'] as $field) { $chain[$field]=$state['personnel_evidence'][$candidate[$field]]['payload']; }
        self::need($chain['suitability']['sources']===[$candidate['persona']]
            && $chain['profile']['sources']===[$candidate['persona'],$candidate['suitability']]
            && $chain['examination']['sources']===[$candidate['profile'],...array_values($findings)]
            && $chain['qualification']['sources']===[$candidate['persona'],$candidate['suitability'],$candidate['profile'],$candidate['examination']]
            && $chain['qualification']['content']['profile_approval_digest']===FormationJournal::digest($candidate['profile_approval']),'ORIGINAL_CHAIN');
        foreach($findings as $criterion=>$id) {
            $finding=$state['personnel_evidence'][$id]['payload'];
            self::need($finding['sources']===[$candidate['profile']] && $finding['scope']===$p['citadel_id']
                && $finding['kind']==='SENATOR_FINDING'
                && $state['personnel_delegations'][$finding['delegation']]['terms']['role']==='senate-'.$criterion,'FINDING_ORIGINAL');
        }
        foreach(['profile_evidence_ref'=>'profile','examination_ref'=>'examination','qualification_ref'=>'qualification'] as $ref=>$field) {
            $original=$state['personnel_evidence'][$candidate[$field]];
            self::need(R::same($p[$ref],self::ref($original['payload']['schema']??'imperium.formation-personnel-evidence/v1',$candidate[$field],$original)),'TYPED_ORIGINAL');
        }
        $bytes+=self::boundedOriginal($candidate['profile_approval'],self::MAX_PERSONNEL_BYTES);
        self::need(R::same($p['approval_ref'],self::ref('imperium.citadel-owner-decision/v1',FormationJournal::digest($candidate['profile_approval']),$candidate['profile_approval'])),'APPROVAL_ORIGINAL');
        $this->historicalDecision($state,$candidate['profile_approval'],'APPROVE_FORMATION_PROFILE',
            ['profile'=>$candidate['profile'],'examination'=>$candidate['examination'],'scope'=>$p['citadel_id'],'seat'=>$p['target']]);
        foreach($this->historicalModel($state,$p,$content['artifact']) as $record) { $bytes+=self::boundedOriginal($record,self::MAX_NATIVE_BYTES); }
        return $bytes;
    }

    /** Cryptographic originals only: old expiry/revocation/tenure never renews. */
    private function historicalModel(array $state,array $p,array $profile): array
    {
        $m=$state['model_preparation']??[]; $c=$p['model_binding'];
        self::shape($c,['seal','binding_ref','configuration_ref','binding_generation']);
        $seal=self::nativeOriginal($m['seals']??[],$c['seal'],FormationModelPreparation::SEAL);
        $sp=$seal['body']['envelope']['payload'];
        $a=self::nativeOriginal($m['authorizations']??[],$sp['authorization'],FormationModelPreparation::AUTHORIZATION);
        $d=self::nativeOriginal($m['delegations']??[],$sp['delegation'],FormationModelPreparation::DELEGATION);
        $b=self::nativeOriginal($m['bindings']??[],$sp['binding'],FormationModelPreparation::BINDING);
        $t=$a['body']['terms'];
        self::need(R::same($t['binding'],FormationModelPreparation::reference($b))
            && R::same($t['delegation'],FormationModelPreparation::reference($d))
            && R::same($d['body']['terms']['binding'],FormationModelPreparation::reference($b))
            && R::same($sp['profile'],$profile) && R::same($seal['body']['profile'],$profile)
            && R::same($sp['source_profile'],$t['source_profile'])
            && R::same($sp['source_line'],FormationModelPreparation::sourceLine($b))
            && R::same($t['source_line'],$sp['source_line'])
            && R::same($m['consumed'][$a['id']]??null,$c['seal'])
            && $b['body']['context']['seat']===$p['target'],'HISTORICAL_MODEL_CORRESPONDENCE');
        foreach(['binding_ref','configuration_ref','binding_generation'] as $field) { self::need(R::same($c[$field],$b['body'][$field]),'HISTORICAL_MODEL_CORRESPONDENCE'); }
        foreach(['binding','configuration'] as $field) { self::need(R::same(R::reference($b['body'][$field.'_original']),$c[$field.'_ref']),'HISTORICAL_MODEL_ORIGINAL'); }
        $sourceRecord=$t['source_evidence']??[]; self::shape($sourceRecord,['schema','id','body','record_digest']);
        $source=self::nativeOriginal([$sourceRecord['id']=>$sourceRecord],$sp['source_evidence'],FormationModelPreparation::SOURCE);
        $original=$source['body']['envelope'];
        self::need(R::same($original,$state['personnel_evidence'][FormationJournal::digest($original)]??null),'HISTORICAL_SOURCE_ORIGINAL');
        $this->historicalDecision($state,$a['body']['decision'],'AUTHORIZE_FORMATION_MODEL_PREPARATION',$t);
        $this->historicalDecision($state,$d['body']['decision'],'DELEGATE_FORMATION_MODEL_SEALING',$d['body']['terms']);
        self::signature($seal['body']['envelope'],$d['body']['terms']['public_key']);
        return [$seal,$a,$d,$b];
    }

    private static function nativeOriginal(array $map,array $ref,string $schema): array
    {
        self::shape($ref,['schema','id','digest']); $r=$map[$ref['id']]??[];
        self::boundedOriginal($r,self::MAX_NATIVE_BYTES);
        self::shape($r,['schema','id','body','record_digest']); $unsigned=$r; unset($unsigned['record_digest']);
        self::need($ref['schema']===$schema && $r['schema']===$schema
            && R::same(FormationModelPreparation::reference($r),$ref)
            && FormationJournal::digest($unsigned)===$r['record_digest'],'HISTORICAL_NATIVE_ORIGINAL');
        return $r;
    }
    private static function boundedOriginal(array $record,int $maximum): int
    {
        $bytes=strlen(CanonicalJson::encode($record)); self::need($bytes<=$maximum,'REFERENCED_ORIGINAL_BOUND'); return $bytes;
    }

    private function continuingBounds(array $state,array $p,array $candidate): void
    {
        $limits=[$state['trust']['expires_at'],$candidate['profile_approval']['payload']['expires_at']];
        $examination=$state['personnel_evidence'][$candidate['examination']]['payload'];
        $ids=[$candidate['persona'],$candidate['suitability'],$candidate['profile'],$candidate['examination'],$candidate['qualification'],...array_values($examination['content']['findings'])];
        foreach($ids as $id) {
            $e=$state['personnel_evidence'][$id]['payload']; $d=$state['personnel_delegations'][$e['delegation']];
            $limits[]=$e['expires_at']; $limits[]=$d['terms']['expires_at']; $limits[]=$d['decision']['payload']['expires_at'];
        }
        $m=$state['model_preparation']; $seal=$m['seals'][$p['model_binding']['seal']['id']];
        $a=$m['authorizations'][$seal['body']['envelope']['payload']['authorization']['id']];
        $d=$m['delegations'][$a['body']['terms']['delegation']['id']];
        $limits[]=$a['body']['terms']['expires_at']; $limits[]=$a['body']['decision']['payload']['expires_at'];
        $limits[]=$d['body']['terms']['expires_at']; $limits[]=$d['body']['decision']['payload']['expires_at'];
        self::need($p['expires_at']<=min($limits),'CONTINUING_BOUNDS');
    }

    private function delegation(array $s,array $p): array
    {
        $d=$s['delegations'][$p['delegation_ref']['id']??'']??[];
        self::need(R::same($p['delegation_ref'],self::ref(self::DELEGATION,FormationJournal::digest($d),$d)),'DELEGATION_REF');
        foreach(['instance_id','citadel_id','steward','target','actor'] as $f) { self::need(R::same($p[$f],$d['terms'][$f]??null),'DELEGATION_SCOPE'); }
        self::need(($d['terms']['purpose']??null)===$p['effect'],'DELEGATION_PURPOSE'); return $d;
    }

    private function currentDelegation(FormationOwnerFrame $owner,array $state,array $d,array $p): void
    {
        $this->signatures->verify($state,$d['decision'],'DELEGATE_FORMATION_PROFILE_DESIGNATION',$d['terms']);
        self::need($d['terms']['not_before']<=$this->now() && $this->now()<$d['terms']['expires_at']
            && R::same($p['actor'],$this->institution->actorInOwner($owner,'laboratorium')),'CURRENT_ACTOR');
    }

    private function historicalDecision(array $state,array $e,string $effect,array $object): void
    {
        self::shape($e,['payload','signature']); $p=$e['payload'];
        self::shape($p,['schema','citadel_id','trust_fingerprint','effect','object_digest','issued_at','expires_at','nonce']);
        $trust=$state['trust']??[];
        self::need($p['schema']==='imperium.citadel-owner-decision/v1' && $p['effect']===$effect
            && $p['object_digest']===FormationJournal::digest($object) && $p['citadel_id']===$state['citadel_id']
            && $p['trust_fingerprint']===($trust['fingerprint']??null),'HISTORICAL_DECISION');
        self::signature($e,$trust['public_key']??'');
    }

    private function payloadShape(array $state,array $p): void
    {
        self::shape($p,['schema','effect','instance_id','citadel_id','steward','target','delegation_ref','actor','expected_head','predecessor_ref',
            'profile_ref','profile_evidence_ref','approval_ref','examination_ref','qualification_ref','model_binding','designation_generation','binding_generation',
            'not_before','issued_at','expires_at','nonce','correlation_id','reason']);
        self::need($p['schema']===self::ACT && in_array($p['effect'],[self::DESIGNATE,self::REVOKE],true),'ACT_SCHEMA'); $this->scope($state,$p);
        foreach(['designation_generation','binding_generation'] as $f) { self::need(is_int($p[$f]) && $p[$f]>=1 && $p[$f]<=self::MAX,'COUNTER'); }
        foreach(['not_before','issued_at','expires_at'] as $f) { self::need(is_int($p[$f]) && $p[$f]>=0,'TIME'); }
        foreach(['correlation_id','reason'] as $f) { self::need(is_string($p[$f]) && trim($p[$f])!=='' && strlen($p[$f])<=1024,'TEXT'); }
        self::need(is_string($p['nonce']) && preg_match('/^[a-f0-9]{48}$/D',$p['nonce'])===1,'NONCE');
        self::shape($p['expected_head'],['generation','digest']);
    }

    private function scope(array $state,array $p): void
    {
        self::need($p['instance_id']===($state['parent_instance_id']??null) && $p['citadel_id']===($state['citadel_id']??null)
            && R::same($p['steward'],['kind'=>'office','id'=>'laboratorium']) && in_array($p['target'],self::TARGETS,true),'SCOPE');
    }

    private function attestations(array $p,?array $previous,array $candidate): array
    {
        $result=[]; $prior=null;
        if($previous!==null) {
            $q=$previous['envelope']['payload']; $prior=end($previous['attestations']);
            if($q['effect']===self::DESIGNATE) {
                $terminal=$q['expires_at']<=$p['issued_at']?'expired':($p['effect']===self::REVOKE?'revoked':'superseded');
                $result[]=$this->attestation($p,$q['profile_ref'],'current_active',$terminal,$prior['attestation_id']);
                if($terminal==='expired' && $p['effect']===self::REVOKE) {
                    $expired=end($result);
                    $result[]=$this->attestation($p,$q['profile_ref'],null,'revoked',$expired['attestation_id']);
                }
            }
        }
        if($p['effect']===self::DESIGNATE) {
            $approval='profile-attestation-'.FormationJournal::digest([$candidate['profile_approval'],'approved']);
            $result[]=$this->attestation($p,$p['profile_ref'],'approved','current_active',$approval);
        }
        return $result;
    }

    private function attestation(array $p,array $profile,?string $from,string $to,string $prior): array
    {
        $a=['contract_version'=>'1.0.0','attestation_id'=>'designation-attestation-'.FormationJournal::digest([$p,$profile,$to]),
            'profile_ref'=>$profile,'transition'=>[...($from===null?[]:['from'=>$from]),'to'=>$to,'prior_attestation_id'=>$prior],
            'actor'=>['kind'=>'seat','id'=>'laboratorium.alchemist'],'issued_at'=>(new \DateTimeImmutable('@'.$p['issued_at']))->format(DATE_ATOM),
            'correlation_id'=>$p['correlation_id'],'reason'=>$p['reason'].'; signed act payload '.FormationJournal::digest($p)];
        return [...$a,'record_digest'=>'sha256:'.FormationJournal::digest($a)];
    }

    private function index(array $events): array
    {
        $index=[];
        foreach(self::ordered($events) as $event) {
            $p=$event['envelope']['payload']; $key=FormationJournal::digest([$p['instance_id'],$p['citadel_id'],$p['steward'],$p['target']]);
            $index[$key]=['last_event'=>self::eventRef($event),'active'=>$p['effect']===self::DESIGNATE?self::eventRef($event):null];
        }
        ksort($index,SORT_STRING); return $index;
    }
    private static function ordered(array $events): array { uasort($events,static fn(array $a,array $b):int=>$a['envelope']['payload']['expected_head']['generation']<=>$b['envelope']['payload']['expected_head']['generation']); return $events; }
    private function last(array $s,string $target): ?array { $last=null; foreach(self::ordered($s['events']) as $e) { if($e['envelope']['payload']['target']===$target) { $last=$e; } } return $last; }
    public static function eventRef(array $e): array { return self::ref(self::EVENT,FormationJournal::digest($e['envelope']),$e['envelope']); }
    public static function ref(string $schema,string $id,array $original): array { return ['schema'=>$schema,'id'=>$id,'digest'=>FormationJournal::digest($original)]; }
    private static function key(string $encoded): string { $key=base64_decode($encoded,true); self::need(is_string($key) && strlen($key)===32,'KEY'); return $key; }
    private static function signature(array $e,string $key): void { $sig=base64_decode($e['signature']??'',true); self::need(is_string($sig) && strlen($sig)===64 && sodium_crypto_sign_verify_detached($sig,CanonicalJson::encode($e['payload']),self::key($key)),'SIGNATURE'); }
    private static function shape(array $a,array $fields): void { self::need(FormationJournal::keys($a,$fields),'SHAPE'); }
    private static function need(bool $ok,string $code): void { if(!$ok) { throw new \RuntimeException('PPC6_'.$code); } }
    private function now(): int { return $this->clock->now()->getTimestamp(); }
}
