<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimAdmissionService;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityCapability;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch5Test extends TestCase
{
    public function testArrayCounterexampleIsAbsentFromTheCanonicalAdmissionBoundary(): void
    {
        $method = new \ReflectionMethod(NativeEffectForwardRecoveryClaimAdmissionService::class, 'admit');
        self::assertSame(NativeEffectReconciliationAuthorityCapability::class, (string) $method->getParameters()[0]->getType());
        self::assertSame('capability', $method->getParameters()[0]->getName());
    }

    public function testIndependentChainContainsEveryAuthorityAndConsumptionJoin(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = '';
        foreach ([
            'NativeEffectReconciliationAuthoritySourceResolver.php',
            'NativeEffectReconciliationAuthorityIssuanceService.php',
            'NativeEffectReconciliationAuthorityResolver.php',
            'NativeEffectReconciliationAuthorityClaimDerivationService.php',
            'NativeEffectForwardRecoveryService.php',
            'NativeEffectReconciliationAuthorityReconstructionService.php',
        ] as $file) { $source .= (string) file_get_contents($root.$file); }
        foreach (['NativeAuthority', 'NativePrincipal', 'source_native_authority', 'source_native_principal', 'issued_authority', 'custody_capability_id', 'AuthorityConsumptionStore', 'operator_root_act'] as $join) {
            self::assertStringContainsString($join, $source, $join);
        }
    }

    public function testRecoveryAndReconstructionContainNoProviderCredentialOrNetworkEdge(): void
    {
        $root = dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/';
        $source = (string) file_get_contents($root.'NativeEffectForwardRecoveryService.php')
            .(string) file_get_contents($root.'NativeEffectReconciliationAuthorityReconstructionService.php');
        foreach (['CredentialBroker', 'AgentMailEmailTransport', 'HttpClient', 'curl_', 'getenv(', '$_ENV', '$_SERVER', 'providerDouble'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $source, $forbidden);
        }
    }

    public function testFrozenCoverageInventoryNamesTheNewCandidateAndConsumer(): void
    {
        $inventory = (string) file_get_contents(dirname(__DIR__, 3).'/docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv');
        self::assertStringContainsString("RUNTIME_CANDIDATE\tAPPROVED_POST_BATCH12_SUCCESSOR\tsrc/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityConsumptionContract.php", $inventory);
        self::assertStringContainsString("AUTHORITY_CONSUMPTION_STORE_CONSUMER\tAPPROVED_EXACT_CONSUMER\tsrc/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryService.php", $inventory);
    }

    public function testEvidenceLedgerDoesNotFabricatePendingCi(): void
    {
        $ledger = json_decode((string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-authority-provenance-remediation-evidence-ledger-v1.json'), true, 32, JSON_THROW_ON_ERROR);
        self::assertSame('98ba984c7cb808bd2195b5637f61c079bd47a22f', $ledger['terminal_audit_start_sha']);
        self::assertSame(2474, $ledger['local_tests'][3]['tests']);
        self::assertSame(51016, $ledger['local_tests'][3]['assertions']);
        self::assertNull($ledger['external_ci']);
        self::assertSame('WITHHELD_PENDING_EXACT_MERGED_SHA_CI', $ledger['closure']);
        self::assertFalse($ledger['provider_effect_performed']);
    }

    public function testCandidateAuditRefusesCeremonialClosure(): void
    {
        $audit = (string) file_get_contents(dirname(__DIR__, 3).'/docs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-5-terminal-audit-candidate-v1.md');
        foreach (['BATCH_5_TERMINAL_AUDIT_CANDIDATE_LOCAL_PROOF_PASSED_CI_PENDING', 'digest authenticates issuance', 'trusted ingress', 'reusable authorization', 'GitHub CI', 'Batch 7 remains suspended'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $audit, $marker);
        }
    }
}
