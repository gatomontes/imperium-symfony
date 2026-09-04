<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimConsumptionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectForwardRecoveryClaimV2Contract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityConsumptionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityCustodyContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityIssuanceContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityReconstructionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityResolutionContract;
use App\Imperium\Runtime\ProviderTransition\NativeEffectReconciliationAuthorityV2Contract;
use PHPUnit\Framework\TestCase;

final class CanonicalNativeEffectReconciliationAuthorityProvenanceRemediationBatch1Test extends TestCase
{
    public function testAuthorityAndIssuanceContractsBindResolvedRootedCompetence(): void
    {
        self::assertSame('imperium.imperator.native-effect-reconciliation-authority/v2', NativeEffectReconciliationAuthorityV2Contract::SCHEMA);
        self::assertContains('source_native_authority', NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FIELDS);
        self::assertContains('source_native_principal', NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FIELDS);
        self::assertContains('source_native_transition', NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FIELDS);
        self::assertContains('issuance_id', NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FIELDS);
        self::assertSame([
            'effect_admission.native_root',
            'native_transition_commit.authority_id',
            'resolved_native_authority.current_native_principal',
            'verified_operator_root_act',
        ], NativeEffectReconciliationAuthorityIssuanceContract::ROOTED_SOURCE_PATH);
        self::assertContains('issued_authority', NativeEffectReconciliationAuthorityIssuanceContract::REQUIRED_FIELDS);
        self::assertContains('authority_issued', NativeEffectReconciliationAuthorityIssuanceContract::REQUIRED_TRUE_FLAGS);
    }

    public function testLabelsDigestAndTrustedStorageAreExplicitlyNonAuthenticating(): void
    {
        foreach (['schema', 'act', 'holder', 'issuer_service', 'sealed', 'record_digest'] as $field) {
            self::assertContains($field, NativeEffectReconciliationAuthorityV2Contract::NON_AUTHENTICATING_FIELDS);
        }
        foreach (['constant_issuer_label', 'caller_computed_digest', 'trusted_directory_without_trusted_ingress', 'fixture_only_source_record'] as $substitute) {
            self::assertContains($substitute, NativeEffectReconciliationAuthorityResolutionContract::PROHIBITED_PROVENANCE_SUBSTITUTES);
        }
    }

    public function testCustodySeparatesDurableRecordFromTypedCapability(): void
    {
        $rules = NativeEffectReconciliationAuthorityCustodyContract::REQUIRED_INVARIANTS;
        self::assertFalse($rules['caller_supplies_record']);
        self::assertFalse($rules['caller_supplies_digest']);
        self::assertFalse($rules['durable_record_is_capability']);
        self::assertTrue($rules['resolver_loads_canonical_issuance']);
        self::assertTrue($rules['resolver_revalidates_source_competence']);
        self::assertTrue($rules['typed_capability_is_process_local']);
        self::assertTrue($rules['typed_capability_is_non_serializable']);
        self::assertTrue($rules['typed_capability_is_non_cloneable']);
        self::assertSame(['authority_id', 'current_time'], NativeEffectReconciliationAuthorityCustodyContract::DELIVERY_INPUTS);
    }

    public function testAuthorityAndClaimHaveSeparateAtomicSingleUseContracts(): void
    {
        $authority = NativeEffectReconciliationAuthorityConsumptionContract::REQUIRED_INVARIANTS;
        self::assertTrue($authority['authority_consumed_once']);
        self::assertTrue($authority['exact_authority_claim_replay_converges']);
        self::assertTrue($authority['different_claim_conflicts']);
        self::assertTrue($authority['claim_write_uses_same_locked_transition']);
        self::assertFalse($authority['consumption_grants_continuing_authority']);

        $claim = NativeEffectForwardRecoveryClaimConsumptionContract::REQUIRED_INVARIANTS;
        self::assertTrue($claim['claim_consumed_once']);
        self::assertTrue($claim['exact_claim_receipt_replay_converges']);
        self::assertTrue($claim['different_receipt_conflicts']);
        self::assertTrue($claim['receipt_write_uses_same_locked_transition']);
        self::assertFalse($claim['receipt_replay_is_new_authorization']);
    }

