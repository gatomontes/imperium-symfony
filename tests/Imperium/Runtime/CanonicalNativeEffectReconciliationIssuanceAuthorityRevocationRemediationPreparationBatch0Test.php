<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationPreparationBatch0Test extends TestCase
{
    private const array ARTIFACTS = [
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-inventory-v1.md',
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-derivation-authority-currentness-call-graph-v1.md',
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-issuance-custody-consumption-matrix-v1.md',
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-revocation-race-matrix-v1.md',
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-adversarial-proof-matrix-v1.md',
        'docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-reading-evidence-ledger-v1.json',
        'docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-preparation-batch-0-complete.md',
    ];

    public function testVersionedArtifactsExistAndPreserveTheHardStop(): void
    {
        foreach (self::ARTIFACTS as $path) {
            self::assertFileExists($this->root().'/'.$path, $path);
            self::assertNotSame('', $this->read($path), $path);
        }
        $all = implode("\n", array_map($this->read(...), self::ARTIFACTS));
        foreach ([
            'PREPARATION_BATCH_0_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_AND_REVOCATION_GAPS_CLASSIFIED',
            'DOCUMENTARY_ONLY_NO_RUNTIME_CHANGE',
            'FORMAL_CLOSURE_REFUSED_RECONCILIATION_DERIVATION_AUTHORITY_ABSENT',
            'REVOCATION_AT_CONSUMPTION_UNPROVED',
            'BATCH_7_LIVE_TRIAL_AUTHORIZATION_SUSPENDED',
            'Batch 1 is not authorized',
        ] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $all, $marker);
        }
    }

    public function testInventoryClassifiesEveryRequiredDistinctionAndSurface(): void
    {
        $inventory = $this->read(self::ARTIFACTS[0]);
        foreach (['EXISTS_CANONICALLY', 'EXISTS_FRAGMENTED', 'ABSENT', 'DEFERRED_BOUNDARY'] as $classification) {
            self::assertStringContainsString('`'.$classification.'`', $inventory, $classification);
        }
        foreach ([
            'Exact unguarded issuance counterexample',
            'Source provenance vs derivation authorization',
            'Issuer service identity vs issuer competence',
            'Construction access vs authority',
            'Deterministic output vs authorized issuance',
            'transition.records.authority_consumption',
            'continuing_authority: false',
            'Smallest acyclic later design',
            'Windows/Linux',
            'stale closure consumers',
        ] as $finding) {
            self::assertStringContainsStringIgnoringCase($finding, $inventory, $finding);
        }
    }

    public function testCurrentIssuerSignatureHasNoDerivationAuthorityAndNoConsumption(): void
    {
        $source = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityIssuanceService.php');
        self::assertStringContainsString('public function issue(string $admissionId, int $at, int $expiresAt): array', $source);
        self::assertStringContainsString('$this->sources->resolve($admissionId, $at)', $source);
        self::assertStringContainsString('$this->records->put(self::AUTHORITIES', $source);
        self::assertStringContainsString('$this->records->put(self::ISSUANCES', $source);
        self::assertStringNotContainsString('AuthorityConsumptionStore', $source);
        self::assertStringNotContainsString('IssuanceAuthority', $source);
        self::assertStringNotContainsString('IssuanceDecision', $source);
        self::assertStringNotContainsString('Capability $', $source);
    }

    public function testSourceDecisionIsExactSingleUseAndConsumedOnlyInNativeCommit(): void
    {
        $authority = $this->read('src/Imperium/Runtime/ProviderTransition/NativeAuthority.php');
        foreach (['AUTHORIZED_EXACT_TRANSITION', "'authority_single_use' => true", "'continuing_authority' => false"] as $fact) {
            self::assertStringContainsString($fact, $authority, $fact);
        }
        $admission = $this->read('src/Imperium/Runtime/ProviderTransition/NativeAdmission.php');
        foreach (['native-transition-consumption/v1', "'consumed' => true", "'continuing_authority' => false"] as $fact) {
            self::assertStringContainsString($fact, $admission, $fact);
        }
        $consumer = $this->read('src/Imperium/Runtime/ProviderTransition/NativeConsumer.php');
        self::assertStringContainsString("'records' => \$records", $consumer);
        self::assertStringContainsString('PREPARED_NO_AUTHORITY_CONSUMED', $consumer);
    }

    public function testGraphPinsResolutionChecksAndConsumeTimeAbsences(): void
    {
        $graph = $this->read(self::ARTIFACTS[1]);
        foreach ([
            'Root anchor current',
            'native principal active/not revoked/not expired',
            'source Imperator lifecycle ACTIVE',
            'source generation has no effective successor',
            'DOES NOT call inspect()',
            'DOES NOT call SourceResolver',
            'DOES NOT verify Root/native/source lifecycle/generation',
            '`forwardComplete()` later',
        ] as $edge) {
            self::assertStringContainsStringIgnoringCase($edge, $graph, $edge);
        }

        $resolver = $this->read('src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityResolver.php');
        $consume = substr($resolver, strpos($resolver, 'public function consume(') ?: 0);
        self::assertStringContainsString('$this->issued[$capability->capabilityId]', $consume);
        self::assertStringContainsString('$at >= $capability->expiresAt', $consume);
        self::assertStringContainsString('$this->incarnation->recognizes(', $consume);
        self::assertStringNotContainsString('$this->inspect(', $consume);
        self::assertStringNotContainsString('NativeEffectReconciliationAuthoritySourceResolver', $consume);
        self::assertStringNotContainsString('NativePrincipal', $consume);
        self::assertStringNotContainsString('NativeRootActs', $consume);
    }

    public function testRevocationMatrixCoversEveryRequiredResolveRevokeConsumeRace(): void
    {
        $matrix = $this->read(self::ARTIFACTS[3]);
        foreach ([
            'Operator Root trust anchor',
            'Native principal effective `REVOKE`',
            'Higher source Imperator principal generation',
            'Lifecycle becomes `SUPERSEDE` or `REVOKE`',
            'Prior-test gap',
            'testRevokedRootAfterIssuanceRefusesFreshResolution',
            'present-tense re-resolution',
        ] as $race) {
            self::assertStringContainsStringIgnoringCase($race, $matrix, $race);
        }
    }

    public function testMatricesPreserveTypedRecoveryAtomicRetryAndNoProviderBoundaries(): void
    {
        $matrix = $this->read(self::ARTIFACTS[2]).$this->read(self::ARTIFACTS[4]);
        foreach ([
            'Typed issuance capability',
            'PID/process-incarnation bound',
            'Claim-to-receipt',
            'Reconstruction | receipt/claim/authority/Root chain',
            'Two issuers race',
            'Fresh process',
            'Windows',
            'Linux',
            'NO_PROVIDER_NO_NETWORK_NO_CREDENTIAL',
            'DEFERRED_BOUNDARY',
        ] as $fact) {
            self::assertStringContainsStringIgnoringCase($fact, $matrix, $fact);
        }
    }

    public function testLedgerPinsCompletePriorCampaignCurrentSourcesCallsAndEvidenceLimits(): void
    {
        $ledger = json_decode($this->read(self::ARTIFACTS[5]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('imperium.canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-reading-evidence-ledger/v1', $ledger['schema']);
        self::assertSame('3dceba3057497c6c80f019bd78835335cf69c774', $ledger['audited_main']);
        self::assertSame('CLEAN', $ledger['entry_worktree']);
        self::assertCount(14, $ledger['prior_campaign_documents']);
        self::assertCount(8, $ledger['prior_campaign_handoffs']);
        self::assertCount(8, $ledger['prior_campaign_tests']);
        self::assertGreaterThanOrEqual(25, count($ledger['current_core_sources']));
        self::assertSame(0, $ledger['call_site_scan']['production_runtime_files_invoking_issue']);
        self::assertSame(7, $ledger['call_site_scan']['test_or_support_files_invoking_issue']);
        self::assertTrue($ledger['call_site_scan']['production_corridor_exposes_issuer']);
        self::assertNull($ledger['counterexamples']['issuance_decision_input']);
        self::assertNull($ledger['counterexamples']['issuance_authority_consumption']);
        self::assertSame([], $ledger['counterexamples']['consumption_currentness']);
        self::assertNull($ledger['git_provenance']['current_preparation_external_ci']);
        self::assertFalse($ledger['network_or_external_io']);
        self::assertFalse($ledger['authority_or_claim_operation_performed']);
        self::assertFalse($ledger['batch_1_authorized']);
        self::assertFalse($ledger['batch_7_authorized']);

        foreach ([
            'governing_documents',
            'prior_campaign_documents',
            'prior_campaign_handoffs',
            'prior_campaign_tests',
            'current_core_sources',
            'reusable_issuance_and_lifecycle_sources',
            'current_call_site_tests_and_workers',
        ] as $set) {
            foreach ($ledger[$set] as $path) {
                self::assertFileExists($this->root().'/'.$path, $path);
            }
        }
    }

    public function testCompletionHandoffReportsExactlyFiveRemainingStagesAndFocusedCommand(): void
    {
        $handoff = $this->read(self::ARTIFACTS[6]);
        foreach ([
            '1. Batch 1',
            '2. Batch 2',
            '3. Batch 3',
            '4. Batch 4',
            '5. Batch 5',
            'CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationPreparationBatch0Test.php',
            'No production runtime behavior, configuration or service wiring changed',
            'Batch 1 contract/test',
            'Iron Gate and Lazaretto remained closed',
        ] as $boundary) {
            self::assertStringContainsStringIgnoringCase($boundary, $handoff, $boundary);
        }
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
