<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAtUseCurrentnessContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationHistoricalReconstructionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceAuthorityContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapabilityCustodyContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceConsumptionPublicationContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceDecisionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceOutcomeContract;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationIssuanceAuthorityRevocationRemediationBatch1Test extends TestCase
{
    public function testDecisionSeparatesCompetentIssuerFromSourceProvenance(): void
    {
        self::assertSame('imperium.imperator.native-effect-reconciliation-issuance-decision/v1', NativeEffectReconciliationIssuanceDecisionContract::SCHEMA);
        self::assertContains('competent_issuer', NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS);
        self::assertContains('competent_issuer_provenance', NativeEffectReconciliationIssuanceDecisionContract::REQUIRED_FIELDS);
        self::assertTrue(NativeEffectReconciliationIssuanceDecisionContract::COMPETENCE_RULES['issuer_is_separately_provenanced']);
        self::assertTrue(NativeEffectReconciliationIssuanceDecisionContract::COMPETENCE_RULES['issuer_has_exact_reconciliation_issuance_competence']);
        self::assertTrue(NativeEffectReconciliationIssuanceDecisionContract::COMPETENCE_RULES['decision_is_not_self_issuing']);
    }

    public function testHistoricalAndPossessionFactsAreExplicitlyNonAuthorizing(): void
    {
        self::assertSame([
            'source_provenance', 'issuer_service_possession', 'historical_approval',
            'deterministic_output', 'consumed_native_transition_authority',
        ], NativeEffectReconciliationIssuanceDecisionContract::NON_AUTHORITIES);
        self::assertFalse(NativeEffectReconciliationIssuanceDecisionContract::COMPETENCE_RULES['decision_grants_continuing_authority']);
    }

    public function testIssuanceAuthorityBindsExactPurposeHolderTargetLineageWindowAndReplay(): void
    {
        self::assertSame('ISSUE_EXACT_RECONCILIATION_AUTHORITY', NativeEffectReconciliationIssuanceAuthorityContract::PERMITTED_TRANSITION);
        foreach (['issuance_decision', 'issuer', 'holder', 'target', 'effect_admission', 'callback_start', 'sealed_response', 'effective_at', 'expires_at', 'replay_identity'] as $field) {
            self::assertContains($field, NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_FIELDS);
        }
        foreach (['authority_id', 'authority_schema', 'authority_digest', 'deterministic_receipt_id', 'effective_at', 'expires_at'] as $field) {
            self::assertContains($field, NativeEffectReconciliationIssuanceAuthorityContract::EXACT_TARGET_FIELDS);
        }
        self::assertTrue(NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_INVARIANTS['single_purpose']);
        self::assertTrue(NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_INVARIANTS['single_use']);
        self::assertFalse(NativeEffectReconciliationIssuanceAuthorityContract::REQUIRED_INVARIANTS['continuing_authority']);
    }

    public function testCustodyContractDoesNotCreateOrDeliverCapability(): void
    {
        self::assertSame('CONTRACT_ONLY_NOT_DELIVERED', NativeEffectReconciliationIssuanceCapabilityCustodyContract::STATUS);
        $rules = NativeEffectReconciliationIssuanceCapabilityCustodyContract::REQUIRED_INVARIANTS;
        self::assertFalse($rules['durable_evidence_is_capability']);
        self::assertFalse($rules['caller_constructed_value_is_capability']);
        self::assertTrue($rules['typed_capability_is_process_local']);
        self::assertTrue($rules['typed_capability_is_exact_object']);
        self::assertTrue($rules['typed_capability_is_non_serializable']);
        self::assertTrue($rules['typed_capability_is_non_cloneable']);
        self::assertFalse($rules['contract_delivers_capability']);
        self::assertFalse($rules['contract_creates_authority']);
    }

    public function testAtomicCutFixesLockAndPublicationOrderWithoutPerformingEither(): void
    {
        self::assertSame('reconciliation_issuance_root', NativeEffectReconciliationIssuanceConsumptionPublicationContract::GLOBAL_LOCK_ORDER[0]);
        self::assertSame('reconciliation_authority_claim_use', NativeEffectReconciliationIssuanceConsumptionPublicationContract::GLOBAL_LOCK_ORDER[4]);
        self::assertSame([
            'issuance_authority_consumption',
            'reconciliation_authority',
            'reconciliation_issuance_evidence',
        ], NativeEffectReconciliationIssuanceConsumptionPublicationContract::PUBLICATION_ORDER);
        $rules = NativeEffectReconciliationIssuanceConsumptionPublicationContract::REQUIRED_INVARIANTS;
        self::assertTrue($rules['one_consumption_winner']);
        self::assertTrue($rules['reverse_lock_acquisition_prohibited']);
        self::assertFalse($rules['external_io_inside_lock']);
        self::assertFalse($rules['contract_performs_consumption']);
        self::assertFalse($rules['contract_performs_publication']);
    }

    public function testInterruptionAndRetryAreExactAndChangedInputsConflict(): void
    {
        self::assertSame([
            'BEFORE_CONSUMPTION_NO_OUTPUT',
            'AFTER_CONSUMPTION_BEFORE_AUTHORITY_EXACT_RETRY_ONLY',
            'AFTER_AUTHORITY_BEFORE_ISSUANCE_EVIDENCE_EXACT_RETRY_ONLY',
            'AFTER_ISSUANCE_EVIDENCE_RETURN_ESTABLISHED_RESULT',
        ], NativeEffectReconciliationIssuanceConsumptionPublicationContract::INTERRUPTION_CUTS);
        $rules = NativeEffectReconciliationIssuanceConsumptionPublicationContract::RETRY_RULES;
        self::assertTrue($rules['exact_decision_authority_issuer_target_and_window_converge']);
        foreach (['changed_decision_conflicts', 'changed_authority_conflicts', 'changed_issuer_conflicts', 'changed_target_conflicts', 'changed_lineage_conflicts', 'changed_validity_window_conflicts'] as $rule) {
            self::assertTrue($rules[$rule]);
        }
        self::assertFalse($rules['retry_grants_new_authority']);
    }

    public function testBothGovernedCutsRequirePresentTenseCurrentness(): void
    {
        self::assertSame(['ISSUER_CONSUME_AND_PUBLISH', 'CLAIM_USE_CONSUME_AND_PUBLISH'], NativeEffectReconciliationAtUseCurrentnessContract::GOVERNED_CUTS);
        self::assertSame([
            'operator_root_revocation', 'native_principal_revocation',
            'source_generation_advance', 'source_lifecycle_change',
        ], NativeEffectReconciliationAtUseCurrentnessContract::INDEPENDENTLY_MUTABLE_CHECKS);
        $rules = NativeEffectReconciliationAtUseCurrentnessContract::REQUIRED_INVARIANTS;
        self::assertFalse($rules['resolution_snapshot_is_permanent_authority']);
        self::assertFalse($rules['currentness_is_serialized_into_capability']);
        self::assertTrue($rules['revalidation_occurs_inside_same_governed_cut']);
        self::assertTrue($rules['issuer_cut_revalidates_before_consumption']);
        self::assertTrue($rules['claim_use_cut_revalidates_before_consumption']);
    }

    public function testBoundedExpiryCasesAreNotMisclassifiedAsStaleCapabilityRaces(): void
    {
        self::assertSame(['RR02', 'RR05', 'RR11'], NativeEffectReconciliationAtUseCurrentnessContract::TRANSITIVELY_BOUNDED_EXPIRY_CASES);
    }

    public function testLifecycleAndMigrationRefusalsRemainDistinct(): void
    {
        self::assertSame([
            'SUSPEND' => 'REFUSED_SOURCE_SUSPENDED',
            'SUPERSEDE' => 'REFUSED_SOURCE_SUPERSEDED',
            'REVOKE' => 'REFUSED_SOURCE_REVOKED',
            'EXPIRE' => 'REFUSED_SOURCE_EXPIRED',
            'RETIRE' => 'REFUSED_SOURCE_RETIRED',
            'V3_MIGRATION_REQUIRED' => 'REFUSED_SOURCE_MIGRATION_REQUIRED',
        ], NativeEffectReconciliationAtUseCurrentnessContract::LIFECYCLE_EVENT_TO_REFUSAL);
    }

    public function testRefusalVocabularyCoversEveryRequiredClass(): void
    {
        $refusals = implode("\n", NativeEffectReconciliationIssuanceOutcomeContract::REFUSALS);
        foreach (['MISSING', 'COUNTERFEIT', 'EXPIRED', 'REPLAYED', 'SUBSTITUTED', 'CONSUMED', 'STALE', 'REVOKED', 'SUSPENDED', 'SUPERSEDED', 'RETIRED', 'MIGRATION_REQUIRED', 'CONFLICTED'] as $word) {
            self::assertStringContainsString($word, $refusals);
        }
        self::assertContains('EXACT_RETRY_CONVERGED', NativeEffectReconciliationIssuanceOutcomeContract::RESULTS);
    }

    public function testReconstructionSeparatesCurrentRootFromTimestampedHistory(): void
    {
        $history = NativeEffectReconciliationHistoricalReconstructionContract::HISTORY_RULES;
        self::assertSame('REFUSED_OPERATOR_ROOT_CURRENTLY_INELIGIBLE', $history['CUR08A_operator_root_revocation_is_current_untimestamped']);
        self::assertSame('EXISTS_FRAGMENTED', $history['CUR08A_historical_root_reconstruction']);
        self::assertSame('HISTORICAL_READ_ONLY_RECONSTRUCTION_PERMITTED', $history['CUR08B_native_source_lifecycle_is_timestamped']);
        self::assertTrue($history['CUR08B_requires_current_root_eligibility']);
        $rules = NativeEffectReconciliationHistoricalReconstructionContract::REQUIRED_INVARIANTS;
        self::assertTrue($rules['read_only']);
        self::assertFalse($rules['continuing_authority']);
        self::assertFalse($rules['repairs_root_history_limitation']);
        self::assertFalse($rules['creates_authority']);
        self::assertFalse($rules['consumes_authority']);
    }

    public function testAllNewDefinitionsAreConstantsOnlyAndAuthorityEmpty(): void
    {
        foreach ($this->contractClasses() as $class) {
            $reflection = new \ReflectionClass($class);
            self::assertFalse($reflection->isInstantiable(), $class);
            self::assertSame([], $reflection->getProperties(), $class);
            foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                self::fail($class.' unexpectedly exposes public method '.$method->getName());
            }
            $source = (string) file_get_contents((string) $reflection->getFileName());
            foreach (['AtomicTransition', 'AuthorityConsumptionStore', 'ImmutableRecordStore', 'NativeState', 'random_bytes', 'file_put_contents', 'curl_'] as $operational) {
                self::assertStringNotContainsString($operational, $source, $class);
            }
        }
    }

    public function testExistingOperationalSurfacesRemainUnmodifiedAndUnwired(): void
    {
        if (is_file(dirname(__DIR__, 3).'/src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceCapability.php')) {
            $method = new \ReflectionMethod(\App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceService::class, 'issue');
            self::assertSame(\App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationIssuanceCapability::class, (string) $method->getParameters()[0]->getType());
            return;
        }
        foreach ([
            'src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityIssuanceService.php',
            'src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityResolver.php',
            'src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityClaimDerivationService.php',
            'src/Imperium/Runtime/ProviderTransition/NativeEffectForwardRecoveryClaimAdmissionService.php',
            'src/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationAuthorityReconstructionService.php',
            'src/Imperium/Runtime/NativeEffect/CanonicalNativeEffectCorridor.php',
            'config/services.yaml',
        ] as $path) {
            self::assertStringNotContainsString('NativeEffectReconciliationIssuanceDecisionContract', $this->read($path), $path);
        }
    }

    public function testFrozenRuntimeCandidateInventoryClassifiesTheNewAuthorityVocabulary(): void
    {
        $inventory = $this->read('docs/frozen-runtime-coverage-tripwire-restoration-inventory-v1.tsv');
        self::assertStringContainsString(
            "RUNTIME_CANDIDATE\tAPPROVED_POST_BATCH12_SUCCESSOR\tsrc/Imperium/Runtime/ProviderTransition/NativeEffectReconciliationIssuanceAuthorityContract.php",
            $inventory,
        );
        self::assertStringContainsString('Authority-empty reconciliation issuance single-purpose and single-use contract vocabulary', $inventory);
    }

    public function testDocumentaryStatusStopsAtBatchOneWithFourStagesRemaining(): void
    {
        $document = $this->read('docs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-contracts-v1.md');
        $handoff = $this->read('docs/handoffs/canonical-native-effect-reconciliation-issuance-authority-revocation-remediation-batch-1-complete.md');
        $combined = $document."\n".$handoff;
        self::assertStringContainsString('BATCH_1_COMPLETE_RECONCILIATION_ISSUANCE_AUTHORITY_CURRENTNESS_CONTRACTS_DEFINED', $combined);
        self::assertStringContainsString('Four stages remain', $combined);
        self::assertStringContainsString('Batch 2 is not authorized', $combined);
        self::assertStringContainsString('RR02, RR05 and RR11', $combined);
        self::assertStringContainsString('CUR08A', $combined);
        self::assertStringContainsString('CUR08B', $combined);
        self::assertStringContainsString('No production issuer, resolver, claim, recovery, corridor, container or command behavior changed', str_replace("\n", ' ', $handoff));
    }

    /** @return list<class-string> */
    private function contractClasses(): array
    {
        return [
            NativeEffectReconciliationIssuanceDecisionContract::class,
            NativeEffectReconciliationIssuanceAuthorityContract::class,
            NativeEffectReconciliationIssuanceCapabilityCustodyContract::class,
            NativeEffectReconciliationIssuanceConsumptionPublicationContract::class,
            NativeEffectReconciliationAtUseCurrentnessContract::class,
            NativeEffectReconciliationIssuanceOutcomeContract::class,
            NativeEffectReconciliationHistoricalReconstructionContract::class,
        ];
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
