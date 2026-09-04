<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Typed-custody admission; caller-authored authority records are not accepted. */
final readonly class NativeEffectForwardRecoveryClaimAdmissionService
{
    public const string AUTHORITIES = NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES;
    public const string CLAIMS = 'var/imperium/runtime/canonical-native-effect-forward-recovery-claims';
    private NativeEffectReconciliationAuthorityClaimDerivationService $derivation;

    public function __construct(NativeState $state, NativeEffectReconciliationAuthorityResolver $resolver)
    {
        $this->derivation = new NativeEffectReconciliationAuthorityClaimDerivationService($state, $resolver);
    }

    public function admit(NativeEffectReconciliationAuthorityCapability $capability, int $at): array
    {
        return $this->derivation->derive($capability, $at);
    }

    public static function receiptId(string $admissionId): string
    {
        return 'canonical-native-effect-receipt-'.substr(hash('sha256', $admissionId), 0, 20);
    }

}
