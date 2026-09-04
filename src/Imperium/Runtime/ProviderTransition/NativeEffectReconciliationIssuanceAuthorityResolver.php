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
            $capabilityId, $authority['issuance_authority_id'], $authority['record_digest'],
            $decision['decision_id'], $decision['record_digest'], $authority['target']['authority_id'],
            $authority['expires_at'], $this->incarnation->runtimeProcessId(), $this->incarnation->binding($material),
        );
        $this->issued[$capabilityId] = $capability;
        return $capability;
    }

    public function inspect(string $issuanceAuthorityId, int $at): array
    {
        NativeState::id($issuanceAuthorityId);
        $authority = $this->records->read(NativeEffectReconciliationIssuanceAuthorizationService::AUTHORITIES, $issuanceAuthorityId);
        $decision = $this->records->read(NativeEffectReconciliationIssuanceAuthorizationService::DECISIONS, $authority['issuance_decision']['id'] ?? 'invalid');
        if (NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS !== array_keys($decision)
            || NativeState::seal($authority) !== $authority || NativeState::seal($decision) !== $decision
            || $authority['issuance_decision'] !== NativeState::ref($decision, 'decision_id')
            || $authority['target'] !== $decision['target'] || 'AUTHORIZED' !== $decision['disposition']
            || true !== $authority['authority_exercisable'] || $at < $authority['effective_at'] || $at >= $authority['expires_at']) {
            throw new \RuntimeException('CNE631_ISSUANCE_AUTHORITY_INVALID');
        }
        $source = (new NativeEffectReconciliationAuthoritySourceResolver($this->state))->resolve($authority['effect_admission']['id'], $at);
        $expected = NativeEffectReconciliationAuthorityFactory::build($source, $decision['effective_at'], $decision['expires_at']);
        foreach (['effect_admission', 'callback_start', 'sealed_response', 'source_native_authority', 'source_native_principal', 'source_native_transition'] as $field) {
            if ($authority[$field] !== $expected[$field] || $decision[$field] !== $expected[$field]) {
                throw new \RuntimeException('CNE632_ISSUANCE_CURRENTNESS_INVALID');
            }
        }
        if ($authority['target']['authority_id'] !== $expected['authority_id'] || $authority['target']['authority_digest'] !== $expected['record_digest']) {
            throw new \RuntimeException('CNE632_ISSUANCE_CURRENTNESS_INVALID');
        }
        return ['issuance_authority' => $authority, 'decision' => $decision, 'source' => $source, 'target_authority' => $expected];
    }

    public function consume(NativeEffectReconciliationIssuanceCapability $capability, int $at): array
    {
        if (($this->issued[$capability->capabilityId] ?? null) !== $capability || $at >= $capability->expiresAt
            || $capability->runtimeProcessId !== $this->currentProcessId()
            || !$this->incarnation->recognizes($this->material($capability->capabilityId, $capability->issuanceAuthorityDigest, $capability->decisionDigest), $capability->processIncarnationBinding)) {
            throw new \RuntimeException('CNE633_ISSUANCE_CAPABILITY_INVALID');
        }
        $evidence = $this->inspect($capability->issuanceAuthorityId, $at);
        if ($evidence['issuance_authority']['record_digest'] !== $capability->issuanceAuthorityDigest
            || $evidence['decision']['record_digest'] !== $capability->decisionDigest
            || $evidence['target_authority']['authority_id'] !== $capability->targetAuthorityId) {
            throw new \RuntimeException('CNE633_ISSUANCE_CAPABILITY_INVALID');
        }
        unset($this->issued[$capability->capabilityId]);
        return $evidence;
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
