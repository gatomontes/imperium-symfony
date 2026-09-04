<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Sealed-response forward completion. No callback, continuation or credential input exists. */
final readonly class NativeEffectForwardRecoveryService
{
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private AuthorityConsumptionStore $consumptions;
    private NativeEffectReceiptBindingService $binder;

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic);
        $this->binder = new NativeEffectReceiptBindingService($state);
    }

    public function forwardComplete(string $claimId, int $at): array
    {
        $claim = $this->records->read(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, $claimId);
        $admissionId = $claim['effect_admission']['id'] ?? null;
        if (!is_string($admissionId)) {
            throw new \RuntimeException('CNE511_FORWARD_RECOVERY_CLAIM_INVALID');
        }
        $continuationScope = 'canonical-native-effect-continuation:'.hash('sha256', $admissionId);
        $claimScope = 'canonical-native-effect-forward-recovery:'.hash('sha256', $claimId);

        return $this->atomic->run($continuationScope, fn (): array => $this->atomic->run($claimScope, function () use ($claimId, $admissionId, $at): array {
            $claim = $this->records->read(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, $claimId);
            $this->validateClaim($claim, $at);
            $evidence = (new NativeEffectReconciliationAuthorityResolver($this->state))->inspect(
                $claim['reconciliation_authority']['id'],
                $at,
                true,
            );
            if ($claim['reconciliation_authority'] !== NativeState::ref($evidence['authority'], 'authority_id')
                || $claim['authority_issuance'] !== NativeState::ref($evidence['issuance'], 'issuance_id')
                || $claim['effect_admission'] !== $evidence['authority']['effect_admission']
                || $claim['callback_start'] !== $evidence['authority']['callback_start']
                || $claim['sealed_response'] !== $evidence['authority']['sealed_response']) {
                throw new \RuntimeException('CNE512_FORWARD_RECOVERY_LINEAGE_INVALID');
            }
            $admission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $admissionId);
            $callback = $this->records->read(NativeEffectDoubleExecutionService::CALLBACK_STARTS, $claim['callback_start']['id']);
            $response = $this->records->read(NativeEffectDoubleExecutionService::RESPONSES, $claim['sealed_response']['id']);

            if (!$this->sameReference($claim['effect_admission'], $this->ref($admission, 'admission_id'))
                || !$this->sameReference($claim['callback_start'], $this->ref($callback, 'callback_start_id'))
                || !$this->sameReference($claim['sealed_response'], $this->ref($response, 'response_id'))
                || ($response['callback_start']['digest'] ?? null) !== $callback['record_digest']) {
                throw new \RuntimeException('CNE512_FORWARD_RECOVERY_LINEAGE_INVALID');
            }

            $this->consumptions->consume(
                $claimId,
                $claimId,
                $claim['record_digest'],
                $claim['deterministic_receipt_id'],
                new \DateTimeImmutable('@'.$at),
            );

            try {
                return $this->records->read(NativeEffectDoubleExecutionService::RECEIPTS, $claim['deterministic_receipt_id']);
            } catch (\RuntimeException $error) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) {
                    throw $error;
                }
            }

            return $this->binder->bind($admission, $response, $claim['deterministic_receipt_id'], $at);
        }));
    }

    private function validateClaim(array $claim, int $at): void
    {
        if (NativeEffectForwardRecoveryClaimV2Contract::REQUIRED_FIELDS !== array_keys($claim)
            || NativeEffectForwardRecoveryClaimV2Contract::SCHEMA !== ($claim['schema'] ?? null)
            || NativeEffectForwardRecoveryClaimV2Contract::ACT !== ($claim['act'] ?? null)
            || NativeState::seal($claim) !== $claim
            || true !== ($claim['sealed'] ?? null)
            || $at < ($claim['admitted_at'] ?? PHP_INT_MAX)
            || $at >= ($claim['expires_at'] ?? PHP_INT_MIN)) {
            throw new \RuntimeException('CNE511_FORWARD_RECOVERY_CLAIM_INVALID');
        }
        foreach (NativeEffectForwardRecoveryClaimV2Contract::REQUIRED_FALSE_FLAGS as $flag) {
            if (false !== ($claim[$flag] ?? null)) {
                throw new \RuntimeException('CNE511_FORWARD_RECOVERY_CLAIM_INVALID');
            }
        }
        $consumption = $claim['authority_consumption'] ?? null;
        if (!is_array($consumption)
            || NativeEffectReconciliationAuthorityConsumptionContract::REQUIRED_FIELDS !== array_keys($consumption)
            || NativeState::seal($consumption) !== $consumption
            || ($consumption['authority_id'] ?? null) !== ($claim['reconciliation_authority']['id'] ?? null)
            || ($consumption['claim_id'] ?? null) !== ($claim['claim_id'] ?? null)) {
            throw new \RuntimeException('CNE511_FORWARD_RECOVERY_CLAIM_INVALID');
        }
    }

    private function ref(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'schema' => $record['schema'], 'digest' => $record['record_digest']];
    }

    private function sameReference(array $left, array $right): bool
    {
        return ($left['id'] ?? null) === ($right['id'] ?? null)
            && ($left['schema'] ?? null) === ($right['schema'] ?? null)
            && ($left['digest'] ?? null) === ($right['digest'] ?? null);
    }
}
