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

    private function source(string $class): string
    {
        return (string) file_get_contents((string) (new \ReflectionClass($class))->getFileName());
    }
}
