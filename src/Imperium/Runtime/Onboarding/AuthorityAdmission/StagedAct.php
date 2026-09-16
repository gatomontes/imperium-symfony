<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

use App\Bootstrap\CanonicalJson;

/** Explicit successor domain. The existing Act::shape remains closed to it. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class StagedAct
{
    public const SCHEMA='imperium.operator-staged-assignment-act/v1';
    public const DOMAIN='IMPERIUM_OPERATOR_STAGED_ASSIGNMENT_V1';
    public static function shape(array $envelope): array
    {
        Rules::object($envelope,['payload','signature']); $p=Rules::object($envelope['payload'],['schema','domain','instance_id','citadel_id','trust_fingerprint','issuer','effect','object_digest','policy_ref','expected_head','issued_at','expires_at','nonce']);
        Rules::require($p['schema']===self::SCHEMA && $p['domain']===self::DOMAIN,'STAGED_ACT_DOMAIN');
        Rules::object($p['issuer'],['kind','id']); Rules::require($p['issuer']['kind']==='operator','STAGED_ISSUER'); Rules::id($p['issuer']['id']);
        foreach(['instance_id','citadel_id'] as $field) { Rules::id($p[$field]); }
        Rules::digest($p['trust_fingerprint']); Rules::digest($p['object_digest']); Rules::ref($p['policy_ref']); Rules::head($p['expected_head']);
        Rules::require($p['policy_ref']['schema']==='imperium.operator-bootstrap-policy/v1','STAGED_FOUNDING_POLICY');
        Rules::require(in_array($p['effect'],['AUTHORIZE_BOOTSTRAP_POLICY','APPLY_BOOTSTRAP_ASSIGNMENTS'],true),'STAGED_EFFECT');
        Rules::require(Rules::time($p['issued_at'])<Rules::time($p['expires_at']),'STAGED_INTERVAL');
        Rules::require(is_string($p['nonce']) && preg_match('/\A[a-f0-9]{48}\z/',$p['nonce'])===1,'NONCE'); Rules::bytes($envelope['signature'],64);
        return $envelope;
    }
    public static function verify(AuthorityStore $store,array $state,array $envelope,array $object): array
    {
        $p=self::shape($envelope)['payload']; $t=$store->currentTrust($state); $now=$store->now();
        Rules::require($p['instance_id']===$store->instance && $p['citadel_id']===$store->citadel
            && Rules::same($p['issuer'],$t['issuer']) && $p['trust_fingerprint']===$t['fingerprint'],'STAGED_ACT_IDENTITY');
        $effects=$p['effect']==='AUTHORIZE_BOOTSTRAP_POLICY'?['AUTHORIZE_BOOTSTRAP_POLICY','AUTHORIZE_BOOTSTRAP_ASSESSMENT','ADMIT_BOOTSTRAP_EVIDENCE','APPLY_BOOTSTRAP_ASSIGNMENTS']:['APPLY_BOOTSTRAP_ASSIGNMENTS'];
        foreach($effects as $effect) { Rules::require(in_array($effect,$t['effects'],true),'STAGED_ACT_COMPETENCE'); }
        Rules::require($t['not_before']<=$p['issued_at'] && $p['issued_at']<=$now && $now<$p['expires_at'] && $p['expires_at']<=$t['expires_at'],'STAGED_ACT_TIME');
        Rules::require(!isset($state['revocations']['act:'.$p['nonce']]),'ACT_REVOKED');
        Rules::require($p['object_digest']===Rules::hash($object),'STAGED_ACT_OBJECT');
        Rules::require(sodium_crypto_sign_verify_detached(Rules::bytes($envelope['signature'],64),CanonicalJson::encode($p),Rules::bytes($t['public_key'],32)),'STAGED_SIGNATURE');
        $policy=$store->checkSource($state,$p['policy_ref']);
        Rules::require($policy['schema']==='imperium.operator-bootstrap-policy/v1' && $p['expires_at']<=$policy['body']['expires_at'],'STAGED_POLICY_CURRENT');
        foreach(array_diff($effects,['AUTHORIZE_BOOTSTRAP_POLICY']) as $effect) { Rules::require(in_array($effect,$policy['body']['allowed_effects'],true),'STAGED_POLICY_COMPETENCE'); }
        Rules::require($object['schema']===($p['effect']==='AUTHORIZE_BOOTSTRAP_POLICY'?'imperium.staged-assignment-scope/v1':'imperium.staged-assignment-terms/v1'),'STAGED_OBJECT_DOMAIN');
        return $p;
    }
}
