<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Imperator\ProviderBindingActivationReconciledDecisionInputContract as DecisionInput;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledLifecycleSuccessorContract as Successor;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledTargetContract as Target;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationStateReconciliationBatch1Test extends TestCase
{
    public function testTargetBindsTheExactImmutableSuccessorBasis(): void
    {
        self::assertSame(
            [
                'schema',
                'target_id',
                'instance_id',
                'active_principal_activation',
                'provider_binding_descriptor',
                'provider_assurance_admission',
                'execution_boundary',
                'operation_scope',
                'replay_contention_root',
                'validity',
                'original_binding_status',
                'original_binding_mutation_permitted',
                'global_bound_active_permitted',
                'exact_operation_scoped',
                'sealed',
                'record_digest',
            ],
            Target::REQUIRED_FIELDS,
        );
        self::assertSame('ACTIVE', Target::REQUIRED_INVARIANTS['active_principal_status']);
        self::assertSame('BOUND_INACTIVE', Target::REQUIRED_INVARIANTS['original_binding_status']);
        self::assertFalse(Target::REQUIRED_INVARIANTS['original_binding_mutation_permitted']);
        self::assertFalse(Target::REQUIRED_INVARIANTS['global_bound_active_permitted']);
        self::assertTrue(Target::REQUIRED_INVARIANTS['exact_operation_scoped']);
    }

    public function testDecisionInputIsAuthorityEmptyAndNotAProductionDecision(): void
    {
        self::assertSame(
            'CREATE_EXACT_OPERATION_SCOPED_PROVIDER_BINDING_SUCCESSOR',
            DecisionInput::PERMITTED_TRANSITION,
        );
        self::assertSame(['AUTHORIZED', 'REFUSED'], DecisionInput::DISPOSITIONS);
        self::assertTrue(DecisionInput::AUTHORITY_INVARIANTS['authority_single_use']);
        self::assertTrue(DecisionInput::AUTHORITY_INVARIANTS['authority_exercisable']);
        self::assertFalse(DecisionInput::AUTHORITY_INVARIANTS['consumed']);
        self::assertFalse(DecisionInput::AUTHORITY_INVARIANTS['continuing_authority']);
        foreach (DecisionInput::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
    }

    public function testSuccessorIsOperationScopedImmutableAndProviderFree(): void
    {
        self::assertContains('OPERATION_SCOPED_BINDING_ACTIVE', Successor::STATUSES);
        self::assertTrue(
            Successor::REQUIRED_INVARIANTS['operation_scoped_binding_sufficiency_established'],
        );
        self::assertFalse(Successor::REQUIRED_INVARIANTS['original_binding_mutated']);
        self::assertFalse(Successor::REQUIRED_INVARIANTS['global_bound_active_asserted']);
        self::assertFalse(Successor::RECONSTRUCTION_INVARIANTS['legacy_activation_promotable']);
        self::assertFalse(
            Successor::RECONSTRUCTION_INVARIANTS['capability_reconstruction_permitted'],
        );
        foreach (Successor::NON_AUTHORITIES as $name => $value) {
            self::assertFalse($value, $name);
        }
    }

    public function testContractsExcludeSecretsAndCapabilityMaterial(): void
    {
        $encoded = json_encode([
            Target::REQUIRED_FIELDS,
            Target::REQUIRED_OPERATION_SCOPE_FIELDS,
            DecisionInput::REQUIRED_FIELDS,
            DecisionInput::REQUIRED_AUTHORITY_FIELDS,
            Successor::REQUIRED_FIELDS,
        ], JSON_THROW_ON_ERROR);

        foreach ([
            'credential_bytes',
            'credential_reference',
            'environment_variable',
            'provider_token',
            'authentication_material',
            'capability_identity',
        ] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $encoded);
        }
    }

    public function testDocumentationAuthorizesPureValidationNext(): void
    {
        $doc = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-1-contracts.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-1-complete.md',
        );

        foreach ([
            'BATCH_1_AUTHORITY_EMPTY_IMMUTABLE_BINDING_SUCCESSOR_CONTRACTS_COMPLETE',
            'exact ACTIVE principal activation',
            'original BOUND_INACTIVE implementation descriptor',
            'without mutating the original binding',
            'no producer, validator, store, reconstructor',
            'provider binding remains BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 2',
            'pure fail-closed validators',
            'segregated immutable caller-supplied offline fixture stores',
            'may not implement a production decision or activation transition',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'approximately five batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function document(string $path): string
    {
        return (string) preg_replace(
            '/\s+/',
            ' ',
            (string) file_get_contents(dirname(__DIR__, 3).'/'.$path),
        );
    }
}
