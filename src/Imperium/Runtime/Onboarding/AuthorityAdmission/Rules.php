<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\AuthorityAdmission;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Onboarding\Selection\Shape;

#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class Rules
{
    public const EFFECTS = ['AUTHORIZE_BOOTSTRAP_POLICY', 'ADMIT_BOOTSTRAP_EVIDENCE', 'AUTHORIZE_BOOTSTRAP_ACCESS', 'CONSTITUTE_FOUNDING_AUGUR', 'APPROVE_STANDING_AUGUR_PROFILE', 'DESIGNATE_STANDING_AUGUR_PROFILE', 'QUALIFY_BIND_AUGUR', 'AUTHORIZE_AUGUR_CUTOVER', 'AUTHORIZE_BOOTSTRAP_ASSESSMENT', 'APPLY_BOOTSTRAP_ASSIGNMENTS', 'APPROVE_RUNTIME_BINDING_MAP', 'REVOKE_BOOTSTRAP'];
    public const DERIVED = ['ADMIT_BOOTSTRAP_EVIDENCE', 'AUTHORIZE_BOOTSTRAP_ACCESS', 'CONSTITUTE_FOUNDING_AUGUR', 'AUTHORIZE_BOOTSTRAP_ASSESSMENT', 'APPLY_BOOTSTRAP_ASSIGNMENTS', 'APPROVE_RUNTIME_BINDING_MAP'];
    public const UNSUPPORTED = ['APPROVE_STANDING_AUGUR_PROFILE', 'DESIGNATE_STANDING_AUGUR_PROFILE', 'QUALIFY_BIND_AUGUR', 'AUTHORIZE_AUGUR_CUTOVER'];
    public static function require(bool $ok, string $code): void { if (!$ok) { throw new \RuntimeException('O2_'.$code); } }
    public static function object(mixed $v, array $keys): array { return Shape::object($v, $keys); }
    public static function text(mixed $v): string { $s = Shape::text($v); self::require(strlen($s) <= 65536, 'TEXT_LIMIT'); return $s; }
    public static function id(mixed $v): string { self::require(is_string($v) && preg_match('/\A[a-z0-9][a-z0-9._-]{7,79}\z/', $v) === 1, 'ID'); return $v; }
    public static function integer(mixed $v): int { self::require(is_int($v) && $v >= 0 && PHP_INT_SIZE === 8, 'INTEGER'); return $v; }
    public static function time(mixed $v): int { $v = self::integer($v); self::require($v > 0 && $v <= 253402300799, 'TIME_SECONDS'); return $v; }
    public static function digest(mixed $v): string { return Shape::digest($v); }
    public static function hash(mixed $v): string { return 'sha256:'.hash('sha256', StrictJson::canonical($v)); }
    public static function same(mixed $a, mixed $b): bool { return StrictJson::canonical($a) === StrictJson::canonical($b); }
    public static function ref(mixed $v): array {
        $v = self::object($v, ['schema','id','digest']); self::text($v['schema']); self::id($v['id']); self::digest($v['digest']);
        return ['schema'=>$v['schema'],'id'=>$v['id'],'digest'=>$v['digest']];
    }
    public static function reference(array $record): array { return ['schema'=>$record['schema'],'id'=>$record['id'],'digest'=>$record['record_digest']]; }
    public static function key(mixed $ref): string { return CanonicalJson::encode(self::ref($ref)); }
    public static function refs(mixed $v): array {
        $out=[]; foreach (Shape::list($v) as $ref) { $k=self::key($ref); self::require(!isset($out[$k]),'DUPLICATE_REF'); $out[$k]=self::ref($ref); }
        ksort($out,SORT_STRING); return array_values($out);
    }
    public static function head(mixed $v): array {
        $v=self::object($v,['generation','digest']); self::integer($v['generation']);
        self::require($v['generation']===0 ? $v['digest']===null : is_string($v['digest']) && preg_match('/\A[0-9a-f]{64}\z/',$v['digest'])===1,'HEAD'); return $v;
    }
    public static function bytes(mixed $v,int $length): string {
        self::require(is_string($v),'BASE64'); $b=base64_decode($v,true);
        self::require(is_string($b) && strlen($b)===$length && base64_encode($b)===$v,'BASE64'); return $b;
    }
    public static function effect(mixed $effect, string $mode): void {
        self::require(in_array($effect,self::EFFECTS,true),'EFFECT');
        self::require(in_array($mode,['signed_act','policy_effect'],true),'AUTHORITY_MODE');
        self::require($mode!=='policy_effect' || in_array($effect,self::DERIVED,true),'DERIVED_EFFECT_FORBIDDEN');
    }
    public static function supported(string $effect): void { self::require(!in_array($effect,self::UNSUPPORTED,true),'UNSUPPORTED_ISSUER_SOURCE'); }
    public static function record(mixed $v): array {
        $v=self::object($v,['schema','id','instance_id','citadel_id','created_at','producer','sources','body','record_digest']);
        self::text($v['schema']); foreach (['id','instance_id','citadel_id'] as $k) { self::id($v[$k]); } self::time($v['created_at']);
        $p=self::object($v['producer'],['service','source_commit']); self::text($p['service']);
        self::require(is_string($p['source_commit']) && preg_match('/\A[0-9a-f]{40}\z/',$p['source_commit'])===1,'PRODUCER_COMMIT');
        self::require(self::same(self::refs($v['sources']),$v['sources']),'SOURCE_ORDER');
        self::require(is_array($v['body']) && !array_is_list($v['body']),'BODY'); self::digest($v['record_digest']);
        $unsigned=$v; unset($unsigned['record_digest']); self::require(self::hash($unsigned)===$v['record_digest'],'RECORD_DIGEST'); return $v;
    }
    public static function seal(array $v): array { $v['record_digest']=self::hash($v); return self::record($v); }
}
