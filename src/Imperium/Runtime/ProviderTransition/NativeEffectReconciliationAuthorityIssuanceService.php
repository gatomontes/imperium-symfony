<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Public issuer boundary: exact typed issuance custody is mandatory. */
final readonly class NativeEffectReconciliationAuthorityIssuanceService
{
    public const string AUTHORITIES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authorities-v2';
    public const string ISSUANCES = 'var/imperium/runtime/canonical-native-effect-reconciliation-authority-issuances';

    private NativeEffectReconciliationAuthorizedIssuanceService $authorized;

    public function __construct(
        NativeState $state,
        NativeEffectReconciliationIssuanceAuthorityResolver $resolver,
        ?\Closure $checkpoint = null,
    ) {
        $this->authorized = new NativeEffectReconciliationAuthorizedIssuanceService($state, $resolver, $checkpoint);
    }

    public function issue(NativeEffectReconciliationIssuanceAuthorityCapability $capability, int $at): array
    {
        return $this->authorized->issue($capability, $at);
    }
}
