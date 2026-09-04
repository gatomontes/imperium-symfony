<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Admits an exact no-provider reconciliation authority and derives its claim. */
final readonly class NativeEffectForwardRecoveryClaimAdmissionService
{
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authorities';
    public const string CLAIMS = 'var/imperium/runtime/canonical-native-effect-forward-recovery-claims';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
    }

    public function admit(array $authority, int $at): array
    {
        $this->validateAuthority($authority, $at);
        $admission = $this->records->read(NativeEffectAtomicAdmissionService::ADMISSIONS, $authority['effect_admission']['id']);
        $callback = $this->records->read(NativeEffectDoubleExecutionService::CALLBACK_STARTS, $authority['callback_start']['id']);
        $response = $this->records->read(NativeEffectDoubleExecutionService::RESPONSES, $authority['sealed_response']['id']);
        $receiptId = self::receiptId($admission['admission_id']);

        if (!$this->sameReference($authority['effect_admission'], $this->ref($admission, 'admission_id'))
            || !$this->sameReference($authority['callback_start'], $this->ref($callback, 'callback_start_id'))
            || !$this->sameReference($authority['sealed_response'], $this->ref($response, 'response_id'))
            || $authority['deterministic_receipt_id'] !== $receiptId
            || ($callback['effect_admission']['digest'] ?? null) !== $admission['record_digest']
            || ($response['effect_admission']['digest'] ?? null) !== $admission['record_digest']
            || ($response['callback_start']['digest'] ?? null) !== $callback['record_digest']) {
            throw new \RuntimeException('CNE510_RECONCILIATION_LINEAGE_INVALID');
        }

        $claimId = 'native-effect-forward-recovery-claim-'.hash('sha256', $authority['authority_id']."\0".$response['record_digest']);
        return $this->atomic->run('canonical-native-effect-reconciliation-authority:'.hash('sha256', $authority['authority_id']), function () use ($authority, $at, $claimId, $receiptId): array {
            $storedAuthority = $this->records->put(self::AUTHORITIES, $authority['authority_id'], $authority);
            return $this->records->put(self::CLAIMS, $claimId, [
                'schema' => NativeEffectForwardRecoveryClaimContract::SCHEMA,
                'claim_id' => $claimId,
                'reconciliation_authority' => $this->ref($storedAuthority, 'authority_id'),
                'effect_admission' => $authority['effect_admission'],
                'callback_start' => $authority['callback_start'],
                'sealed_response' => $authority['sealed_response'],
                'deterministic_receipt_id' => $receiptId,
                'act' => NativeEffectForwardRecoveryClaimContract::ACT,
                'provider_invocation_permitted' => false,
                'credential_resolution_permitted' => false,
                'callback_reinvocation_permitted' => false,
                'automatic_retry_permitted' => false,
                'admitted_at' => $at,
                'expires_at' => $authority['expires_at'],
                'sealed' => true,
            ]);
        });
    }

    public static function receiptId(string $admissionId): string
    {
        return 'canonical-native-effect-receipt-'.substr(hash('sha256', $admissionId), 0, 20);
    }

    private function validateAuthority(array $authority, int $at): void
    {
        if (NativeEffectReconciliationAuthorityContract::REQUIRED_FIELDS !== array_keys($authority)
            || NativeEffectReconciliationAuthorityContract::SCHEMA !== ($authority['schema'] ?? null)
            || NativeEffectReconciliationAuthorityContract::ACT !== ($authority['act'] ?? null)
            || NativeEffectReconciliationAuthorityContract::HOLDER !== ($authority['holder'] ?? null)
            || NativeEffectReconciliationAuthorityContract::ISSUER !== ($authority['issuer'] ?? null)
            || true !== ($authority['single_purpose'] ?? null)
            || true !== ($authority['sealed'] ?? null)
            || NativeState::seal($authority) !== $authority
            || !is_int($authority['effective_at'] ?? null)
            || !is_int($authority['expires_at'] ?? null)
            || $at < $authority['effective_at'] || $at >= $authority['expires_at']) {
            throw new \RuntimeException('CNE509_RECONCILIATION_AUTHORITY_INVALID');
        }
        NativeState::id($authority['authority_id'] ?? null);
        foreach (NativeEffectReconciliationAuthorityContract::REQUIRED_FALSE_FLAGS as $flag) {
            if (false !== ($authority[$flag] ?? null)) {
                throw new \RuntimeException('CNE509_RECONCILIATION_AUTHORITY_INVALID');
            }
        }
        foreach (['effect_admission', 'callback_start', 'sealed_response'] as $reference) {
            NativeState::reference($authority[$reference] ?? null);
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
