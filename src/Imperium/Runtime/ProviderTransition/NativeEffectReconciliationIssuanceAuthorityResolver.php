<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Resolves durable issuance evidence into exact process-local custody. */
final class NativeEffectReconciliationIssuanceAuthorityResolver
{
    private readonly ImmutableRecordStore $records;
    private readonly NativeEffectProcessIncarnation $incarnation;
    private readonly string $resolverBindingId;

    /** @var array<string, NativeEffectReconciliationIssuanceCapability> */
    private array $issued = [];

    public function __construct(private readonly NativeState $state, ?NativeEffectProcessIncarnation $incarnation = null)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
        $this->incarnation = $incarnation ?? new NativeEffectProcessIncarnation();
        $this->resolverBindingId = bin2hex(random_bytes(16));
    }

    public function resolve(string $issuanceAuthorityId, int $at): NativeEffectReconciliationIssuanceCapability
    {
        $evidence = $this->inspect($issuanceAuthorityId, $at);
        $authority = $evidence['issuance_authority'];
        $decision = $evidence['decision'];
        $capabilityId = 'native-effect-reconciliation-issuance-capability-'.bin2hex(random_bytes(16));
        $material = $this->material($capabilityId, $authority['record_digest'], $decision['record_digest']);
        $capability = new NativeEffectReconciliationIssuanceCapability(
            $capabilityId,
            $authority['issuance_authority_id'],
            $authority['record_digest'],
            $decision['decision_id'],
            $decision['record_digest'],
            $decision['effect_admission']['id'],
            $decision['target']['authority_id'],
            $decision['competent_issuer']['principal_version_id'],
            $decision['effective_at'],
            $decision['expires_at'],
            $this->incarnation->runtimeProcessId(),
            $this->incarnation->binding($material),
        );
        $this->issued[$capabilityId] = $capability;
        return $capability;
    }

    /** Durable evidence is returned read-only and is never itself custody. */
    public function inspect(string $issuanceAuthorityId, int $at, bool $atUse = false): array
    {
        NativeState::id($issuanceAuthorityId);
        $authority = $this->records->read(NativeEffectReconciliationIssuanceAuthorizationService::AUTHORITIES, $issuanceAuthorityId);
        $decisionId = $authority['issuance_decision']['id'] ?? '';
        NativeState::id($decisionId);
        $decision = $this->records->read(NativeEffectReconciliationIssuanceAuthorizationService::DECISIONS, $decisionId);
        $this->validate($authority, $decision, $at);

        $source = (new NativeEffectReconciliationAuthoritySourceResolver($this->state))->resolve(
            $decision['effect_admission']['id'],
            $atUse ? $at : $decision['effective_at'],
            $atUse,
        );
        $plan = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))->preview(
            $decision['effect_admission']['id'], $decision['effective_at'], $decision['expires_at'],
        );
        if ($authority['issuance_decision'] !== NativeState::ref($decision, 'decision_id')
            || $decision['target']['authority_id'] !== $plan['authority']['authority_id']
            || $decision['target']['authority_schema'] !== $plan['authority']['schema']
            || $decision['target']['authority_digest'] !== $plan['authority']['record_digest']
            || $decision['effect_admission'] !== NativeState::ref($source['admission'], 'admission_id')
            || $decision['callback_start'] !== NativeState::ref($source['callback'], 'callback_start_id')
            || $decision['sealed_response'] !== NativeState::ref($source['response'], 'response_id')
            || $decision['source_native_authority'] !== NativeState::ref($source['nativeAuthority']['authority'], 'authority_id')
            || $decision['source_native_principal'] !== NativeState::ref($source['nativePrincipal'], 'principal_version_id')
            || $decision['source_native_transition'] !== NativeState::ref($source['commit'], 'root')) {
            throw new \RuntimeException('CNE643_RECONCILIATION_ISSUANCE_LINEAGE_INVALID');
        }
        $imperator = $this->state->source('principal', $source['nativePrincipal']['source_principal']);
        if ($decision['competent_issuer_provenance'] !== NativeState::ref($imperator, 'principal_version_id')
            || $decision['competent_issuer']['principal_version_id'] !== $imperator['principal_version_id']
            || NativeEffectReconciliationIssuanceAuthorizationService::COMPETENCE !== $decision['competent_issuer']['competence']) {
            throw new \RuntimeException('CNE641_RECONCILIATION_ISSUANCE_COMPETENCE_ABSENT');
        }
        return ['decision' => $decision, 'issuance_authority' => $authority, 'source' => $source, 'target_authority' => $plan['authority']];
    }

    public function consume(NativeEffectReconciliationIssuanceCapability $capability, int $at): array
    {
        if (($this->issued[$capability->capabilityId] ?? null) !== $capability
            || $at < $capability->effectiveAt
            || $at >= $capability->expiresAt
            || $capability->runtimeProcessId !== $this->currentProcessId()
            || !$this->incarnation->recognizes(
                $this->material($capability->capabilityId, $capability->issuanceAuthorityDigest, $capability->decisionDigest),
                $capability->processIncarnationBinding,
            )) {
            throw new \RuntimeException('CNE644_RECONCILIATION_ISSUANCE_CAPABILITY_INVALID');
        }
        unset($this->issued[$capability->capabilityId]);
        $evidence = $this->inspect($capability->issuanceAuthorityId, $at, true);
        if ($evidence['decision']['record_digest'] !== $capability->decisionDigest
            || $evidence['issuance_authority']['record_digest'] !== $capability->issuanceAuthorityDigest
            || $evidence['decision']['effect_admission']['id'] !== $capability->admissionId
            || $evidence['decision']['target']['authority_id'] !== $capability->authorityId
            || $evidence['decision']['competent_issuer']['principal_version_id'] !== $capability->issuerId) {
            throw new \RuntimeException('CNE644_RECONCILIATION_ISSUANCE_CAPABILITY_INVALID');
        }
        return $evidence;
    }

    private function validate(array $authority, array $decision, int $at): void
    {
        if (NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS !== array_keys($decision)
            || NativeEffectReconciliationIssuanceDecisionContract::SCHEMA !== ($decision['schema'] ?? null)
            || NativeState::seal($decision) !== $decision
            || NativeEffectReconciliationIssuanceDecisionContract::ACT !== ($decision['act'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || $at < ($decision['effective_at'] ?? PHP_INT_MAX)
            || $at >= ($decision['expires_at'] ?? PHP_INT_MIN)
            || NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectReconciliationIssuanceAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeState::seal($authority) !== $authority
            || NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION !== ($authority['permitted_transition'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || $at < ($authority['effective_at'] ?? PHP_INT_MAX)
            || $at >= ($authority['expires_at'] ?? PHP_INT_MIN)
            || $authority['target'] !== $decision['target']
            || $authority['replay_identity'] !== $decision['replay_identity']) {
            throw new \RuntimeException('CNE642_RECONCILIATION_ISSUANCE_AUTHORITY_INVALID');
        }
    }

    private function material(string $capabilityId, string $authorityDigest, string $decisionDigest): string
    {
        return implode("\0", [$this->resolverBindingId, $capabilityId, $authorityDigest, $decisionDigest]);
    }

    private function currentProcessId(): ?int
    {
        try { return $this->incarnation->runtimeProcessId(); } catch (\RuntimeException) { return null; }
    }
}
