<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityClaimDerivationService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionService;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch5TerminalAuditTest extends TestCase
{
    public function testDecisionAndBothUseCutsHoldNativeExclusionAcrossCurrentnessAndPublication(): void
    {
        $decision = $this->source(NativeEffectReconciliationIssuanceDecisionService::class);
        self::assertStringContainsString('$this->state->locked(', $decision);
        self::assertStringContainsString('authorizeInsideNativeExclusion', $decision);

        $issuer = $this->source(NativeEffectReconciliationAuthorityIssuanceService::class);
        self::assertStringContainsString('NativeEffectReconciliationIssuanceAuthorityCapability $capability', $issuer);
        self::assertStringNotContainsString('issue(string $admissionId', $issuer);

        $claim = $this->source(NativeEffectReconciliationAuthorityClaimDerivationService::class);
        self::assertStringContainsString('$this->state->locked(', $claim);
        self::assertStringContainsString('$this->resolver->inspect(', $claim);
        self::assertStringContainsString('$this->resolver->consume(', $claim);
    }

    public function testTerminalPathContainsNoProviderCredentialOrExternalIoEdge(): void
    {
        $directory = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = '';
        foreach ([
            'NativeEffectReconciliationIssuanceDecisionService.php',
            'NativeEffectReconciliationIssuanceAuthorityResolver.php',
            'NativeEffectReconciliationAuthorizedIssuanceService.php',
            'NativeEffectReconciliationAuthorityIssuanceService.php',
            'NativeEffectReconciliationAuthorityResolver.php',
            'NativeEffectReconciliationAuthorityClaimDerivationService.php',
            'NativeEffectReconciliationAuthorityReconstructionService.php',
        ] as $file) {
            $source .= (string) file_get_contents($directory.$file);
        }
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testTerminalRecordAcceptsLocalCandidateButWithholdsUnprovedCiClosure(): void
    {
        $audit = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-terminal-audit-v1.md');
        foreach (['fa963fcea32ddf7d64b6a0ed0b6a9805cc50a783', '124 tests / 855 assertions', '2608 tests / 51982 assertions', 'CAMPAIGN_CLOSURE_WITHHELD_EXACT_SHA_GITHUB_CI_ABSENT'] as $evidence) {
            self::assertStringContainsString($evidence, $audit, $evidence);
        }
        self::assertStringNotContainsString('CAMPAIGN_COMPLETE', $audit);
        self::assertStringNotContainsString('zero stages remain', strtolower($audit));
    }

    private function source(string $class): string
    {
        return (string) file_get_contents((string) (new \ReflectionClass($class))->getFileName());
    }
}
