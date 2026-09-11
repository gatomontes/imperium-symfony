<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

/** Retains signed originals atomically; never completes a progressing F2 effect. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class Admission
{
    public function __construct(private AuthorityStore $store) {}
    public function retain(string $envelopeJson,string $objectJson,array $supportingJson=[]): array {
        Rules::require(array_is_list($supportingJson) && count($supportingJson)<=256,'BUNDLE_LIMIT');
        $e=Act::shape(StrictJson::decode($envelopeJson)); $h=Rules::record(StrictJson::decode($objectJson)); $this->store->identity($h);
        $bundle=[]; $raws=[]; $ids=[]; $size=strlen($envelopeJson);
        foreach ([$objectJson,...$supportingJson] as $raw) {
            Rules::require(is_string($raw),'RAW_BYTES'); $r=Rules::record(StrictJson::decode($raw)); $this->store->identity($r);
            $size+=strlen($raw); Rules::require($size<=4194304,'BUNDLE_BYTE_LIMIT');
            $k=Rules::key(Rules::reference($r)); Rules::require(!isset($bundle[$k]),'DUPLICATE_BUNDLE'); $bundle[$k]=$r; $raws[$k]=$raw;
            Rules::require(!isset($ids[$r['id']]),'BUNDLE_ID_CONFLICT'); $ids[$r['id']]=true;
        }
        return $this->store->journal->changeAtHead(function(array &$state,array $head) use($e,$h,$bundle,$raws,$envelopeJson,$objectJson):array {
            $s=$this->store->state($state); $p=$e['payload']; $nonce=$p['nonce'];
            $key=Rules::hash([$p['instance_id'],$p['trust_fingerprint'],$nonce]);
            $fingerprint=Rules::hash([$e,$h,$bundle]);
            if (isset($s['acts'][$key])) {
                $prior=$s['acts'][$key]; Rules::require($prior['fingerprint']===$fingerprint,'ADMISSION_CONFLICT');
                self::retained($s,$key);
                return ['status'=>'HISTORICAL_RECOGNITION','admission'=>$s['admissions'][$key],'current_authority'=>false,'effect_completed'=>false,'authority_consumed'=>false];
            }
            Rules::require(count($s['admissions'])<4096,'ADMISSION_LIMIT');
            Rules::require(Rules::same($head,$p['expected_head']),'STALE_HEAD'); Act::verify($this->store,$s,$e,$h);
            Rules::require($h['created_at']<=$this->store->now(),'FUTURE_SOURCE');
            $seen=[]; $visiting=[]; $visits=0;
            $visit=function(array $ref,int $depth=0) use(&$visit,&$seen,&$visiting,&$visits,$bundle,$s):array {
                Rules::require(++$visits<=8192,'SOURCE_VISIT_LIMIT');
                Rules::require($depth<=16,'SOURCE_DEPTH'); $k=Rules::key($ref);
                Rules::require(!isset($visiting[$k]),'SOURCE_CYCLE');
                if (!isset($bundle[$k])) { return $this->store->checkSource($s,$ref,$visiting,$depth,$visits); }
                $r=$bundle[$k];
                // Resupply cannot erase the first admission's expiry/revocation lineage.
                if (isset($s['evidence'][$k]) || isset($s['policies'][$k])) {
                    $retained=$this->store->checkSource($s,$ref,$visiting,$depth,$visits);
                    Rules::require(Rules::same($retained,$r),'SOURCE_IDENTITY');
                }
                $visiting[$k]=true; Rules::require($r['created_at']<=$this->store->now(),'FUTURE_SOURCE');
                foreach ($r['sources'] as $source) { $visit($source,$depth+1); }
                unset($visiting[$k]); $seen[$k]=true; return $r;
            };
            $visit(Rules::reference($h)); Rules::require(count($seen)===count($bundle),'UNREACHABLE_SUPPORT');
            foreach ($bundle as $k=>$record) {
                foreach (['evidence','policies'] as $map) {
                    foreach ($s[$map] as $existing) {
                        Rules::require($existing['record']['id']!==$record['id'] || Rules::same($existing['record'],$record),'RECORD_ID_CONFLICT');
                    }
                }
                if ($k!==Rules::key(Rules::reference($h))) { self::support($record); }
            }
            if ($p['effect']==='AUTHORIZE_BOOTSTRAP_POLICY') {
                Policy::validate($h,$visit); Rules::require($h['body']['expires_at']<=$p['expires_at'],'POLICY_ACT_EXPIRY');
            } elseif ($p['effect']==='REVOKE_BOOTSTRAP') {
                Rules::require($h['schema']==='imperium.bootstrap-revocation/v1','REVOCATION_SCHEMA');
                $b=Rules::object($h['body'],['target_kind','target_id','expected_head']); Rules::text($b['target_id']);
                Rules::require(Rules::same($b['expected_head'],$head),'REVOCATION_HEAD');
                $exists=match($b['target_kind']) {
                    'issuer'=>$b['target_id']===$this->store->operator,
                    'act'=>count(array_filter($s['acts'],static fn(array $a):bool=>$a['envelope']['payload']['nonce']===$b['target_id']))===1,
                    'policy'=>count(array_filter($s['policies'],static fn(array $a):bool=>$a['record']['id']===$b['target_id']))===1,
                    default=>false,
                };
                Rules::require($exists,'REVOCATION_TARGET');
                Rules::require(!isset($s['revocations'][$b['target_kind'].':'.$b['target_id']]),'ALREADY_REVOKED');
                $s['revocations'][$b['target_kind'].':'.$b['target_id']]=Rules::reference($h);
            } else {
                self::terms($h,$p['effect']);
                $policy=$this->store->checkSource($s,$p['policy_ref']);
                Rules::require(in_array($p['effect'],$policy['body']['allowed_effects'],true),'POLICY_EFFECT');
                self::signedTerms($policy,$h,$p['effect'],$this->store->now());
                $visit($h['body']['terms']);
                // Retention preserves prerequisites, but does not claim they have completed.
                foreach ($h['body']['required_completed_refs'] as $ref) { $visit($ref); }
                // Policy linkage is retained in the signed envelope. Requiring it in the
                // pre-enumerated object's H sources would make policy/object hashes circular.
            }
            $act=$this->store->make('imperium.bootstrap-retained-act/v1','act-'.$nonce,
                ['envelope'=>$e,'object_ref'=>Rules::reference($h),'raw_envelope_sha256'=>'sha256:'.hash('sha256',$envelopeJson),'raw_object_sha256'=>'sha256:'.hash('sha256',$objectJson)],[Rules::reference($h)]);
            $receipt=$this->store->make('imperium.bootstrap-original-admission/v1','admission-'.$nonce,
                ['act_ref'=>Rules::reference($act),'object_ref'=>Rules::reference($h),'predecessor_head'=>$head,'authority_consumed'=>false,'effect_completed'=>false],[Rules::reference($act),Rules::reference($h)]);
            foreach ($bundle as $k=>$record) {
                $map=$record['schema']==='imperium.operator-bootstrap-policy/v1'?'policies':'evidence';
                if (!isset($s[$map][$k])) { $s[$map][$k]=['record'=>$record,'raw'=>$raws[$k],'admission_key'=>$key]; }
            }
            $s['acts'][$key]=['record'=>$act,'envelope'=>$e,'object'=>$h,'raw_envelope'=>$envelopeJson,'raw_object'=>$objectJson,'fingerprint'=>$fingerprint];
            Rules::require(count($s['evidence'])+count($s['policies'])<=8192,'SOURCE_STORE_LIMIT');
            $s['admissions'][$key]=$receipt; if($s['schema']==='imperium.onboarding-authority-state/v2'){\App\Imperium\Runtime\Onboarding\Ledger\LedgerState::validate($s);} $state['onboarding']=$s;
            return ['status'=>'ORIGINAL_ADMITTED','admission'=>$receipt,'current_authority'=>false,'effect_completed'=>false,'authority_consumed'=>false];
        });
    }
    public static function support(array $h): void {
        if ($h['schema']==='imperium.bootstrap-proposed-terms/v1') { self::terms($h,$h['body']['effect']??''); return; }
        Rules::require($h['schema']==='imperium.bootstrap-source/v1','UNSUPPORTED_SOURCE_SCHEMA');
        $b=Rules::object($h['body'],['kind','content','content_digest','limitations']); Rules::text($b['kind']); Rules::text($b['content']); Rules::text($b['limitations']);
        Rules::require($b['content_digest']==='sha256:'.hash('sha256',$b['content']),'SOURCE_CONTENT_DIGEST');
    }
    public static function terms(array $h,string $effect): void {
        Rules::require($h['schema']==='imperium.bootstrap-proposed-terms/v1','PROPOSED_TERMS_REQUIRED');
        $b=Rules::object($h['body'],['effect','terms','required_completed_refs']); Rules::effect($effect,'signed_act'); Rules::supported($effect);
        Rules::require($b['effect']===$effect,'TERMS_EFFECT'); Rules::ref($b['terms']); Rules::refs($b['required_completed_refs']);
        $sources=array_map(Rules::key(...),$h['sources']);
        foreach ([$b['terms'],...$b['required_completed_refs']] as $ref) { Rules::require(in_array(Rules::key($ref),$sources,true),'TERMS_SOURCE_LINK'); }
    }
    public static function signedTerms(array $policy,array $object,string $effect,int $now): void {
        $matches=[];
        foreach ($policy['body']['effect_slots'] as $slot) {
            if ($slot['effect']!==$effect || $slot['authority_mode']!=='signed_act') { continue; }
            $rule=$slot['terms_rule'];
            if (($rule['kind']==='exact' && Rules::same($rule['object_ref'],Rules::reference($object)))
                || ($rule['kind']==='eligible_binding' && in_array(Rules::key(Rules::reference($object)),array_map(Rules::key(...),$rule['permitted_object_refs']),true))) {
                $matches[]=$slot;
            }
        }
        Rules::require($matches!==[],'SIGNED_TERMS_OUTSIDE_POLICY');
        // The envelope has no slot selector; never substitute another matching/live slot.
        Rules::require(count($matches)===1,'AMBIGUOUS_SIGNED_SLOT');
        Rules::require($policy['created_at']<=Rules::time($now) && $now<Rules::time($matches[0]['expires_at']),'SIGNED_SLOT_CURRENT');
    }
    public static function retained(array $s,string $key): array {
        $a=$s['acts'][$key]??null; $receipt=$s['admissions'][$key]??null;
        Rules::require(is_array($a) && is_array($receipt),'ADMISSION_MISSING'); $h=Rules::record($a['record']); Rules::record($receipt);
        Rules::require(Rules::same(StrictJson::decode($a['raw_envelope']),$a['envelope']) && Rules::same(StrictJson::decode($a['raw_object']),$a['object']),'ACT_BYTES');
        Rules::require(Rules::same($h['body']['envelope'],$a['envelope']) && Rules::same($h['body']['object_ref'],Rules::reference($a['object'])),'ACT_LINK');
        Rules::require($h['body']['raw_envelope_sha256']==='sha256:'.hash('sha256',$a['raw_envelope']) && $h['body']['raw_object_sha256']==='sha256:'.hash('sha256',$a['raw_object']),'ACT_RAW_DIGEST');
        Rules::require(Rules::same($receipt['body']['act_ref'],Rules::reference($h)) && Rules::same($receipt['body']['object_ref'],Rules::reference($a['object'])) && $receipt['body']['authority_consumed']===false && $receipt['body']['effect_completed']===false,'RECEIPT_LINK');
        return $a;
    }
}