    public function testNoProviderFlagsRemainFalseAcrossEveryNewRecord(): void
    {
        foreach ([
            NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FALSE_FLAGS,
            NativeEffectReconciliationAuthorityIssuanceContract::REQUIRED_FALSE_FLAGS,
            NativeEffectForwardRecoveryClaimV2Contract::REQUIRED_FALSE_FLAGS,
        ] as $flags) {
            foreach (['continuing_authority'] as $flag) {
                self::assertContains($flag, $flags);
            }
        }
        foreach (['provider_invocation_permitted', 'credential_resolution_permitted', 'callback_reinvocation_permitted', 'automatic_retry_permitted'] as $flag) {
            self::assertContains($flag, NativeEffectReconciliationAuthorityV2Contract::REQUIRED_FALSE_FLAGS);
            self::assertContains($flag, NativeEffectForwardRecoveryClaimV2Contract::REQUIRED_FALSE_FLAGS);
        }
    }

    public function testReconstructionIsReadOnlyAndCannotManufactureProgress(): void
    {
        $rules = NativeEffectReconciliationAuthorityReconstructionContract::REQUIRED_INVARIANTS;
        self::assertTrue($rules['read_only']);
        foreach (['repairs_records', 'creates_authority', 'creates_claim', 'completes_receipt', 'invokes_provider', 'resolves_credentials'] as $rule) {
            self::assertFalse($rules[$rule]);
        }
        self::assertSame('receipt', NativeEffectReconciliationAuthorityReconstructionContract::JOIN_ORDER[0]);
        self::assertContains('operator_root_act', NativeEffectReconciliationAuthorityReconstructionContract::JOIN_ORDER);
    }

    public function testBatchContractsRemainDeclarativeAfterTheLaterAdmissionReplacement(): void
    {
        $classes = [
            NativeEffectReconciliationAuthorityV2Contract::class,
            NativeEffectReconciliationAuthorityIssuanceContract::class,
            NativeEffectReconciliationAuthorityCustodyContract::class,
            NativeEffectReconciliationAuthorityResolutionContract::class,
            NativeEffectReconciliationAuthorityConsumptionContract::class,
            NativeEffectForwardRecoveryClaimV2Contract::class,
            NativeEffectForwardRecoveryClaimConsumptionContract::class,
            NativeEffectReconciliationAuthorityReconstructionContract::class,
        ];
        foreach ($classes as $class) {
            $source = (string) file_get_contents((new \ReflectionClass($class))->getFileName());
            self::assertStringNotContainsString('AtomicTransition', $source, $class);
            self::assertStringNotContainsString('ImmutableRecordStore', $source, $class);
            self::assertStringNotContainsString('NativeAuthority::load', $source, $class);
            self::assertStringNotContainsString('random_bytes', $source, $class);
        }
    }

    public function testDocumentAndHandoffPinTheNextBoundary(): void
    {
        $document = $this->read('docs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-1-contracts-v1.md');
        $handoff = $this->read('docs/handoffs/canonical-native-effect-reconciliation-authority-provenance-remediation-batch-1-complete.md');
        foreach (['BATCH_1_COMPLETE_CANONICAL_ISSUANCE_AND_CUSTODY_CONTRACTS_DEFINED', 'caller-computed digest', 'read-only', 'Batch 2', 'Batch 7'] as $marker) {
            self::assertStringContainsStringIgnoringCase($marker, $document."\n".$handoff);
        }
        self::assertStringContainsString('No production behavior, configuration or service wiring changed', $handoff);
    }

    private function read(string $path): string
    {
        return str_replace(["\r\n", "\r"], "\n", (string) file_get_contents(dirname(__DIR__, 3).'/'.$path));
    }
}
