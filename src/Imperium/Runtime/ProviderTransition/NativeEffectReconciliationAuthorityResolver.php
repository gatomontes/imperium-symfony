<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Resolves canonical evidence and delivers exact process-local custody. */
final class NativeEffectReconciliationAuthorityResolver
{
    private readonly ImmutableRecordStore $records;
    private readonly NativeEffectProcessIncarnation $incarnation;
    private readonly string $resolverBindingId;

    /** @var array<string, NativeEffectReconciliationAuthorityCapability> */
    private array $issued = [];

    public function __construct(private readonly NativeState $state, ?NativeEffectProcessIncarnation $incarnation = null)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
        $this->incarnation = $incarnation ?? new NativeEffectProcessIncarnation();
        $this->resolverBindingId = bin2hex(random_bytes(16));
    }

    public function resolve(string $authorityId, int $at): NativeEffectReconciliationAuthorityCapability
    {
        $evidence = $this->inspect($authorityId, $at);
        $authority = $evidence['authority'];
        $issuance = $evidence['issuance'];

        $capabilityId = 'native-effect-reconciliation-capability-'.bin2hex(random_bytes(16));
        $material = $this->material($capabilityId, $authorityId, $authority['record_digest'], $issuance['record_digest']);
        $capability = new NativeEffectReconciliationAuthorityCapability(
            $capabilityId,
            $authorityId,
            $authority['record_digest'],
            $issuance['issuance_id'],
            $issuance['record_digest'],
            $authority['mission_id'],
            $authority['mission_dossier_identity'],
            $authority['expires_at'],
            $this->incarnation->runtimeProcessId(),
            $this->incarnation->binding($material),
        );
        $this->issued[$capabilityId] = $capability;
        return $capability;
    }

    /** Read-only canonical evidence resolution; returned records are not custody. */
    public function inspect(string $authorityId, int $at, bool $allowConsumed = false): array
    {
        NativeState::id($authorityId);
        $authority = $this->records->read(NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES, $authorityId);
        $this->validateAuthority($authority, $at);
        $issuance = $this->records->read(NativeEffectReconciliationAuthorityIssuanceService::ISSUANCES, $authority['issuance_id']);
        $this->validateIssuance($issuance, $authority);
        $source = (new NativeEffectReconciliationAuthoritySourceResolver($this->state))->resolve($authority['effect_admission']['id'], $at);
        if ($authority['source_native_authority'] !== NativeState::ref($source['nativeAuthority']['authority'], 'authority_id')
            || $authority['source_native_principal'] !== NativeState::ref($source['nativePrincipal'], 'principal_version_id')
            || $authority['source_native_transition'] !== NativeState::ref($source['commit'], 'root')
            || $authority['callback_start'] !== NativeState::ref($source['callback'], 'callback_start_id')
            || $authority['sealed_response'] !== NativeState::ref($source['response'], 'response_id')) {
            throw new \RuntimeException('CNE622_RECONCILIATION_AUTHORITY_LINEAGE_INVALID');
        }
        if (!$allowConsumed) {
            $claimId = NativeEffectReconciliationAuthorityClaimDerivationService::claimId($authority);
            try {
                $this->records->read(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, $claimId);
                throw new \RuntimeException('CNE623_RECONCILIATION_AUTHORITY_CONSUMED');
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) {
                    throw $error;
                }
            }
        }
        return ['authority' => $authority, 'issuance' => $issuance, 'source' => $source];
    }

    public function consume(NativeEffectReconciliationAuthorityCapability $capability, int $at): array
    {
        if (($this->issued[$capability->capabilityId] ?? null) !== $capability
            || $at >= $capability->expiresAt
            || $capability->runtimeProcessId !== $this->currentProcessId()
            || !$this->incarnation->recognizes(
                $this->material($capability->capabilityId, $capability->authorityId, $capability->authorityDigest, $capability->issuanceDigest),
                $capability->processIncarnationBinding,
            )) {
            throw new \RuntimeException('CNE624_RECONCILIATION_CAPABILITY_INVALID');
        }
        unset($this->issued[$capability->capabilityId]);
        $authority = $this->records->read(NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES, $capability->authorityId);
        $issuance = $this->records->read(NativeEffectReconciliationAuthorityIssuanceService::ISSUANCES, $capability->issuanceId);
        if ($authority['record_digest'] !== $capability->authorityDigest || $issuance['record_digest'] !== $capability->issuanceDigest
            || $authority['mission_id'] !== $capability->missionId
            || $authority['mission_dossier_identity'] !== $capability->dossierIdentity
            || $issuance['mission_id'] !== $capability->missionId
            || $issuance['mission_dossier_identity'] !== $capability->dossierIdentity) {
            throw new \RuntimeException('CNE624_RECONCILIATION_CAPABILITY_INVALID');
        }
        return ['authority' => $authority, 'issuance' => $issuance];
    }

    private function validateAuthority(array $authority, int $at): void
    {
        if (NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectReconciliationAuthorityV2Contract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeState::seal($authority) !== $authority
            || true !== ($authority['single_purpose'] ?? null)
            || true !== ($authority['single_use'] ?? null)
            || $at < ($authority['effective_at'] ?? PHP_INT_MAX)
            || $at >= ($authority['expires_at'] ?? PHP_INT_MIN)) {
            throw new \RuntimeException('CNE621_RECONCILIATION_AUTHORITY_INVALID');
        }
        foreach (NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FALSE_FLAGS as $flag) {
            if (false !== ($authority[$flag] ?? null)) {
                throw new \RuntimeException('CNE621_RECONCILIATION_AUTHORITY_INVALID');
            }
        }
    }

    private function validateIssuance(array $issuance, array $authority): void
    {
        if (NativeEffectReconciliationAuthorityIssuanceContract::REQUIRED_FIELDS !== array_keys($issuance)
            || NativeEffectReconciliationAuthorityIssuanceContract::SCHEMA !== ($issuance['schema'] ?? null)
            || NativeState::seal($issuance) !== $issuance
            || ($issuance['issued_authority'] ?? null) !== NativeState::ref($authority, 'authority_id')
            || ($issuance['mission_id'] ?? null) !== $authority['mission_id']
            || ($issuance['mission_dossier_identity'] ?? null) !== $authority['mission_dossier_identity']
            || ($issuance['source_native_authority'] ?? null) !== $authority['source_native_authority']
            || ($issuance['source_native_principal'] ?? null) !== $authority['source_native_principal']
            || ($issuance['source_native_transition'] ?? null) !== $authority['source_native_transition']
            || ($issuance['effect_admission'] ?? null) !== $authority['effect_admission']
            || true !== ($issuance['authority_issued'] ?? null)) {
            throw new \RuntimeException('CNE625_RECONCILIATION_ISSUANCE_INVALID');
        }
        foreach (NativeEffectReconciliationAuthorityIssuanceContract::REQUIRED_FALSE_FLAGS as $flag) {
            if (false !== ($issuance[$flag] ?? null)) {
                throw new \RuntimeException('CNE625_RECONCILIATION_ISSUANCE_INVALID');
            }
        }
    }

    private function material(string $capabilityId, string $authorityId, string $authorityDigest, string $issuanceDigest): string
    {
        return implode("\0", [$this->resolverBindingId, $capabilityId, $authorityId, $authorityDigest, $issuanceDigest]);
    }

    private function currentProcessId(): ?int
    {
        try { return $this->incarnation->runtimeProcessId(); } catch (\RuntimeException) { return null; }
    }
}
