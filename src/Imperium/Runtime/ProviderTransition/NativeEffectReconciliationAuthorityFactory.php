<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Pure deterministic construction shared by authorization and issuance cuts. */
final class NativeEffectReconciliationAuthorityFactory
{
    public static function build(array $source, string $missionId, string $dossierIdentity, int $at, int $expiresAt): array
    {
        $admission = $source['admission'];
        $nativeAuthority = $source['nativeAuthority'];
        $nativePrincipal = $source['nativePrincipal'];
        $response = $source['response'];
        $sourceExpiry = min($nativeAuthority['decision']['expires_at'] ?? 0, $nativePrincipal['expires_at'] ?? 0);
        if ($expiresAt <= $at || $expiresAt > $sourceExpiry || $at < ($response['sealed_at'] ?? PHP_INT_MAX)) {
            throw new \RuntimeException('CNE610_RECONCILIATION_ISSUANCE_TIME_INVALID');
        }
        $authorityId = 'native-effect-reconciliation-authority-'.hash('sha256', $missionId."\0".$dossierIdentity."\0".$admission['record_digest']."\0".$response['record_digest']);
        $issuanceId = 'native-effect-reconciliation-authority-issuance-'.hash('sha256', $authorityId);
        return NativeState::seal([
            'schema' => NativeEffectReconciliationAuthorityV2Contract::SCHEMA,
            'authority_id' => $authorityId,
            'issuance_id' => $issuanceId,
            'mission_id' => $missionId,
            'mission_dossier_identity' => $dossierIdentity,
            'source_native_authority' => NativeState::ref($nativeAuthority['authority'], 'authority_id'),
            'source_native_principal' => NativeState::ref($nativePrincipal, 'principal_version_id'),
            'source_native_transition' => NativeState::ref($source['commit'], 'root'),
            'effect_admission' => NativeState::ref($admission, 'admission_id'),
            'callback_start' => NativeState::ref($source['callback'], 'callback_start_id'),
            'sealed_response' => NativeState::ref($response, 'response_id'),
            'deterministic_receipt_id' => NativeEffectForwardRecoveryClaimAdmissionService::receiptId($admission['admission_id']),
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
        ]);
    }

    private function __construct() {}
}
