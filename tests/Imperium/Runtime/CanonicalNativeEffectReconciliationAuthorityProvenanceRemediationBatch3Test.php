<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\NativeEffect\CanonicalNativeEffectCorridor;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityCapability;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolver;

require_once __DIR__.'/CanonicalNativeEffectCorridorActivationBatch4Test.php';

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch3Test extends CanonicalNativeEffectCorridorActivationBatch4Test
{
    public function testPublicAdmissionAcceptsOnlyTypedProcessCustody(): void
    {
        $method = new \ReflectionMethod(NativeEffectForwardRecoveryClaimAdmissionService::class, 'admit');
        self::assertSame(NativeEffectReconciliationAuthorityCapability::class, (string) $method->getParameters()[0]->getType());
        self::assertSame('capability', $method->getParameters()[0]->getName());
        $source = (string) file_get_contents($method->getFileName());
        self::assertStringNotContainsString('admit(array $authority', $source);
        self::assertStringNotContainsString('NativeState::seal($authority)', $source);
        self::assertStringNotContainsString('NativeEffectReconciliationAuthorityContract::ISSUER', $source);
    }

    public function testCorridorExposesCanonicalIssuanceResolutionAndSharedCustodyAdmission(): void
    {
        self::assertSame(NativeEffectReconciliationAuthorityIssuanceService::class, (string) (new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'reconciliationAuthorityIssuer'))->getReturnType());
        self::assertSame(NativeEffectReconciliationAuthorityResolver::class, (string) (new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'reconciliationAuthorityResolver'))->getReturnType());
        $admission = new \ReflectionMethod(CanonicalNativeEffectCorridor::class, 'recoveryClaimAdmission');
        self::assertSame(NativeEffectReconciliationAuthorityResolver::class, (string) $admission->getParameters()[0]->getType());
    }

    public function testAllFormerSelfSealedFixtureSitesUseTheCanonicalPath(): void
    {
        foreach ([
            'CanonicalNativeEffectContinuationExclusivityRemediationBatch3Test.php',
            'CanonicalNativeEffectContinuationExclusivityRemediationBatch4Test.php',
            'CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch3Test.php',
            'CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch4Test.php',
        ] as $file) {
            $source = (string) file_get_contents(__DIR__.'/'.$file);
            self::assertStringContainsString('NativeEffectReconciliationAuthorityIssuanceService', $source, $file);
            self::assertStringContainsString('NativeEffectReconciliationAuthorityResolver', $source, $file);
            self::assertStringNotContainsString('NativeEffectReconciliationAuthorityContract::SCHEMA', $source, $file);
        }
    }

    public function testForwardRecoverySourceConsumesExactClaimAndHasNoEffectEdge(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryService.php');
        self::assertStringContainsString('$this->consumptions->consume(', $source);
        self::assertStringContainsString('$claim[\'record_digest\']', $source);
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER', 'providerDouble'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testBatchDocumentationPreservesTheAdversarialAndLiveStops(): void
    {
        $docs = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-3-admission-corridor-v1.md')
            .(string) file_get_contents(dirname(__DIR__, 3).'/docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-3-complete.md');
        foreach (['BATCH_3_COMPLETE_TYPED_ADMISSION_AND_CORRIDOR_INTEGRATION', 'Batch 4', 'Batch 5', 'Batch 7', 'No credential or live provider'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $docs, $marker);
        }
    }
}
