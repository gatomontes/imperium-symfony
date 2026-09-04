<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Issues durable reconciliation authority only from resolved native competence. */
final readonly class NativeEffectReconciliationAuthorityIssuanceService
{
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authorities-v2';
    public const string ISSUANCES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authority-issuances';
    private NativeEffectReconciliationAuthoritySourceResolver $sources;

    public function __construct(
        private NativeState $state,
        private NativeEffectReconciliationIssuanceAuthorityResolver $issuanceResolver,
        private ?\Closure $checkpoint = null,
    )
    {
        $this->sources = new NativeEffectReconciliationAuthoritySourceResolver($state);
    }

    public function issue(NativeEffectReconciliationIssuanceCapability $capability, int $at): array
    {
        $outcome = (new NativeEffectReconciliationIssuancePublicationService(
            $this->state,
            $this->issuanceResolver,
            $this->checkpoint,
        ))->publish($capability, $at);
        return [
            'authority' => $outcome['established_result']['reconciliation_authority'],
            'issuance' => $outcome['established_result']['reconciliation_issuance_evidence'],
            'issuance_authority_consumption' => $outcome['established_result']['issuance_authority_consumption'],
            'result' => $outcome['result'],
        ];
    }

    /** Read-only deterministic target derivation; it grants no issuance authority. */
    public function preview(string $admissionId, int $at, int $expiresAt): array
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

        return ['source' => $source, 'authority' => NativeState::seal($authority)];
    }
}
