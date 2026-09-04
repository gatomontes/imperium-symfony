<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Pure deterministic construction shared by decision targeting and authorized publication. */
final class NativeEffectReconciliationAuthorityRecordFactory
{
    public function build(array $source, int $effectiveAt, int $expiresAt): array
    {
        $admission = $source['admission'];
        $response = $source['response'];
        $authorityId = 'native-effect-reconciliation-authority-'.hash('sha256', $admission['record_digest']."\0".$response['record_digest']);
        $issuanceId = 'native-effect-reconciliation-authority-issuance-'.hash('sha256', $authorityId);

        return NativeState::seal([
            'schema' => NativeEffectReconciliationAuthorityV2Contract::SCHEMA,
            'authority_id' => $authorityId,
            'issuance_id' => $issuanceId,
            'source_native_authority' => NativeState::ref($source['nativeAuthority']['authority'], 'authority_id'),
            'source_native_principal' => NativeState::ref($source['nativePrincipal'], 'principal_version_id'),
            'source_native_transition' => NativeState::ref($source['commit'], 'root'),
            'effect_admission' => NativeState::ref($admission, 'admission_id'),
            'callback_start' => NativeState::ref($source['callback'], 'callback_start_id'),
            'sealed_response' => NativeState::ref($response, 'response_id'),
            'deterministic_receipt_id' => NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admission['admission_id']),
            'act' => NativeEffectReconciliationAuthorityV2Contract::ACT,
            'holder' => NativeEffectReconciliationAuthorityV2Contract::HOLDER,
            'issuer_service' => NativeEffectReconciliationAuthorityV2Contract::ISSUER_SERVICE,
            'effective_at' => $effectiveAt,
            'expires_at' => $expiresAt,
            'revocation_source' => NativeState::ref($source['nativePrincipal'], 'principal_version_id'),
            'single_purpose' => true,
            'single_use' => true,
            'provider_invocation_permitted' => false,
            'credential_resolution_permitted' => false,
            'callback_reinvocation_permitted' => false,
            'automatic_retry_permitted' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ]);
    }
}
