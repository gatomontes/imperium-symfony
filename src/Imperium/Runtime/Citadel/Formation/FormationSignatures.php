<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clock;

/** Public trust is deployment-owned. Never learn a key from an incoming decision. */
final readonly class FormationSignatures
{
    public function __construct(private FormationJournal $journal, private Clock $clock) {}

    /** Deployment-owner prerequisite, deliberately absent from the command protocol.
     * Mirrors the protected runtime's explicit public-only enrollment boundary.
     * This does not import or enlarge its APPROVE_CANONICAL_MISSION_PLAN trust.
     */
    public function enrollPublicTrust(array $trust, string $confirmedFingerprint): void
    {
        $key = base64_decode($trust['public_key'] ?? '', true);
        if (!is_string($key) || strlen($key) !== 32
            || !hash_equals(hash('sha256', $key), $confirmedFingerprint)
            || !FormationJournal::keys($trust, ['public_key', 'not_before', 'expires_at'])
            || !is_int($trust['not_before']) || !is_int($trust['expires_at'])
            || $trust['expires_at'] <= $trust['not_before']) {
            throw new \RuntimeException('CMF020_PUBLIC_TRUST_INVALID');
        }
        $this->journal->change(function (array &$state) use ($trust, $confirmedFingerprint): void {
            if (isset($state['trust'])) { throw new \RuntimeException('CMF021_TRUST_ALREADY_ENROLLED'); }
            $state['citadel_id'] ??= 'citadel-'.bin2hex(random_bytes(16));
            $state['trust'] = [...$trust, 'fingerprint' => $confirmedFingerprint,
                'competence' => 'CITADEL_MISSION_FORMATION', 'revoked' => false];
        });
    }

    public function verify(array $state, array $envelope, string $effect, mixed $object): array
    {
        $trust = $state['trust'] ?? [];
        $payload = $envelope['payload'] ?? [];
        $now = $this->clock->now()->getTimestamp();
        $signature = base64_decode($envelope['signature'] ?? '', true);
        $key = base64_decode($trust['public_key'] ?? '', true);
        if (!FormationJournal::keys($envelope, ['payload', 'signature'])
            || !FormationJournal::keys($payload, ['schema', 'citadel_id', 'trust_fingerprint', 'effect', 'object_digest', 'issued_at', 'expires_at', 'nonce'])
            || ($trust['competence'] ?? null) !== 'CITADEL_MISSION_FORMATION'
            || ($trust['revoked'] ?? true) !== false
            || $now < ($trust['not_before'] ?? PHP_INT_MAX) || $now >= ($trust['expires_at'] ?? 0)
            || ($payload['schema'] ?? null) !== 'imperium.citadel-owner-decision/v1'
            || ($payload['citadel_id'] ?? null) !== ($state['citadel_id'] ?? null)
            || ($payload['trust_fingerprint'] ?? null) !== ($trust['fingerprint'] ?? null)
            || ($payload['effect'] ?? null) !== $effect
            || ($payload['object_digest'] ?? null) !== FormationJournal::digest($object)
            || !is_int($payload['issued_at'] ?? null) || !is_int($payload['expires_at'] ?? null)
            || $payload['issued_at'] > $now || $payload['expires_at'] <= $now
            || $payload['expires_at'] > $trust['expires_at']
            || !preg_match('/^[a-f0-9]{48}$/D', $payload['nonce'] ?? '')
            || isset($state['revoked_decisions'][$payload['nonce'] ?? ''])
            || !is_string($key) || strlen($key) !== 32 || !is_string($signature) || strlen($signature) !== 64
            || !sodium_crypto_sign_verify_detached($signature, CanonicalJson::encode($payload), $key)) {
            throw new \RuntimeException('CMF022_AUTHENTIC_EXACT_DECISION_REQUIRED');
        }
        return $payload;
    }

    public function revoke(array $envelope, string $nonce): void
    {
        $this->journal->change(function (array &$state) use ($envelope, $nonce): void {
            $this->verify($state, $envelope, 'REVOKE_DECISION', ['nonce' => $nonce]);
            $state['revoked_decisions'][$nonce] = $envelope;
        });
    }
}
