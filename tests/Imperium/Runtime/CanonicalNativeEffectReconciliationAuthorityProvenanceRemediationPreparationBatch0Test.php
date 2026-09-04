<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationPreparationBatch0Test extends TestCase
{
    private const array ARTIFACTS = [
        'docs/canonical-native-effect-reconciliation-authority-provenance-remediation-preparation-inventory-v1.md',
        'docs/canonical-native-effect-reconciliation-authority-provenance-remediation-authority-provenance-call-graph-v1.md',
        'docs/canonical-native-effect-reconciliation-authority-provenance-remediation-issuance-custody-consumption-matrix-v1.md',
        'docs/canonical-native-effect-reconciliation-authority-provenance-remediation-adversarial-proof-matrix-v1.md',
        'docs/canonical-native-effect-reconciliation-authority-provenance-remediation-reading-evidence-ledger-v1.json',
        'docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-preparation-batch-0-complete.md',
    ];

    public function testVersionedArtifactsExistAndPreserveTheHardStop(): void
    {
        foreach (self::ARTIFACTS as $path) {
            self::assertFileExists($this->root().'/'.$path, $path);
            self::assertNotSame('', $this->read($path), $path);
        }
        $all = implode("\n", array_map($this->read(...), self::ARTIFACTS));
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_AUTHORITY_PROVENANCE_GAPS_CLASSIFIED',
            'DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE',
            'FORMAL_CLOSURE_REFUSED_RECONCILIATION_AUTHORITY_PROVENANCE_ABSENT',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'Batch 1 is not authorized',
            'No production runtime behavior',
        ] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $all, $marker);
        }
    }

    public function testInventoryClassifiesTheCounterexampleAndAllRequiredDistinctions(): void
    {
        $inventory = $this->read(self::ARTIFACTS[0]);
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY'] as $classification) {
            self::assertStringContainsString('`'.$classification.'`', $inventory);
        }
        foreach ([
            'Exact present self-sealed-array counterexample',
            'Digest integrity vs authenticated issuance',
            'Constant issuer prose vs resolved provenance',
            'Durable record vs consumable authority',
            'Idempotent receipt replay vs reusable authorization',
            'Trusted storage vs trusted ingress',
            'four test fixture constructors',
            'admit(array $authority',
            'No public method in the recovery corridor',
        ] as $fact) {
            self::assertStringContainsStringIgnoringCase($fact, $inventory, $fact);
        }
    }

    public function testReadingLedgerPreservesTheThenCurrentPublicSealAndCallerArrayCounterexample(): void
    {
        $ledger = $this->read(self::ARTIFACTS[4]);
        self::assertStringContainsString('admit(array $authority, int $at)', $ledger);
        self::assertStringContainsString('NativeState::seal() public deterministic digest', $ledger);
        self::assertStringContainsString('CALLER_CREATED_SELF_SEALED_ARRAY', $ledger);
    }

    public function testAllFourFormerFixtureFamiliesNowUseCanonicalIssuance(): void
    {
        $paths = [
            'tests/Imperium/Runtime/CanonicalNativeEffectContinuationExclusivityRemediationBatch3Test.php',
            'tests/Imperium/Runtime/CanonicalNativeEffectContinuationExclusivityRemediationBatch4Test.php',
            'tests/Imperium/Runtime/CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch3Test.php',
            'tests/Imperium/Runtime/CanonicalNativeEffectProcessCustodyFormalClosureRemediationBatch4Test.php',
        ];
        foreach ($paths as $path) {
            $source = $this->read($path);
            self::assertStringContainsString('NativeEffectReconciliationAuthorityIssuanceService', $source, $path);
            self::assertStringContainsString('NativeEffectReconciliationAuthorityResolver', $source, $path);
            self::assertStringNotContainsString('NativeEffectReconciliationAuthorityContract::SCHEMA', $source, $path);
        }
    }

    public function testGraphAndMatrixDefineTheSmallestAcyclicNoProviderDesign(): void
    {
        $graph = $this->read(self::ARTIFACTS[1]);
        $matrix = $this->read(self::ARTIFACTS[2]);
        foreach ([
            'active Imperator principal reconstruction',
            'ReconciliationAuthorityResolver',
            'non-transferable typed custody',
            'consume exact authority',
            'consume claim exactly once',
            'ReceiptReconstruction',
            'caller array',
        ] as $edge) {
            self::assertStringContainsStringIgnoringCase($edge, $graph, $edge);
        }
        foreach ([
            'ISSUANCE_AUTHORITY_CONSUMED',
            'AUTHORITY_CONSUMED_FOR_EXACT_CLAIM',
            'CLAIM_CONSUMED_FOR_EXACT_RECEIPT',
            'RECEIPT_READ_ONLY_REPLAY',
            'Nested use of `AuthorityConsumptionStore`',
        ] as $rule) {
            self::assertStringContainsStringIgnoringCase($rule, $matrix, $rule);
        }
    }

    public function testAdversarialMatrixCoversCounterfeitProvenanceConsumptionProcessAndEvidence(): void
    {
        $matrix = $this->read(self::ARTIFACTS[3]);
        foreach ([
            'Caller copies public schema/issuer/holder/act',
            'Fixture stored with valid digest but no authenticated ingress',
            'Authority revoked before resolution',
            'Two processes consume one reconciliation authority',
            'Exit after authority consumption before claim write',
            'Fresh process resolves unconsumed authority',
            'Reflection sees `admit(array $authority, int $at)`',
            'Real Symfony container resolves corridor',
            'Terminal audit before clean merged Batch 4',
        ] as $case) {
            self::assertStringContainsStringIgnoringCase($case, $matrix, $case);
        }
    }

    public function testReadingLedgerPinsTheBaselineSourcesAndNoExecutionClaim(): void
    {
        $ledger = json_decode($this->read(self::ARTIFACTS[4]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.canonical-native-effect-reconciliation-authority-provenance-remediation-reading-evidence-ledger/v1', $ledger['schema']);
        self::assertSame('a0740ff65747838edaa5a58ded26487bb5bf9f6d', $ledger['audited_main']);
        self::assertSame(0, $ledger['call_site_scan']['production_authority_constructors']);
        self::assertSame(4, $ledger['call_site_scan']['test_authority_constructors']);
        self::assertNull($ledger['counterexample']['canonical_production_issuer']);
        self::assertNull($ledger['counterexample']['authority_consumption']);
        self::assertNull($ledger['counterexample']['claim_consumption']);
        self::assertNull($ledger['git_provenance']['current_external_ci']);
        self::assertNull($ledger['ci_result']);
        self::assertGreaterThanOrEqual(65, count($ledger['sources']));

        foreach ($ledger['sources'] as $source) {
            self::assertSame('FULLY_READ', $source['read_status'], $source['path']);
            self::assertFileExists($this->root().'/'.$source['path'], $source['path']);
            self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/D', $source['normalized_sha256'], $source['path']);
        }
    }

    public function testPreparationLedgerRemainsAnImmutableBaselineAfterAuthorizedLaterBatches(): void
    {
        $ledger = json_decode($this->read(self::ARTIFACTS[4]), true, 512, JSON_THROW_ON_ERROR);
        $pinned = [];
        foreach ($ledger['sources'] as $source) {
            $pinned[$source['path']] = $source['normalized_sha256'];
        }
        self::assertArrayHasKey('src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryClaimAdmissionService.php', $pinned);
        self::assertArrayHasKey('src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryService.php', $pinned);
        self::assertArrayHasKey('src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectCorridor.php', $pinned);
        self::assertArrayHasKey('config/services.yaml', $pinned);
        $handoff = $this->read(self::ARTIFACTS[5]);
        self::assertStringContainsString('issuer, authority, capability, authority record or claim was created', $handoff);
        self::assertStringContainsString('Batch 1 is not authorized by this handoff', $handoff);
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents($this->root().'/'.$path));
    }

    private function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
