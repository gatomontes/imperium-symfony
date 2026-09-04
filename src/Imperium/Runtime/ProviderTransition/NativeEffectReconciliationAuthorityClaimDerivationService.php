<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Atomically embodies one authority consumption in its deterministic claim. */
final readonly class NativeEffectReconciliationAuthorityClaimDerivationService
{
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;

    public function __construct(
        private NativeState $state,
        private NativeEffectReconciliationAuthorityResolver $resolver,
        private ?\Closure $checkpoint = null,
    )
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
    }

    public function derive(NativeEffectReconciliationAuthorityCapability $capability, int $at): array
    {
        return $this->atomic->run('canonical-native-effect-reconciliation-authority:'.hash('sha256', $capability->authorityId), function () use ($capability, $at): array {
            $preview = $this->records->read(NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES, $capability->authorityId);
            if (($preview['record_digest'] ?? null) !== $capability->authorityDigest) {
                throw new \RuntimeException('CNE624_RECONCILIATION_CAPABILITY_INVALID');
            }
            $previewClaimId = self::claimId($preview);
            try {
                $this->records->read(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, $previewClaimId);
                throw new \RuntimeException('CNE623_RECONCILIATION_AUTHORITY_CONSUMED');
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) { throw $error; }
            }
            $resolved = $this->resolver->consume($capability, $at);
            $authority = $resolved['authority'];
            $issuance = $resolved['issuance'];
            $claimId = self::claimId($authority);
            $consumption = NativeState::seal([
                'schema' => NativeEffectReconciliationAuthorityConsumptionContract::SCHEMA,
                'consumption_id' => 'reconciliation-authority-consumption-'.hash('sha256', $authority['authority_id']),
                'authority_id' => $authority['authority_id'],
                'claim_id' => $claimId,
                'custody_capability_id' => $capability->capabilityId,
                'act' => NativeEffectReconciliationAuthorityConsumptionContract::ACT,
                'consumed_at' => $at,
                'sealed' => true,
            ]);
            if (null !== $this->checkpoint) { ($this->checkpoint)('capability.consumed'); }
            return $this->records->put(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, $claimId, [
                'schema' => NativeEffectForwardRecoveryClaimV2Contract::SCHEMA,
                'claim_id' => $claimId,
                'reconciliation_authority' => NativeState::ref($authority, 'authority_id'),
                'authority_issuance' => NativeState::ref($issuance, 'issuance_id'),
                'authority_consumption' => $consumption,
                'effect_admission' => $authority['effect_admission'],
                'callback_start' => $authority['callback_start'],
                'sealed_response' => $authority['sealed_response'],
                'deterministic_receipt_id' => $authority['deterministic_receipt_id'],
                'act' => NativeEffectForwardRecoveryClaimV2Contract::ACT,
                'provider_invocation_permitted' => false,
                'credential_resolution_permitted' => false,
                'callback_reinvocation_permitted' => false,
                'automatic_retry_permitted' => false,
                'continuing_authority' => false,
                'admitted_at' => $at,
                'expires_at' => $authority['expires_at'],
                'sealed' => true,
            ]);
        });
    }

    public static function claimId(array $authority): string
    {
        return 'native-effect-forward-recovery-claim-'.hash('sha256', $authority['authority_id']."\0".$authority['sealed_response']['digest']);
    }
}
