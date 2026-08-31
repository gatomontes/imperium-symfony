<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalInputContract;
use App\Imperium\Runtime\LaCortine\ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract;
use PHPUnit\Framework\TestCase;

final class ProviderEffectPrincipalBindingActivationResumptionBatch1Test extends TestCase
{
    public function testCanonicalResolutionAdmissionNamesEveryImmutableJoinExactly(): void
    {
        self::assertSame(
            'imperium.la-cortine.provider-executor-principal-activation-canonical-resolution-admission/v1',
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::SCHEMA,
        );
        self::assertSame([
            'schema',
            'resolution_admission_id',
            'instance_id',
            'provenance_production',
            'production_decision',
            'principal_attestation',
            'provider_assurance_admission',
            'execution_boundary',
            'activation_target',
            'activation_authority',
            'replay_contention_root',
            'admitted_at',
            'exact_replay_only',
            'changed_evidence_conflicts',
            'resolution_required',
            'activation_performed',
            'authority_consumed',
            'continuing_authority',
            'sealed',
            'record_digest',
        ], ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_FIELDS);
        self::assertSame(
            ['id', 'digest', 'schema'],
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REFERENCE_FIELDS,
        );
        self::assertSame([
            'principal_id',
            'binding_id',
            'generation',
            'process_boundary_id',
            'provider_id',
            'operation',
        ], ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_TARGET_FIELDS);
    }

    public function testAuthorityAndReplayRootAreSingleExactSharedContracts(): void
    {
        $authorityFields = [
            'authority_id',
            'decision_digest',
            'target_attestation_digest',
            'effective_at',
            'expires_at',
            'revocation_reference',
            'authority_single_use',
            'authority_exercisable',
            'consumed',
            'continuing_authority',
        ];
        $rootFields = [
            'root_id',
            'instance_id',
            'principal_id',
            'principal_generation',
            'process_boundary_id',
            'production_id',
            'decision_id',
            'authority_id',
        ];

        self::assertSame(
            $authorityFields,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        );
        self::assertSame(
            $authorityFields,
            ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        );
        self::assertSame(
            $rootFields,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS,
        );
        self::assertSame(
            $rootFields,
            ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS,
        );
        self::assertSame(
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_TARGET_FIELDS,
            ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_ACTIVATION_TARGET_FIELDS,
        );
    }

    public function testCanonicalActivationInputCannotDropAdmittedEvidenceOrIdentity(): void
    {
        self::assertSame(
            'imperium.la-cortine.provider-executor-principal-activation-canonical-input/v1',
            ProviderExecutorPrincipalActivationCanonicalInputContract::SCHEMA,
        );
        self::assertSame([
            'schema',
            'input_id',
            'instance_id',
            'resolution_admission',
            'provenance_production',
            'production_decision',
            'principal_attestation',
            'provider_assurance_admission',
            'execution_boundary',
            'activation_target',
            'activation_authority',
            'replay_contention_root',
            'exact_replay_only',
            'changed_evidence_conflicts',
            'sealed',
            'record_digest',
        ], ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_FIELDS);
        self::assertSame(
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REFERENCE_FIELDS,
            ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_REFERENCE_FIELDS,
        );
    }

    public function testBothContractsRemainAuthorityEmpty(): void
    {
        self::assertNotContains(
            true,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::NON_AUTHORITIES,
        );
        self::assertNotContains(
            true,
            ProviderExecutorPrincipalActivationCanonicalInputContract::NON_AUTHORITIES,
        );

        foreach ([
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::NON_AUTHORITIES,
            ProviderExecutorPrincipalActivationCanonicalInputContract::NON_AUTHORITIES,
        ] as $nonAuthorities) {
            self::assertArrayHasKey('consumes_activation_authority', $nonAuthorities);
            self::assertArrayHasKey('activates_principal', $nonAuthorities);
            self::assertArrayHasKey('activates_provider_binding', $nonAuthorities);
            self::assertArrayHasKey('handles_credential_or_capability', $nonAuthorities);
            self::assertArrayHasKey('invokes_provider', $nonAuthorities);
            self::assertArrayHasKey('starts_external_io', $nonAuthorities);
            self::assertArrayHasKey('opens_iron_gate', $nonAuthorities);
            self::assertArrayHasKey('opens_lazaretto', $nonAuthorities);
        }
    }

    public function testDocumentationPreservesTheClosedRuntimePerimeterAndNextBatch(): void
    {
        $document = file_get_contents(
            dirname(__DIR__, 3).'/docs/provider-effect-principal-binding-activation-resumption-batch-1-contracts.md',
        );
        $handoff = file_get_contents(
            dirname(__DIR__, 3).'/docs/handoffs/provider-effect-principal-binding-activation-resumption-batch-1-complete.md',
        );

        self::assertIsString($document);
        self::assertIsString($handoff);
        self::assertStringContainsString(
            'RESUMPTION_BATCH_1_AUTHORITY_EMPTY_CANONICAL_RESOLUTION_AND_ACTIVATION_INPUT_CONTRACTS_COMPLETE',
            $document,
        );
        self::assertStringContainsString('do not resolve live custody', $document);
        self::assertStringContainsString('issue or consume authority', $document);
        self::assertStringContainsString('Iron Gate and Lazaretto remain closed', $document);
        self::assertStringContainsString('Only Provider Effect Principal and Binding Activation Resumption Batch 2', $handoff);
        self::assertStringContainsString('pure validators', $handoff);
        self::assertStringContainsString('caller-supplied offline fixture stores', $handoff);
        self::assertStringContainsString('approximately five batches', $handoff);
    }
}
