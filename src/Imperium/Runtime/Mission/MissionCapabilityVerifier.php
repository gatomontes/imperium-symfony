<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

use App\Bootstrap\CanonicalJson;

/** Non-substitutable verification resolved from Runtime-owned custody. */
final readonly class MissionCapabilityVerifier
{
    private MissionCapabilityKeyStore $keys;

    public function __construct(string $root)
    {
        $this->keys = new MissionCapabilityKeyStore($root);
    }

    public function verify(MissionCapability $capability, AuthenticatedMissionAuthorization $authorization, array $transition, \DateTimeImmutable $at): void
    {
        $record = $capability->toArray();
        $signature = $record['signature'];
        $record['signature'] = '';
        if (!hash_equals(hash_hmac('sha256', CanonicalJson::encode($record), $this->keys->existing()), $signature)) {
            throw new \RuntimeException('MIS412_CAPABILITY_FORGED');
        }
        $expected = [
            'authorization_id' => $authorization->authorizationId,
            'authorization_digest' => $authorization->authorizationDigest,
            'dossier_id' => $authorization->dossierId,
            'dossier_digest' => $authorization->dossierDigest,
            'mission_id' => $authorization->mission->id(),
            'mission_digest' => $authorization->mission->digest(),
            'action' => $transition['action'],
            'actor' => $transition['actor'],
            'target' => $transition['target'],
            'issuer' => MissionCapabilityIssuanceService::ISSUER,
            'required_state' => $transition['from'],
            'resulting_state' => $transition['to'],
        ];
        foreach ($expected as $field => $value) {
            if ($value !== ($record[$field] ?? null)) { throw new \RuntimeException('MIS413_CAPABILITY_BINDING_MISMATCH'); }
        }
        if ($at->getTimestamp() < $record['not_before'] || $at->getTimestamp() >= $record['expires_at']) {
            throw new \RuntimeException('MIS414_CAPABILITY_EXPIRED');
        }
    }
}
