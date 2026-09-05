<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Verifies an exact approval with an asymmetric Operator key held outside the consumer. */
final readonly class OperatorApprovalAuthenticator
{
    public const string SCHEMA = 'imperium.operator-approval-authenticity/v1';
    public const string COMPETENCE = 'APPROVE_CANONICAL_MISSION_PLAN';
    private string $trustPath;

    public function __construct(string $root)
    {
        $this->trustPath = $root.'/config/imperium/operator-approval-trust.json';
    }

    public function verify(array $review, array $dossier, CanonicalMissionPlan $mission): void
    {
        $authenticity = $review['operator_authenticity'] ?? null;
        $trust = $this->readTrust();
        if (!is_array($authenticity)
            || ['schema', 'key_id', 'competence', 'payload_digest', 'signature'] !== array_keys($authenticity)
            || self::SCHEMA !== $authenticity['schema']
            || self::COMPETENCE !== $authenticity['competence']
            || ($trust['key_id'] ?? null) !== $authenticity['key_id']
            || ($trust['operator_identity'] ?? null) !== ($review['actor'] ?? null)
            || !in_array(self::COMPETENCE, $trust['competences'] ?? [], true)
            || true !== ($trust['active'] ?? null)
            || true === ($trust['revoked'] ?? null)) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
        $payload = self::payload($review, $dossier, $mission, $authenticity['key_id']);
        $canonical = CanonicalJson::encode($payload);
        if (!hash_equals(hash('sha256', $canonical), $authenticity['payload_digest'] ?? '')) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
        try {
            $reviewedAt = new \DateTimeImmutable($review['reviewed_at']);
            $notBefore = new \DateTimeImmutable($trust['not_before']);
            $expiresAt = new \DateTimeImmutable($trust['expires_at']);
            $publicKey = base64_decode($trust['public_key_base64'], true);
            $signature = base64_decode($authenticity['signature'], true);
        } catch (\Throwable) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
        if (false === $publicKey || SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES !== strlen($publicKey)
            || false === $signature || SODIUM_CRYPTO_SIGN_BYTES !== strlen($signature)
            || $reviewedAt < $notBefore || $reviewedAt >= $expiresAt
            || !sodium_crypto_sign_verify_detached($signature, $canonical, $publicKey)) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
    }

    public static function payload(array $review, array $dossier, CanonicalMissionPlan $mission, string $keyId): array
    {
        return [
            'schema' => self::SCHEMA,
            'key_id' => $keyId,
            'operator_identity' => $review['actor'] ?? null,
            'competence' => self::COMPETENCE,
            'dossier' => $review['dossier'] ?? null,
            'dossier_record_digest' => $dossier['record_digest'] ?? null,
            'canonical_mission_digest' => $mission->digest(),
            'disposition' => $review['disposition'] ?? null,
            'all_lines_acknowledged' => $review['all_lines_acknowledged'] ?? null,
            'review_authority_id' => $review['review_authority']['id'] ?? null,
            'derivation_authority_id' => $review['mission_authorization_derivation_authority']['authority_id'] ?? null,
            'reviewed_at' => $review['reviewed_at'] ?? null,
        ];
    }

    private function readTrust(): array
    {
        if (!is_file($this->trustPath)) { throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED'); }
        try {
            $trust = json_decode((string) file_get_contents($this->trustPath), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
        if (!is_array($trust) || 'imperium.operator-approval-trust/v1' !== ($trust['schema'] ?? null)) {
            throw new \RuntimeException('MIS407_OPERATOR_APPROVAL_UNAUTHENTICATED');
        }
        return $trust;
    }
}
