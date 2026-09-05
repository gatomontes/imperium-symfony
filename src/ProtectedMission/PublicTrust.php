<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;

final class PublicTrust
{
    public const COMPETENCE = 'APPROVE_CANONICAL_MISSION_PLAN';

    public static function validate(array $trust, string $fingerprint): array
    {
        $public = base64_decode($trust['public_key'] ?? '', true);
        if (!is_string($public) || strlen($public) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES
            || !hash_equals(hash('sha256', $public), $fingerprint)
            || !preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $trust['identity'] ?? '')
            || ($trust['competence'] ?? null) !== self::COMPETENCE
            || !is_int($trust['not_before'] ?? null) || !is_int($trust['expires_at'] ?? null)
            || $trust['expires_at'] <= $trust['not_before']) {
            throw new \RuntimeException('PMA_TRUST_INVALID');
        }
        return ['identity'=>$trust['identity'], 'competence'=>self::COMPETENCE,
            'public_key'=>$trust['public_key'], 'fingerprint'=>$fingerprint,
            'not_before'=>$trust['not_before'], 'expires_at'=>$trust['expires_at'], 'revoked'=>false];
    }

    public static function verify(array $trust, array $payload, string $signature, int $now): void
    {
        $key = base64_decode($trust['public_key'] ?? '', true);
        $sig = base64_decode($signature, true);
        if (($trust['revoked'] ?? true) !== false || $now < ($trust['not_before'] ?? PHP_INT_MAX)
            || $now >= ($trust['expires_at'] ?? 0)
            || ($payload['operator_identity'] ?? null) !== ($trust['identity'] ?? null)
            || ($payload['competence'] ?? null) !== self::COMPETENCE
            || ($trust['competence'] ?? null) !== self::COMPETENCE
            || ($payload['trust_fingerprint'] ?? null) !== ($trust['fingerprint'] ?? null)
            || !is_string($key) || strlen($key) !== 32 || !is_string($sig) || strlen($sig) !== 64
            || !sodium_crypto_sign_verify_detached($sig, CanonicalJson::encode($payload), $key)) {
            throw new \RuntimeException('PMA_SIGNATURE_OR_TRUST_INVALID');
        }
    }
}
