<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityReconstructionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch5TerminalAuditTest extends TestCase
{
    public function testTerminalAuditPinsExactCandidateChainVerdictAndLimits(): void
    {
        $root = dirname(__DIR__, 3);
        $audit = (string) file_get_contents($root.'/docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-5-terminal-blackquill-audit-v1.md');
        foreach ([
            'afcaf025d097db0b9adddac25a9083a8be2322a0',
            '0ad41ba9a6904ab375c2c6cbc514f01ac9e79958',
            '86372330b077268da0c2e22cca9fdae3672c001a',
            '66f5a2cb45453dcbdf00f63659cfac7de4c7e62c',
            '54852f945c8a01fd1bd66d051b992b25b56733b5',
            'bf0f8153be28fefe7298a18d8973ef47dbd57ecb',
            'BOUNDED_LOCAL_CANDIDATE_ACCEPTABLE_PENDING_INDEPENDENT_REMOTE_REVIEW',
            'multi-host',
            'hostile',
            'untimestamped Root',
            'LOCAL_RECONCILIATION_ISSUANCE_CAMPAIGN_CANDIDATE_COMPLETE_PENDING_REMOTE_REVIEW',
        ] as $fact) {
            self::assertStringContainsStringIgnoringCase($fact, $audit, $fact);
        }
    }

    public function testPublicIssuerAndReconstructionRetainAuditedBoundaries(): void
    {
        $method = new \ReflectionMethod(NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
        self::assertSame(NativeEffectReconciliationIssuanceCapability::class, (string) $method->getParameters()[0]->getType());

        $source = (string) file_get_contents((new \ReflectionClass(NativeEffectReconciliationAuthorityReconstructionService::class))->getFileName());
        foreach (["'issuance_authority_consumption'", "'issuance_authority'", "'issuance_decision'"] as $join) {
            self::assertStringContainsString($join, $source, $join);
        }
        foreach (['->put(', '->consume(', 'CredentialBroker', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }
}
