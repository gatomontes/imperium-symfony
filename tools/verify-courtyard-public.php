<?php
declare(strict_types=1);
/** Independent public-byte verification only; no root, signing, transport or state writes. */
require dirname(__DIR__).'/vendor/autoload.php';
use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Formation\FormationJournal as J;

if ($argc!==3) {fwrite(STDERR,"Usage: php tools/verify-courtyard-public.php formation-proof.json custody-proof.json\n"); exit(64);}
try {
    $formation=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR);
    $custody=json_decode(file_get_contents($argv[2]),true,512,JSON_THROW_ON_ERROR);
    $state=$custody['after']['state'];
    if ($state['courtthane']['seat']!=='courtyard.courtthane' || $state['courtthane']['officer_class']!=='LEGATE'
        || $state['courtthane']['decision']['payload']['effect']!=='APPOINT_COURTTHANE'
        || $custody['effect_counts']!==['issue'=>1,'consume'=>1,'dispatch'=>1]
        || $formation['courtthane']['seat']!=='courtyard.courtthane'
        || $formation['boundary']['network_calls']!==0 || $formation['boundary']['mission_execution']!==false
        || $formation['step_one_validation']['execution_authority']!==false) {throw new RuntimeException('Boundary mismatch');}
    foreach ([$custody['before'],$custody['after']] as $frame) {
        $digest=$frame['record_digest']; unset($frame['record_digest']);
        if ($digest!==J::digest($frame)) {throw new RuntimeException('Frame digest mismatch');}
    }
    $record=$custody['record']; $claim=$record['claim']; $session=$state['sessions'][$claim['session_id']];
    $attempt=$session['attempts'][$claim['attempt_id']]; $op=$claim['prepared_operation'];
    $body=$claim; unset($body['record_digest']);
    if (J::digest($body)!==$claim['record_digest'] || $attempt['claim']!==$claim || $attempt['admitted']!==$record
        || $claim['holder']!==$state['courtthane'] || $claim['request_digest']!==J::digest($attempt['request'])
        || base64_decode($op['request_bytes_base64'],true)!==CanonicalJson::encode($attempt['request'])
        || hash('sha256',base64_decode($op['wire_bytes_base64'],true))!==$op['wire_sha256']
        || $attempt['custody']['response_identity']!=='sha256:'.hash('sha256',$record['envelope']['response'])
        || $attempt['custody']['provider_response_id']!==$record['provider_response_id']
        || $attempt['custody']['provenance']!==$record['provider_provenance']
        || $claim['derivation']['lease']['scope']['prepared_operation_digest']!==J::digest($op)) {throw new RuntimeException('Claim/operation/custody mismatch');}
    $verified=[];
    foreach ([[$custody,$state['trust'],$state['personnel_delegations']],
        [$formation,$formation['handoff']['packet']['personnel_evidence']['public_trust'],$formation['handoff']['packet']['personnel_evidence']['delegations']]] as [$proof,$trust,$delegations]) {
        $walk=function(mixed $value) use(&$walk,&$verified,$trust,$delegations): void {
            if (!is_array($value)) {return;}
            if (isset($value['payload'],$value['signature'])) {
                $p=$value['payload']; $id=J::digest($value);
                $key=isset($p['delegation']) ? ($delegations[$p['delegation']]['terms']['public_key'] ?? null) : $trust['public_key'];
                if ($key===null || !sodium_crypto_sign_verify_detached(base64_decode($value['signature'],true),CanonicalJson::encode($p),base64_decode($key,true))) {
                    throw new RuntimeException('Public signature failed: '.$id);
                }
                $verified[$id]=true;
            }
            foreach ($value as $child) {$walk($child);}
        };
        $walk($proof);
    }
    echo json_encode(['status'=>'VERIFIED_PUBLIC_SYNTHETIC_BYTES','unique_signatures'=>count($verified),
        'claim'=>$claim['record_digest'],'wire_sha256'=>$op['wire_sha256'],'effect_counts'=>$custody['effect_counts'],
        'genuine_authority_or_custody_proven'=>false,'execution_authority'=>false],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR)."\n";
} catch (Throwable $e) {fwrite(STDERR,'REFUSED '.$e->getMessage()."\n"); exit(1);}
