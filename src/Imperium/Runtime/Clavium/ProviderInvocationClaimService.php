<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderInvocationClaimService
{
    private string $activations;
    private string $claims;
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        ?AtomicTransition $atomic = null,
        ?ImmutableRecordStore $records = null,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->activations = $root.'/var/imperium/offices/clavium/delegate-mission-provider-invocation-activations';
        $this->claims = $root.'/var/imperium/runtime/provider-invocations';
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function claim(
        string $activationId,
        string $turnAuthorityId,
        \DateTimeImmutable $claimedAt,
    ): array {
        return $this->atomic->run(
            'provider-invocation-claim:'.hash('sha256', $activationId),
            fn (): array => $this->claimWhileLocked($activationId, $turnAuthorityId, $claimedAt),
        );
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
        $legacyAuthoritativeInputs = [
            'activation_id' => $activationId,
            'activation_digest' => $activation['record_digest'],
            'turn_authority_id' => $turnAuthorityId,
            'lease_id' => $activation['credential_lease']['lease_id'],
        ];
        $authoritativeInputs = [
            'activation' => $activation,
            'turn_authority_id' => $turnAuthorityId,
            'credential_lease_id' => $activation['credential_lease']['lease_id'],
        ];
        $fingerprint = ReplayFingerprint::of($authoritativeInputs);
        $authoritySet = $this->authoritySet($activation, $activationId, $turnAuthorityId);
        $consumer = [
            'actor' => ['kind' => 'runtime-service', 'id' => self::class],
            'competent_service' => self::class,
            'bounded_act' => 'CLAIM_ONE_DELEGATE_PROVIDER_INVOCATION_PRE_IO',
        ];
        $lockScope = 'provider-invocation-claim:'.hash('sha256', $activationId);
        $lockPlan = [
            ['order' => 1, 'scope' => $lockScope, 'authority_id' => $turnAuthorityId],
            ['order' => 2, 'scope' => $lockScope, 'authority_id' => $activation['credential_lease']['lease_id']],
        ];
        $immutableResult = ['schema' => 'imperium.clavium-provider-invocation-claim/v1', 'id' => $claimId, 'embedded' => true];

        foreach (glob($this->claims.'/*.json') ?: [] as $existingPath) {
            $existing = $this->read($existingPath, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            if (!$this->hasValidDigest($existing)) {
                throw new \RuntimeException('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
            }
            if (($existing['source_activation']['id'] ?? null) !== $activationId) {
                continue;
            }
            $this->assertReplayIsExact($existing, $claimId, $activation, $authoritySet, $authoritativeInputs, $legacyAuthoritativeInputs, $fingerprint, $consumer, $lockPlan, $immutableResult);

            return $existing;
        }

        if (is_file($path)) {
            $existing = $this->records->read('var/imperium/runtime/provider-invocations', $claimId);
            $this->assertReplayIsExact($existing, $claimId, $activation, $authoritySet, $authoritativeInputs, $legacyAuthoritativeInputs, $fingerprint, $consumer, $lockPlan, $immutableResult);

            return $existing;
        }

        $idempotencyKey = 'imperium-'.$claimId;
        $transactionalConsumption = TransactionalAuthorityConsumptionEnvelope::complete(
            $claimId,
            (string) $activation['instance_id'],
            $authoritySet,
            $authoritativeInputs,
            $fingerprint,
            $consumer,
            $lockPlan,
            $immutableResult,
            $claimedAt,
        );
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
            'transactional_consumption' => $transactionalConsumption,
            'claimed_at' => $claimedAt->format(DATE_ATOM),
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
            'provider_invoked' => false,
            'credential_material_present' => false,
            'sealed' => true,
        ];

        return $this->records->put('var/imperium/runtime/provider-invocations', $claimId, $record);
    }

    private function assertReplayIsExact(
        array $existing,
        string $claimId,
        array $activation,
        array $authoritySet,
        array $authoritativeInputs,
        array $legacyAuthoritativeInputs,
        string $fingerprint,
        array $consumer,
        array $lockPlan,
        array $immutableResult,
    ): void {
        if (!array_key_exists('transactional_consumption', $existing)) {
            ReplayFingerprint::requireMatch($existing['claim_fingerprint'] ?? null, $legacyAuthoritativeInputs, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');

            return;
        }
        if (!is_array($existing['transactional_consumption'])) {
            throw new \RuntimeException('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        }
        ReplayFingerprint::requireMatch($existing['claim_fingerprint'] ?? null, $authoritativeInputs, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        try {
            $recordedAt = new \DateTimeImmutable((string) ($existing['claimed_at'] ?? ''));
        } catch (\Exception) {
            throw new \RuntimeException('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        }
        $expected = TransactionalAuthorityConsumptionEnvelope::complete(
            $claimId,
            (string) $activation['instance_id'],
            $authoritySet,
            $authoritativeInputs,
            $fingerprint,
            $consumer,
            $lockPlan,
            $immutableResult,
            $recordedAt,
        );
        TransactionalAuthorityConsumptionEnvelope::requireExact($existing['transactional_consumption'], $expected, 'CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
    }

    private function authoritySet(array $activation, string $activationId, string $turnAuthorityId): array
    {
        $lease = $activation['credential_lease'];
        $source = ['id' => $activationId, 'digest' => $activation['record_digest']];
        $issuer = ['kind' => 'source-record', 'id' => $activationId];

        return [
            [
                'authority_id' => $turnAuthorityId,
                'authority_schema' => (string) $activation['schema'],
                'source' => $source,
                'issuer' => $issuer,
                'holder' => $activation['target'],
                'scope' => ['target' => $activation['target'], 'model' => $activation['model'], 'maximum_turns' => 1],
                'expires_at' => $lease['expires_at'],
                'single_use' => true,
                'expected_unconsumed' => true,
            ],
            [
                'authority_id' => $lease['lease_id'],
                'authority_schema' => (string) $activation['schema'],
                'source' => $source,
                'issuer' => $issuer,
                'holder' => $activation['target'],
                'scope' => [
                    'target' => $activation['target'],
                    'provider' => $lease['provider'],
                    'model' => $activation['model'],
                    'lease_scope' => $lease['scope'],
                    'credential_reference_digest' => $lease['credential_reference_digest'],
                ],
                'expires_at' => $lease['expires_at'],
                'single_use' => true,
                'expected_unconsumed' => true,
            ],
        ];
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

    private function read(string $path, string $error): array
    {
        return $this->validator->read($path, $error);
    }

    private function hasValidDigest(array $record): bool
    {
        return $this->validator->isIntact($record);
    }
}
