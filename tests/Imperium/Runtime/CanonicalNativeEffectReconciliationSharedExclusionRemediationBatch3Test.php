<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorizationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityResolver;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability;
use App\Imperium\Runtime\ProviderTransition\NativePrincipal;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testPublicIssuerRequiresExactTypedIssuanceCapability(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
        self::assertSame(NativeEffectReconciliationIssuanceCapability::class, (string) $method->getParameters()[0]->getType());
        self::assertCount(2, $method->getParameters());
    }

    public function testIU01NativeRevocationAfterCapabilityResolutionRefusesBeforeConsumptionOrPublication(): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('iu01');
        $authorization = (new NativeEffectReconciliationIssuanceAuthorizationService($this->state))->authorize($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationIssuanceAuthorityResolver($this->state);
        $capability = $resolver->resolve($authorization['issuance_authority']['issuance_authority_id'], $at + 2);
        $this->revokeSourceNativePrincipal($admission, $at + 3, 'iu01-native-revoke');
        $this->fails('NIR_PRINCIPAL_NOT_CURRENT', fn () => (new NativeEffectReconciliationAuthorityIssuanceService($this->state, $resolver))->issue($capability, $at + 3));
        self::assertSame([], glob($this->root.'/'.NativeEffectReconciliationAuthorityIssuanceService::AUTHORITIES.'/*.json') ?: []);
    }

    public function testCU01NativeRevocationAfterClaimCapabilityResolutionRefusesBeforeClaimPublication(): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('cu01');
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $capability = $resolver->resolve($issued['authority']['authority_id'], $at + 2);
        $this->revokeSourceNativePrincipal($admission, $at + 3, 'cu01-corrected-native-revoke');
        $this->fails('NIR_PRINCIPAL_NOT_CURRENT', fn () => (new NativeEffectReconciliationAuthorityClaimDerivationService($this->state, $resolver))->derive($capability, $at + 3));
        self::assertSame([], glob($this->root.'/var/imperium/runtime/canonical-native-effect-forward-recovery-claims/*.json') ?: []);
    }

    private function revokeSourceNativePrincipal(array $admission, int $at, string $actId): void
    {
        $commit = $this->state->get('transitions', $admission['native_root']);
        $chain = $this->state->get('authorities', $commit['authority_id']);
        $act = $this->act;
        $act['action'] = 'REVOKE';
        $act['act_id'] = $actId;
        (new NativePrincipal($this->state, static fn (): int => $at))->lifecycle($chain['principal']['id'], $this->sign($act));
    }
}
