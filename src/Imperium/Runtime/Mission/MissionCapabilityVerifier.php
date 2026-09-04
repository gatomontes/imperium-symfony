<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Verifies Operator-originated custody and atomically retires its nonce in this process. */
final class MissionCapabilityVerifier implements MissionCapabilityConsumer
{
    private array $consumed = [];
    private array $revoked = [];

    public function __construct(private readonly string $verificationKey) {}

    public function consume(
        MissionCapability $capability,
        string $missionId,
        string $dossierIdentity,
        string $action,
        string $actor,
        string $target,
        int $at,
    ): array {
        if (!hash_equals(self::signature($this->verificationKey, $capability), $capability->signature)) {
            throw new \RuntimeException('MIS202_CAPABILITY_FORGED');
        }
        if ($capability->missionId !== $missionId || $capability->dossierIdentity !== $dossierIdentity) {
            throw new \RuntimeException('MIS203_CAPABILITY_MISSION_MISMATCH');
        }
        if ($capability->action !== $action) { throw new \RuntimeException('MIS204_CAPABILITY_ACTION_MISMATCH'); }
        if ($capability->actor !== $actor) { throw new \RuntimeException('MIS205_CAPABILITY_ACTOR_MISMATCH'); }
        if ($capability->target !== $target) { throw new \RuntimeException('MIS206_CAPABILITY_TARGET_MISMATCH'); }
        if ($at < $capability->notBefore || $at >= $capability->expiresAt) { throw new \RuntimeException('MIS207_CAPABILITY_EXPIRED'); }
        if (isset($this->revoked[$capability->nonce])) { throw new \RuntimeException('MIS208_CAPABILITY_REVOKED'); }
        if (isset($this->consumed[$capability->nonce])) { throw new \RuntimeException('MIS209_CAPABILITY_CONSUMED'); }
        $this->consumed[$capability->nonce] = true;

        return [
            'mission_id' => $missionId, 'dossier_identity' => $dossierIdentity,
            'capability_nonce' => $capability->nonce, 'action' => $action,
            'actor' => $actor, 'target' => $target, 'consumed_at' => $at,
            'authorization_provenance' => $capability->authorizationProvenance,
        ];
    }

    public function revoke(MissionCapability $capability): void
    {
        if (!hash_equals(self::signature($this->verificationKey, $capability), $capability->signature)) {
            throw new \RuntimeException('MIS202_CAPABILITY_FORGED');
        }
        $this->revoked[$capability->nonce] = true;
    }

    public static function signature(string $key, MissionCapability $capability): string
    {
        return hash_hmac('sha256', implode("\0", [
            $capability->missionId, $capability->dossierIdentity, $capability->action,
            $capability->actor, $capability->target, (string) $capability->notBefore,
            (string) $capability->expiresAt, $capability->nonce, $capability->authorizationProvenance,
        ]), $key);
    }
}

