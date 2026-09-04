<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\NativeEffect;

use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionValidator;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectContinuationCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeState;

/** Auto-discovered construction boundary. It exposes no command, credential resolver or provider transport. */
final readonly class CanonicalNativeEffectCorridor
{
    public function __construct(private NativeState $state) {}

    public function admissionValidator(): NativeEffectAdmissionValidator
    {
        return new NativeEffectAdmissionValidator($this->state);
    }

    public function capabilityIssuer(): NativeEffectCredentialCapabilityIssuer
    {
        return new NativeEffectCredentialCapabilityIssuer();
    }

    public function continuationIssuer(): NativeEffectContinuationCapabilityIssuer
    {
        return new NativeEffectContinuationCapabilityIssuer();
    }

    public function atomicAdmission(
        NativeEffectCredentialCapabilityIssuer $issuer,
        ?NativeEffectContinuationCapabilityIssuer $continuations = null,
    ): NativeEffectAtomicAdmissionService
    {
        return new NativeEffectAtomicAdmissionService($this->state, $issuer, $continuations ?? $this->continuationIssuer());
    }

    public function providerDouble(NativeEffectContinuationCapabilityIssuer $continuations): NativeEffectDoubleExecutionService
    {
        return new NativeEffectDoubleExecutionService($this->state, $continuations);
    }

    public function reconciliationIssuanceAuthorization(): NativeEffectReconciliationIssuanceAuthorizationService
    {
        return new NativeEffectReconciliationIssuanceAuthorizationService($this->state);
    }

    public function reconciliationIssuanceAuthorityResolver(): NativeEffectReconciliationIssuanceAuthorityResolver
    {
        return new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
    }

    public function reconciliationAuthorityIssuer(?NativeEffectReconciliationIssuanceAuthorityResolver $resolver = null): NativeEffectReconciliationAuthorityIssuanceService
    {
        return new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver ?? $this->reconciliationIssuanceAuthorityResolver());
    }

    public function reconciliationAuthorityResolver(): NativeEffectReconciliationAuthorityResolver
    {
        return new NativeEffectReconciliationAuthorityResolver($this->state);
    }

    public function recoveryClaimAdmission(NativeEffectReconciliationAuthorityResolver $resolver): NativeEffectForwardRecoveryClaimAdmissionService
    {
        return new NativeEffectForwardRecoveryClaimAdmissionService($this->state, $resolver);
    }

    public function forwardRecovery(): NativeEffectForwardRecoveryService
    {
        return new NativeEffectForwardRecoveryService($this->state);
    }
}
