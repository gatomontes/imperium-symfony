<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderInvocationClaimService
{
    private string $activations;
    private string $claims;
    private string $lockPath;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
    ) {
        $this->activations = $root.'/var/imperium/offices/clavium/delegate-mission-provider-invocation-activations';
        $this->claims = $root.'/var/imperium/runtime/provider-invocations';
        $this->lockPath = $root.'/var/imperium/runtime/provider-invocations.lock';
    }

    public function claim(
        string $activationId,
        string $turnAuthorityId,
        \DateTimeImmutable $claimedAt,
    ): array {
        $this->prepareStorage();

        $lock = fopen($this->lockPath, 'c+');
        if (false === $lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }

            throw new \RuntimeException('CLV401_PROVIDER_INVOCATION_CLAIM_LOCK_FAILED');
        }

        try {
            return $this->claimWhileLocked($activationId, $turnAuthorityId, $claimedAt);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function claimWhileLocked(
        string $activationId,
        string $turnAuthorityId,
        \DateTimeImmutable $claimedAt,
    ): array {
        $activation = $this->read(
            $this->activations.'/'.$activationId.'.json',
            'CLV402_PROVIDER_INVOCATION_ACTIVATION_ABSENT',
        );
        $this->assertActivationIsClaimable($activation, $activationId, $turnAuthorityId, $claimedAt);

        $claimId = 'provider-invocation-'.substr(hash('sha256', CanonicalJson::encode([
            $activationId,
            $activation['record_digest'],
            $turnAuthorityId,
        ])), 0, 20);
        $path = $this->claims.'/'.$claimId.'.json';
        $fingerprint = hash('sha256', CanonicalJson::encode([
            'activation_id' => $activationId,
            'activation_digest' => $activation['record_digest'],
            'turn_authority_id' => $turnAuthorityId,
            'lease_id' => $activation['credential_lease']['lease_id'],
        ]));

        foreach (glob($this->claims.'/*.json') ?: [] as $existingPath) {
            $existing = $this->read($existingPath, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            if (($existing['source_activation']['id'] ?? null) !== $activationId) {
                continue;
            }
            if (($existing['claim_fingerprint'] ?? null) !== $fingerprint) {
                throw new \RuntimeException('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            }

            return $existing;
        }

        if (is_file($path)) {
            $existing = $this->read($path, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            if (($existing['claim_fingerprint'] ?? null) !== $fingerprint) {
                throw new \RuntimeException('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            }

            return $existing;
        }

        $idempotencyKey = 'imperium-'.$claimId;
        $record = [
            'schema' => 'imperium.clavium-provider-invocation-claim/v1',
            'claim_id' => $claimId,
            'claim_fingerprint' => $fingerprint,
            'instance_id' => $activation['instance_id'],
            'source_activation' => [
                'id' => $activationId,
                'digest' => $activation['record_digest'],
            ],
            'target' => $activation['target'],
            'model' => $activation['model'],
            'lease_consumption' => [
                'lease_id' => $activation['credential_lease']['lease_id'],
                'consumed' => true,
                'consumed_at' => $claimedAt->format(DATE_ATOM),
                'expires_at' => $activation['credential_lease']['expires_at'],
                'continuing_authority' => false,
            ],
            'turn_authority_consumption' => [
                'authority_id' => $turnAuthorityId,
                'consumed' => true,
                'consumed_at' => $claimedAt->format(DATE_ATOM),
                'continuing_authority' => false,
            ],
            'provider_request' => [
                'idempotency_key' => $idempotencyKey,
                'external_io_started' => false,
                'provider_response_identity' => null,
            ],
            'recovery' => [
                'automatic_replay_permitted' => false,
                'unknown_outcome_requires_governed_resolution' => true,
            ],
            'claimed_at' => $claimedAt->format(DATE_ATOM),
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
            'provider_invoked' => false,
            'credential_material_present' => false,
            'sealed' => true,
        ];

        return $this->writeAtomically($path, $record);
    }

    private function assertActivationIsClaimable(
        array $activation,
        string $activationId,
        string $turnAuthorityId,
        \DateTimeImmutable $claimedAt,
    ): void {
        $authority = $activation['bounded_cognition_turn_authority'] ?? [];
        $lease = $activation['credential_lease'] ?? [];

        if (!$this->hasValidDigest($activation)
            || $activationId !== ($activation['activation_id'] ?? null)
            || 'DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN' !== ($activation['status'] ?? null)
            || true !== ($activation['provider_invocation_authority'] ?? null)
            || true !== ($activation['credential_use_authority'] ?? null)
            || $turnAuthorityId !== ($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || true !== ($lease['authority_single_use'] ?? null)
            || false !== ($lease['consumed'] ?? null)
            || !is_string($lease['lease_id'] ?? null)
            || !is_string($lease['expires_at'] ?? null)
            || new \DateTimeImmutable($lease['expires_at']) <= $claimedAt
            || true === ($lease['credential_reference_disclosed'] ?? null)
            || true === ($lease['credential_possession_transferred'] ?? null)
        ) {
            throw new \RuntimeException('CLV404_PROVIDER_INVOCATION_CLAIM_CHAIN_INVALID');
        }
    }

    private function prepareStorage(): void
    {
        if (!is_dir($this->claims) && !mkdir($this->claims, 0770, true) && !is_dir($this->claims)) {
            throw new \RuntimeException('CLV405_PROVIDER_INVOCATION_CLAIM_STORAGE_FAILED');
        }
    }

    private function writeAtomically(string $path, array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";

        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);

            throw new \RuntimeException('CLV405_PROVIDER_INVOCATION_CLAIM_STORAGE_FAILED');
        }

        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function hasValidDigest(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
}
