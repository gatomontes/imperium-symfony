<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Read-only receipt-to-Root reconstruction; it cannot issue, consume or repair. */
final readonly class NativeEffectReconciliationAuthorityReconstructionService
{
    private ImmutableRecordStore $records;

    public function __construct(private NativeState $state)
    {
        $this->records = new ImmutableRecordStore($state->root, new AtomicTransition($state->root));
    }

    public function reconstruct(string $receiptId): array
    {
        NativeState::id($receiptId);
        $receipt = $this->records->read(NativeEffectDoubleExecutionService::RECEIPTS, $receiptId);
        $claim = $this->claimForReceipt($receiptId);
        $at = $claim['admitted_at'] ?? null;
        if (!is_int($at)) {
            throw new \RuntimeException('CNE630_RECONCILIATION_RECONSTRUCTION_INVALID');
        }
        $evidence = (new NativeEffectReconciliationAuthorityResolver($this->state))->inspect(
            $claim['reconciliation_authority']['id'] ?? '',
            $at,
            true,
        );
        $consumptionId = 'authority-consumption-'.hash('sha256', $claim['claim_id']);
        $claimConsumption = $this->records->read('var/imperium/runtime/authority-consumptions', $consumptionId);
        if (($claim['deterministic_receipt_id'] ?? null) !== $receiptId
            || ($claimConsumption['authority_id'] ?? null) !== $claim['claim_id']
            || ($claimConsumption['source'] ?? null) !== ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']]
            || ($claimConsumption['consumer'] ?? null) !== $receiptId
            || ($claim['reconciliation_authority'] ?? null) !== NativeState::ref($evidence['authority'], 'authority_id')
            || ($claim['authority_issuance'] ?? null) !== NativeState::ref($evidence['issuance'], 'issuance_id')) {
            throw new \RuntimeException('CNE630_RECONCILIATION_RECONSTRUCTION_INVALID');
        }

        return [
            'receipt' => $receipt,
            'claim_consumption' => $claimConsumption,
            'forward_recovery_claim' => $claim,
            'authority_consumption' => $claim['authority_consumption'],
            'reconciliation_authority' => $evidence['authority'],
            'authority_issuance' => $evidence['issuance'],
            'native_authority' => $evidence['source']['nativeAuthority'],
            'native_principal' => $evidence['source']['nativePrincipal'],
            'operator_root_act' => $evidence['source']['nativePrincipal']['root_act'],
            'read_only' => true,
            'provider_reinvoked' => false,
            'credential_resolved' => false,
            'retry_authorized' => false,
            'continuing_authority' => false,
        ];
    }

    private function claimForReceipt(string $receiptId): array
    {
        $matches = [];
        foreach (glob($this->state->root.'/'.NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS.'/*.json') ?: [] as $path) {
            $claim = $this->records->read(NativeEffectForwardRecoveryClaimAdmissionService::CLAIMS, basename($path, '.json'));
            if (($claim['deterministic_receipt_id'] ?? null) === $receiptId) { $matches[] = $claim; }
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException('CNE630_RECONCILIATION_RECONSTRUCTION_INVALID');
        }
        return $matches[0];
    }
}
