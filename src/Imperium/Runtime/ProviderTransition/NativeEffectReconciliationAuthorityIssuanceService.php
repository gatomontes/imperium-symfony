<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

/** Issues durable reconciliation authority only from resolved native competence. */
final readonly class NativeEffectReconciliationAuthorityIssuanceService
{
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authorities-v2';
    public const string ISSUANCES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authority-issuances';
    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private NativeEffectReconciliationAuthoritySourceResolver $sources;

    public function __construct(private NativeState $state)
    {
        $this->atomic = new AtomicTransition($state->root);
        $this->records = new ImmutableRecordStore($state->root, $this->atomic);
        $this->sources = new NativeEffectReconciliationAuthoritySourceResolver($state);
    }

    public function issue(string $admissionId, int $at, int $expiresAt): array
    {
        $source = $this->sources->resolve($admissionId, $at);
        $admission = $source['admission'];
        $nativeAuthority = $source['nativeAuthority'];
        $nativePrincipal = $source['nativePrincipal'];
        $response = $source['response'];
        $sourceExpiry = min($nativeAuthority['decision']['expires_at'] ?? 0, $nativePrincipal['expires_at'] ?? 0);
        if ($expiresAt <= $at || $expiresAt > $sourceExpiry || $at < ($response['sealed_at'] ?? PHP_INT_MAX)) {
            throw new \RuntimeException('CNE610_RECONCILIATION_ISSUANCE_TIME_INVALID');
        }

        $authorityId = 'native-effect-reconciliation-authority-'.hash('sha256', $admission['record_digest']."\0".$response['record_digest']);
        $issuanceId = 'native-effect-reconciliation-authority-issuance-'.hash('sha256', $authorityId);
        $receiptId = NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admissionId);
        $authority = [
            'schema' => NativeEffectReconciliationAuthorityV2Contract::SCHEMA,
            'authority_id' => $authorityId,
            'issuance_id' => $issuanceId,
            'source_native_authority' => NativeState::ref($nativeAuthority['authority'], 'authority_id'),
            'source_native_principal' => NativeState::ref($nativePrincipal, 'principal_version_id'),
            'source_native_transition' => NativeState::ref($source['commit'], 'root'),
            'effect_admission' => NativeState::ref($admission, 'admission_id'),
            'callback_start' => NativeState::ref($source['callback'], 'callback_start_id'),
            'sealed_response' => NativeState::ref($response, 'response_id'),
            'deterministic_receipt_id' => $receiptId,
            'act' => NativeEffectReconciliationAuthorityV2Contract::ACT,
            'holder' => NativeEffectReconciliationAuthorityV2Contract::HOLDER,
            'issuer_service' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
            'effective_at' => $at,
            'expires_at' => $expiresAt,
            'revocation_source' => NativeState::ref($nativePrincipal, 'principal_version_id'),
            'single_purpose' => true,
            'single_use' => true,
            'provider_invocation_permitted' => false,
            'credential_resolution_permitted' => false,
            'callback_reinvocation_permitted' => false,
            'automatic_retry_permitted' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];

        return $this->atomic->run('canonical-native-effect-reconciliation-issuance:'.hash('sha256', $authorityId), function () use ($authority, $authorityId, $issuanceId, $nativeAuthority, $nativePrincipal, $source, $admission, $at): array {
            $storedAuthority = $this->records->put(self::AUTHORITIES, $authorityId, $authority);
            $issuance = $this->records->put(self::ISSUANCES, $issuanceId, [
                'schema' => NativeEffectReconciliationAuthorityIssuanceContract::SCHEMA,
                'issuance_id' => $issuanceId,
                'issued_authority' => NativeState::ref($storedAuthority, 'authority_id'),
                'source_native_authority' => NativeState::ref($nativeAuthority['authority'], 'authority_id'),
                'source_native_principal' => NativeState::ref($nativePrincipal, 'principal_version_id'),
                'source_native_transition' => NativeState::ref($source['commit'], 'root'),
                'effect_admission' => NativeState::ref($admission, 'admission_id'),
                'issuer_service' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
                'issued_at' => $at,
                'authority_issued' => true,
                'provider_invocation_performed' => false,
                'credential_resolution_performed' => false,
                'callback_invocation_performed' => false,
                'external_io_performed' => false,
                'continuing_authority' => false,
                'sealed' => true,
            ]);
            return ['authority' => $storedAuthority, 'issuance' => $issuance];
        });
    }
}
