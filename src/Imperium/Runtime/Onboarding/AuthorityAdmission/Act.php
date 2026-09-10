<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;
use App\Bootstrap\CanonicalJson;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Act
{
    public static function shape(mixed $e): array {
        $e=Rules::object($e,['payload','signature']); $p=Rules::object($e['payload'],['schema','domain','instance_id','citadel_id','trust_fingerprint','issuer','effect','object_digest','policy_ref','expected_head','issued_at','expires_at','nonce']);
        Rules::require($p['schema']==='imperium.operator-bootstrap-act/v1' && $p['domain']==='IMPERIUM_OPERATOR_BOOTSTRAP_V1','ACT_DOMAIN');
        Rules::require(($p['issuer']['kind']??null)==='operator','UNSUPPORTED_ISSUER_SOURCE'); Rules::object($p['issuer'],['kind','id']); Rules::id($p['issuer']['id']);
        foreach (['instance_id','citadel_id'] as $k) { Rules::id($p[$k]); }
        Rules::digest($p['trust_fingerprint']); Rules::digest($p['object_digest']); Rules::head($p['expected_head']);
        Rules::time($p['issued_at']); Rules::time($p['expires_at']); Rules::require($p['issued_at']<$p['expires_at'],'ACT_INTERVAL');
        Rules::effect($p['effect'],'signed_act'); Rules::supported($p['effect']);
        if (in_array($p['effect'],['AUTHORIZE_BOOTSTRAP_POLICY','REVOKE_BOOTSTRAP'],true)) { Rules::require($p['policy_ref']===null,'POLICY_SELF_AUTHORIZATION'); }
        else { Rules::ref($p['policy_ref']); }
        Rules::require(is_string($p['nonce']) && preg_match('/\A[a-f0-9]{48}\z/',$p['nonce'])===1,'NONCE'); Rules::bytes($e['signature'],64); return $e;
    }
    public static function verify(AuthorityStore $store,array $s,array $envelope,array $object,bool $policy=true): array {
        $e=self::shape($envelope); $p=$e['payload']; $t=$store->currentTrust($s); $now=$store->now();
        Rules::require($p['instance_id']===$store->instance && $p['citadel_id']===$store->citadel && Rules::same($p['issuer'],$t['issuer']) && $p['trust_fingerprint']===$t['fingerprint'],'ACT_IDENTITY');
        Rules::require(in_array($p['effect'],$t['effects'],true),'ACT_COMPETENCE');
        Rules::require($t['not_before']<=$p['issued_at'] && $p['issued_at']<=$now && $now<$p['expires_at'] && $p['expires_at']<=$t['expires_at'],'ACT_TIME');
        Rules::require(!isset($s['revocations']['act:'.$p['nonce']]),'ACT_REVOKED');
        Rules::require($p['object_digest']===Rules::hash($object),'ACT_OBJECT');
        Rules::require(sodium_crypto_sign_verify_detached(Rules::bytes($e['signature'],64),CanonicalJson::encode($p),Rules::bytes($t['public_key'],32)),'SIGNATURE');
        if ($p['policy_ref']!==null) {
            $h=$store->lookup($s,$p['policy_ref']); Rules::require($h['schema']==='imperium.operator-bootstrap-policy/v1','POLICY_SOURCE');
            Rules::require(!isset($s['revocations']['policy:'.$h['id']]) && $now<$h['body']['expires_at'] && $p['expires_at']<=$h['body']['expires_at'],'POLICY_CURRENT');
            Rules::require(in_array($p['effect'],$h['body']['allowed_effects'],true),'POLICY_EFFECT');
            if ($policy) { $store->checkSource($s,$p['policy_ref']); }
        }
        return $p;
    }
}
