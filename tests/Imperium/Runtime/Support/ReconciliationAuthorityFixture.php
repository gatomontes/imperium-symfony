<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionService;
use App\Imperium\Runtime\ProviderTransition\NativeState;

/** Test-only canonical setup for the mandatory typed issuance path. */
final class ReconciliationAuthorityFixture
{
    public static function issue(
        NativeState $state,
        string $admissionId,
        int $at,
        int $expiresAt,
        ?\Closure $checkpoint = null,
    ): array {
        $authorized = (new NativeEffectReconciliationIssuanceDecisionService($state))->authorize($admissionId, $at, $expiresAt);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($state);
        $capability = $resolver->resolve($authorized['issuance_authority']['issuance_authority_id'], $at);

        return (new NativeEffectReconciliationAuthorityIssuanceService($state, $resolver, $checkpoint))->issue($capability, $at);
    }

    private function __construct() {}
}
