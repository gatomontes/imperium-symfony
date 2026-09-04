<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Resolves exact issuance authority and revalidates its Root/native/source chain at use. */
final class NativeEffectReconciliationIssuanceAuthorityResolver
{
    private readonly ImmutableRecordStore $records;
    private readonly NativeEffectProcessIncarnation $incarnation;
    private readonly string $resolverBindingId;

    /** @var array<string, NativeEffectReconciliationIssuanceAuthorityCapability> */
    private array $issued = [];

    public function __construct(private readonly NativeState $state, ?NativeEffectProcessIncarnation $incarnation = null)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
        $this->incarnation = $incarnation ?? new NativeEffectProcessIncarnation();
        $this->resolverBindingId = bin2hex(random_bytes(16));
    }

    public function resolve(string $issuanceAuthorityId, int $at): NativeEffectReconciliationIssuanceAuthorityCapability
    {
        $evidence = $this->inspect($issuanceAuthorityId, $at);
        $authority = $evidence['issuance_authority'];
        $decision = $evidence['decision'];
        $capabilityId = 'native-effect-reconciliation-issuance-capability-'.bin2hex(random_bytes(16));
        $material = $this->material($capabilityId, $authority['record_digest'], $decision['record_digest']);
        $capability = new NativeEffectReconciliationIssuanceAuthorityCapability(
            $capabilityId,
            $authority['issuance_authority_id'],
            $authority['record_digest'],
            $decision['decision_id'],
            $decision['record_digest'],
            $authority['target']['authority_id'],
            $authority['target']['authority_digest'],
            $authority['expires_at'],
            $this->incarnation->runtimeProcessId(),
            $this->incarnation->binding($material),
        );
        $this->issued[$capabilityId] = $capability;

        return $capability;
    }

    /** @return array{decision: array, issuance_authority: array, source: array} */
    public function inspect(string $issuanceAuthorityId, int $at): array
    {
        NativeState::id($issuanceAuthorityId);
        $authority = $this->records->read(NativeEffectReconciliationIssuanceDecisionService::AUTHORITIES, $issuanceAuthorityId);
        if (NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectReconciliationIssuanceAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeState::seal($authority) !== $authority
            || NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION !== ($authority['permitted_transition'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || $at < ($authority['effective_at'] ?? PHP_INT_MAX)
            || $at >= ($authority['expires_at'] ?? PHP_INT_MIN)) {
            throw new \RuntimeException('CNE642_ISSUANCE_AUTHORITY_INVALID');
        }
        $decision = $this->records->read(NativeEffectReconciliationIssuanceDecisionService::DECISIONS, $authority['issuance_decision']['id'] ?? '');
        if (NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS !== array_keys($decision)
            || NativeEffectReconciliationIssuanceDecisionContract::SCHEMA !== ($decision['schema'] ?? null)
            || NativeState::seal($decision) !== $decision
            || NativeState::ref($decision, 'decision_id') !== $authority['issuance_decision']
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || $decision['target'] !== $authority['target']
            || $decision['competent_issuer'] !== $authority['issuer']
            || $decision['holder'] !== $authority['holder']
            || $decision['replay_identity'] !== $authority['replay_identity']) {
            throw new \RuntimeException('CNE643_ISSUANCE_DECISION_INVALID');
        }
        $source = (new NativeEffectReconciliationAuthoritySourceResolver($this->state))->resolve($authority['effect_admission']['id'] ?? '', $at);
        $rootReference = [
            'id' => $source['nativePrincipal']['root_act']['act']['act_id'],
            'schema' => NativeRootActs::SCHEMA,
            'digest' => hash('sha256', CanonicalJson::encode($source['nativePrincipal']['root_act'])),
        ];
        $expectedIssuer = [
            'principal_id' => $source['nativePrincipal']['principal_id'],
            'principal_version_id' => $source['nativePrincipal']['principal_version_id'],
            'generation' => $source['nativePrincipal']['principal_generation'],
            'office' => 'imperator',
            'seat' => 'imperator',
            'competence' => 'DECIDE_EXACT_RECONCILIATION_AUTHORITY_ISSUANCE',
        ];
        $factory = new NativeEffectReconciliationAuthorityRecordFactory();
        $targetAuthority = $factory->build($source, $decision['effective_at'], $decision['expires_at']);
        if ($authority['source_native_authority'] !== NativeState::ref($source['nativeAuthority']['authority'], 'authority_id')
            || $authority['source_native_principal'] !== NativeState::ref($source['nativePrincipal'], 'principal_version_id')
            || $authority['source_native_transition'] !== NativeState::ref($source['commit'], 'root')
            || $authority['callback_start'] !== NativeState::ref($source['callback'], 'callback_start_id')
            || $authority['sealed_response'] !== NativeState::ref($source['response'], 'response_id')
            || $authority['operator_root_act'] !== $rootReference
            || $authority['issuer'] !== $expectedIssuer
            || $authority['target']['authority_digest'] !== $targetAuthority['record_digest']) {
            throw new \RuntimeException('CNE644_ISSUANCE_CURRENTNESS_INVALID');
        }

        return ['decision' => $decision, 'issuance_authority' => $authority, 'source' => $source];
    }

    /** @return array{decision: array, issuance_authority: array, source: array} */
    public function consume(NativeEffectReconciliationIssuanceAuthorityCapability $capability, int $at): array
    {
        if (($this->issued[$capability->capabilityId] ?? null) !== $capability
            || $at >= $capability->expiresAt
            || $capability->runtimeProcessId !== $this->currentProcessId()
            || !$this->incarnation->recognizes(
                $this->material($capability->capabilityId, $capability->issuanceAuthorityDigest, $capability->issuanceDecisionDigest),
                $capability->processIncarnationBinding,
            )) {
            throw new \RuntimeException('CNE645_ISSUANCE_CAPABILITY_INVALID');
        }
        $evidence = $this->inspect($capability->issuanceAuthorityId, $at);
        if ($evidence['issuance_authority']['record_digest'] !== $capability->issuanceAuthorityDigest
            || $evidence['decision']['record_digest'] !== $capability->issuanceDecisionDigest
            || $evidence['issuance_authority']['target']['authority_id'] !== $capability->targetAuthorityId
            || $evidence['issuance_authority']['target']['authority_digest'] !== $capability->targetAuthorityDigest) {
            throw new \RuntimeException('CNE645_ISSUANCE_CAPABILITY_INVALID');
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
