<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use App\Imperium\Runtime\Clock;

final readonly class NativeTrust
{
    public const DOMAIN = 'IMPERIUM_NATIVE_INSTITUTIONAL_V1';
    public const ROLE = 'NATIVE_INSTITUTIONAL_CUSTODIAN';
    public const EFFECTS = ['ADOPT_ROSTER', 'SUPERSEDE_RECRUITER', 'RETIRE_ROSTER', 'REVISE_GARRISON', 'REVOKE_DECISION', 'REVOKE_ISSUER'];
    public const BOUNDARY = 'NATIVE_REGISTRY_EXCLUSIVE_GARRISON_AND_RECRUITER_V1';
    public function __construct(private NativeJournal $journal, private Clock $clock) {}
    /** Separate deployment-owner operation. No incoming decision can invoke this. */
    public function enroll(array $policy, string $confirmedFingerprint): array
    {
        A::keys($policy, ['schema', 'domain', 'instance_id', 'public_key', 'issuer_role', 'effects', 'not_before', 'expires_at', 'writer_boundary']);
        A::id($policy['instance_id']); A::hex($confirmedFingerprint);
        $key = base64_decode($policy['public_key'], true);
        A::require($policy['schema'] === 'imperium.native-authority-enrollment/v1' && $policy['domain'] === self::DOMAIN
            && $policy['issuer_role'] === self::ROLE && $policy['effects'] === self::EFFECTS && $policy['writer_boundary'] === self::BOUNDARY
            && is_string($key) && strlen($key) === 32 && hash('sha256', $key) === $confirmedFingerprint
            && is_int($policy['not_before']) && is_int($policy['expires_at']) && $policy['not_before'] > 0
            && $policy['not_before'] <= $this->clock->now()->getTimestamp() && $policy['expires_at'] > $this->clock->now()->getTimestamp(), 'NAT010_ENROLLMENT_INVALID');
        return $this->journal->change(function (array &$s) use ($policy, $confirmedFingerprint): array {
            A::require($s === [], 'NAT011_ALREADY_ENROLLED');
            $s = ['trust' => [...$policy, 'fingerprint' => $confirmedFingerprint], 'issuer_revoked' => false,
                'revoked' => [], 'head' => null, 'revision' => 0, 'roster' => [], 'garrison_revision' => null,
                'acts' => [], 'request_replays' => [], 'admissions' => []];
            return ['disposition' => 'PUBLIC_TRUST_ENROLLED_ROSTER_ABSENT', 'fingerprint' => $confirmedFingerprint, ...A::flags()];
        });
    }
    public static function verify(array $s, array $decision, array $object, int $now, bool $historical = false): array
    {
        A::keys($decision, ['payload', 'signature']); $p = $decision['payload'];
        A::keys($p, ['schema', 'domain', 'instance_id', 'trust_fingerprint', 'issuer_role', 'effect', 'object_digest', 'issued_at', 'expires_at', 'nonce']);
        $t = $s['trust'] ?? []; A::require($t !== [], 'NAT012_TRUST_ABSENT');
        $key = base64_decode($t['public_key'], true); $sig = base64_decode($decision['signature'], true);
        A::require($p['schema'] === 'imperium.native-institutional-decision/v1' && $p['domain'] === self::DOMAIN
            && $p['instance_id'] === $t['instance_id'] && $p['trust_fingerprint'] === $t['fingerprint'] && $p['issuer_role'] === self::ROLE
            && in_array($p['effect'], $t['effects'], true) && $p['object_digest'] === A::digest($object)
            && is_int($p['issued_at']) && is_int($p['expires_at']) && $p['issued_at'] >= $t['not_before'] && $p['issued_at'] <= $now
            && $p['expires_at'] > $now && $p['expires_at'] <= $t['expires_at'] && $now >= $t['not_before'] && $now < $t['expires_at']
            && is_string($p['nonce']) && preg_match('/^[a-f0-9]{48}$/D', $p['nonce'])
            && ($historical || (!$s['issuer_revoked'] && !isset($s['revoked'][$p['nonce']])))
            && is_string($key) && strlen($key) === 32 && is_string($sig) && strlen($sig) === 64
            && sodium_crypto_sign_verify_detached($sig, CanonicalJson::encode($p), $key), 'NAT013_EXACT_NATIVE_DECISION_REQUIRED');
        return $p;
    }
}
