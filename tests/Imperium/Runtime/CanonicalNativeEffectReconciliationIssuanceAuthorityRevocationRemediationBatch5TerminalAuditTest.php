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

    public function testTerminalRecordAcceptsExactShaCiBoundedClosure(): void
    {
        $audit = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-terminal-audit-v1.md');
        foreach (['fa963fcea32ddf7d64b6a0ed0b6a9805cc50a783', '124 tests / 855 assertions', '2608 tests / 51982 assertions', '80d335f466cacdd78c4f2e40f1859ad42e9c73e8', '33893111949', '101089298657', '2609 tests / 51993 assertions', 'CANONICAL_NATIVE_EFFECT_RECONCILIATION_ISSUANCE_AUTHORITY_REVOCATION_REMEDIATION_COMPLETE'] as $evidence) {
            self::assertStringContainsString($evidence, $audit, $evidence);
        }
        self::assertStringContainsString('Zero campaign stages remain', $audit);
    }

    private function source(string $class): string
    {
        return (string) file_get_contents((string) (new \ReflectionClass($class))->getFileName());
    }
}
