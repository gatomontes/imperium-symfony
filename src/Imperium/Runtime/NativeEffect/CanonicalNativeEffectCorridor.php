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

    public function recoveryClaimAdmission(): NativeEffectForwardRecoveryClaimAdmissionService
    {
        return new NativeEffectForwardRecoveryClaimAdmissionService($this->state);
    }

    public function forwardRecovery(): NativeEffectForwardRecoveryService
    {
        return new NativeEffectForwardRecoveryService($this->state);
    }
}
