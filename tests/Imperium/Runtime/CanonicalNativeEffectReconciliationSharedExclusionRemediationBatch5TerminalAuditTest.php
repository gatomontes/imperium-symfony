<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;
use PHPUnit\Framework\TestCase;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationSharedExclusionRemediationBatch5TerminalAuditTest extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testIndependentSourceReconstructionProvesSharedBeforeTargetAtAllThreeCuts(): void
    {
        $authorization = $this->source('NativeEffectReconciliationIssuanceAuthorizationService.php');
        $issuer = $this->source('NativeEffectReconciliationAuthorityIssuanceService.php');
        $claim = $this->source('NativeEffectReconciliationAuthorityClaimDerivationService.php');
        $this->ordered($authorization, ['$this->state->locked', '$this->sources->resolve', 'reconciliation-issuance-root:', '$this->records->put(self::DECISIONS', '$this->records->put(self::AUTHORITIES']);
        $this->ordered($issuer, ['$this->state->locked', 'reconciliation-issuance-root:', '$this->resolver->consume', '$this->consumptions->consume', '$this->records->put(self::AUTHORITIES', '$this->records->put(self::ISSUANCES']);
        $this->ordered($claim, ['$this->state->locked', 'canonical-native-effect-reconciliation-authority:', '$this->resolver->inspect', '$this->resolver->consume', '$this->records->put']);
    }

    public function testEndToEndAuthorizedIssuanceAndClaimRemainProviderEmpty(): void
    {
        [$admission, $at] = $this->sealedResponseForSharedCampaign('terminal-audit');
        $issued = $this->issueReconciliation($admission['admission_id'], $at + 1, $at + 100);
        $resolver = new NativeEffectReconciliationAuthorityResolver($this->state);
        $claim = (new NativeEffectReconciliationAuthorityClaimDerivationService($this->state, $resolver))->derive(
            $resolver->resolve($issued['authority']['authority_id'], $at + 2), $at + 2,
        );
        self::assertSame($issued['authority']['authority_id'], $claim['reconciliation_authority']['id']);
        foreach (['provider_invocation_permitted', 'credential_resolution_permitted', 'callback_reinvocation_permitted', 'automatic_retry_permitted', 'continuing_authority'] as $flag) {
            self::assertFalse($claim[$flag]);
        }
    }

    public function testTerminalArtifactsStateBoundariesWithoutInventingCI(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/docs/canonical-native-effect-reconciliation-shared-exclusion-remediation-terminal-audit-v1.md');
        $handoff = (string) file_get_contents($root.'/docs/handoffs/canonical-native-effect-reconciliation-shared-exclusion-remediation-campaign-complete.md');
        self::assertStringContainsString('CANONICAL_NATIVE_EFFECT_RECONCILIATION_SHARED_EXCLUSION_REMEDIATION_COMPLETE_LOCAL', $audit.$handoff);
        self::assertStringContainsString('GITHUB_CI_NOT_CLAIMED', $audit.$handoff);
        self::assertStringContainsString('DISTRIBUTED_AND_HOSTILE_WRITER_BOUNDARY_DEFERRED', $audit.$handoff);
        self::assertStringContainsString('BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED', $audit.$handoff);
    }

    private function source(string $name): string
    {
        return (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/'.$name);
    }

    private function ordered(string $source, array $needles): void
    {
        $position = -1;
        foreach ($needles as $needle) {
            $next = strpos($source, $needle);
            self::assertNotFalse($next, $needle);
            self::assertGreaterThan($position, $next, $needle);
            $position = $next;
        }
    }
}
