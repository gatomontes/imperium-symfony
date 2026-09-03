<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\NativeEffect;

use App\Imperium\Runtime\ProviderTransition\NativeEffectAdmissionValidator;
use App\Imperium\Runtime\ProviderTransition\NativeEffectAtomicAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectCredentialCapabilityIssuer;
use App\Imperium\Runtime\ProviderTransition\NativeEffectDoubleExecutionService;
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

    public function atomicAdmission(NativeEffectCredentialCapabilityIssuer $issuer): NativeEffectAtomicAdmissionService
    {
        return new NativeEffectAtomicAdmissionService($this->state, $issuer);
    }

    public function providerDouble(): NativeEffectDoubleExecutionService
    {
        return new NativeEffectDoubleExecutionService($this->state);
    }
}
