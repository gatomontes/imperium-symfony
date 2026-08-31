<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationReconciledDecisionInputContract as DecisionInput;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledLifecycleSuccessorContract as Successor;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciledTargetContract as Target;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationContractValidator;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationFixtureStore;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationStateReconciliationBatch2Test extends TestCase
{
    public function testExactCallerSuppliedChainValidatesWithoutRuntimeAuthority(): void
    {
        $fixture = $this->fixture();
        $validator = new ProviderBindingActivationReconciliationContractValidator();

        $validator->assertTarget(...$this->targetArguments($fixture));
        $validator->assertDecisionInput(...$this->decisionArguments($fixture));
        $validator->assertSuccessor(...$this->successorArguments($fixture));

        self::assertSame('BOUND_INACTIVE', $fixture['binding']['status']);
        self::assertFalse($fixture['successor']['provider_invoked']);
        self::assertFalse($fixture['successor']['external_io_started']);
        self::assertFalse($fixture['successor']['provider_effect_started']);
        self::assertFalse($fixture['successor']['continuing_authority']);
    }

    public function testExpiryRevocationAndSubstitutionFailClosed(): void
    {
        $validator = new ProviderBindingActivationReconciliationContractValidator();

        $expired = $this->fixture();
        $expired['target']['validity']['expires_at'] = '2026-08-31T00:30:00+00:00';
        $expired['target'] = $this->seal($expired['target']);
        $this->expectFailure(
            'PBR200_RECONCILED_TARGET_INVALID',
            fn () => $validator->assertTarget(...$this->targetArguments($expired)),
        );

        $revoked = $this->fixture();
        $revoked['target']['validity']['revocation_reference'] = [
            'id' => 'revocation.1',
            'digest' => str_repeat('a', 64),
            'schema' => 'imperium.revocation/v1',
        ];
        $revoked['target'] = $this->seal($revoked['target']);
        $this->expectFailure(
            'PBR200_RECONCILED_TARGET_INVALID',
            fn () => $validator->assertTarget(...$this->targetArguments($revoked)),
        );

        $substituted = $this->fixture();
        $substituted['target']['operation_scope']['provider_substitution_permitted'] = true;
        $substituted['target'] = $this->seal($substituted['target']);
        $this->expectFailure(
            'PBR200_RECONCILED_TARGET_INVALID',
            fn () => $validator->assertTarget(...$this->targetArguments($substituted)),
        );
    }

    public function testReferenceDriftAndSecretMaterialFailClosed(): void
    {
        $validator = new ProviderBindingActivationReconciliationContractValidator();

        $drift = $this->fixture();
        $drift['input']['basis']['operation_scope']['operation'] = 'email.replace';
        $drift['input'] = $this->seal($drift['input']);
        $this->expectFailure(
            'PBR210_DECISION_INPUT_INVALID',
            fn () => $validator->assertDecisionInput(...$this->decisionArguments($drift)),
        );

        $secret = $this->fixture();
        $secret['principal']['credential_reference'] = 'env://forbidden';
        $secret['principal'] = $this->seal($secret['principal']);
        $secret['target']['active_principal_activation'] = $this->reference(
            $secret['principal'],
            'activation_id',
        );
        $secret['target'] = $this->seal($secret['target']);
        $this->expectFailure(
            'PBR201_PRINCIPAL_ACTIVATION_INVALID',
            fn () => $validator->assertTarget(...$this->targetArguments($secret)),
        );
    }

    public function testSegregatedImmutableStoreConvergesOnReplayAndConflictsOnChange(): void
    {
        $fixture = $this->fixture();
        $root = sys_get_temp_dir().'/imperium-pbr-batch2-'.bin2hex(random_bytes(8));
        mkdir($root, 0770, true);

        try {
            $store = new ProviderBindingActivationReconciliationFixtureStore($root);
            $first = $store->putTarget(...$this->targetArguments($fixture));
            $replay = $store->putTarget(...$this->targetArguments($fixture));
            self::assertSame($first, $replay);

            $changed = $fixture;
            $changed['target']['validity']['expires_at'] = '2026-09-01T02:00:00+00:00';
            $changed['target'] = $this->seal($changed['target']);

            $this->expectFailure(
                'PST111_IMMUTABLE_RECORD_CONFLICT',
                fn () => $store->putTarget(...$this->targetArguments($changed)),
            );
            self::assertNotSame(
                ProviderBindingActivationReconciliationFixtureStore::TARGETS,
                ProviderBindingActivationReconciliationFixtureStore::DECISION_INPUTS,
            );
            self::assertNotSame(
                ProviderBindingActivationReconciliationFixtureStore::DECISION_INPUTS,
                ProviderBindingActivationReconciliationFixtureStore::LIFECYCLE_SUCCESSORS,
            );
        } finally {
            $this->removeTree($root);
        }
    }

    public function testDocumentationPreservesTheClosedRuntimePerimeter(): void
    {
        $doc = $this->document(
            'docs/provider-binding-activation-state-reconciliation-batch-2-validation.md',
        );
        $handoff = $this->document(
            'docs/handoffs/provider-binding-activation-state-reconciliation-batch-2-complete.md',
        );

        foreach ([
            'BATCH_2_FAIL_CLOSED_VALIDATORS_AND_IMMUTABLE_FIXTURE_STORES_COMPLETE',
            'segregated immutable caller-supplied offline fixture stores',
            'Exact replay converges',
            'changed evidence for the same identity conflicts',
            'provider binding remains BOUND_INACTIVE',
            'UNKNOWN_REPLAY_PROHIBITED remains binding',
        ] as $finding) {
            self::assertNotFalse(stripos($doc, $finding), $finding);
        }

        foreach ([
            'Only Provider Binding Activation State Reconciliation Batch 3',
            'disposable-root offline interruption',
            'may not activate a provider binding',
            'may not issue or consume authority',
            'may not handle or resolve a credential or capability',
            'may not invoke a provider',
            'may not perform external I/O',
            'approximately four batches',
        ] as $boundary) {
            self::assertNotFalse(stripos($handoff, $boundary), $boundary);
        }
    }

    private function fixture(): array
    {
        $principal = $this->seal([
            'schema' => 'imperium.provider-executor-principal-activation/v1',
            'activation_id' => 'principal-activation.1',
            'instance_id' => 'instance.1',
            'principal_id' => 'principal.1',
            'generation' => 3,
            'process_boundary_id' => 'process-boundary.1',
            'status' => 'ACTIVE',
            'sealed' => true,
        ]);
        $binding = $this->seal([
            'schema' => 'imperium.provider-binding-descriptor/v1',
            'binding_id' => 'binding.1',
            'instance_id' => 'instance.1',
            'provider_id' => 'agentmail',
            'status' => 'BOUND_INACTIVE',
            'sealed' => true,
        ]);
        $assurance = $this->seal([
            'schema' => 'imperium.provider-assurance-admission/v1',
            'admission_id' => 'assurance.1',
            'instance_id' => 'instance.1',
            'sealed' => true,
        ]);
        $boundary = $this->seal([
            'schema' => 'imperium.provider-execution-boundary/v1',
            'boundary_id' => 'boundary.1',
            'instance_id' => 'instance.1',
            'sealed' => true,
        ]);
        $scope = [
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
            'principal_id' => 'principal.1',
            'principal_generation' => 3,
            'process_boundary_id' => 'process-boundary.1',
            'provider_substitution_permitted' => false,
            'operation_substitution_permitted' => false,
            'principal_generation_substitution_permitted' => false,
            'binding_substitution_permitted' => false,
        ];
        $root = [
            'root_id' => 'binding-reconciliation-root.1',
            'instance_id' => 'instance.1',
            'principal_activation_id' => 'principal-activation.1',
            'binding_id' => 'binding.1',
            'provider_id' => 'agentmail',
            'operation' => 'email.send',
        ];
        $validity = [
            'effective_at' => '2026-08-31T00:00:00+00:00',
            'expires_at' => '2026-09-01T00:00:00+00:00',
            'revocation_reference' => null,
        ];
        $target = $this->seal([
            'schema' => Target::SCHEMA,
            'target_id' => 'reconciled-target.1',
            'instance_id' => 'instance.1',
            'active_principal_activation' => $this->reference($principal, 'activation_id'),
            'provider_binding_descriptor' => $this->reference($binding, 'binding_id'),
            'provider_assurance_admission' => $this->reference($assurance, 'admission_id'),
            'execution_boundary' => $this->reference($boundary, 'boundary_id'),
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'validity' => $validity,
            'original_binding_status' => 'BOUND_INACTIVE',
            'original_binding_mutation_permitted' => false,
            'global_bound_active_permitted' => false,
            'exact_operation_scoped' => true,
            'sealed' => true,
        ]);
        $authority = [
            'authority_id' => 'binding-successor-authority.1',
            'authority_single_use' => true,
            'authority_exercisable' => true,
            'permitted_transition' => DecisionInput::PERMITTED_TRANSITION,
            'target_digest' => $target['record_digest'],
            'effective_at' => $validity['effective_at'],
            'expires_at' => $validity['expires_at'],
            'revocation_reference' => null,
            'consumed' => false,
            'continuing_authority' => false,
        ];
        $input = $this->seal([
            'schema' => DecisionInput::SCHEMA,
            'decision_input_id' => 'binding-successor-decision-input.1',
            'instance_id' => 'instance.1',
            'actor' => [
                'principal_id' => 'principal.1',
                'office' => 'Imperator',
                'seat' => 'active',
                'binding_id' => 'binding.1',
                'generation' => 3,
            ],
            'successor_target' => $this->reference($target, 'target_id'),
            'basis' => [
                'active_principal_activation' => $target['active_principal_activation'],
                'provider_binding_descriptor' => $target['provider_binding_descriptor'],
                'provider_assurance_admission' => $target['provider_assurance_admission'],
                'execution_boundary' => $target['execution_boundary'],
                'operation_scope' => $scope,
                'replay_contention_root' => $root,
            ],
            'requested_transition' => DecisionInput::PERMITTED_TRANSITION,
            'disposition' => 'AUTHORIZED',
            'activation_authority' => $authority,
            'limitations' => ['offline_fixture_only'],
            'decided_at' => '2026-08-31T00:15:00+00:00',
            'sealed' => true,
        ]);
        $successor = $this->seal([
            'schema' => Successor::SCHEMA,
            'successor_id' => 'binding-lifecycle-successor.1',
            'instance_id' => 'instance.1',
            'source_decision' => $this->reference($input, 'decision_input_id'),
            'successor_target' => $this->reference($target, 'target_id'),
            'active_principal_activation' => $target['active_principal_activation'],
            'provider_binding_descriptor' => $target['provider_binding_descriptor'],
            'provider_assurance_admission' => $target['provider_assurance_admission'],
            'execution_boundary' => $target['execution_boundary'],
            'operation_scope' => $scope,
            'replay_contention_root' => $root,
            'consumed_activation_authority' => [
                'id' => $authority['authority_id'],
                'digest' => $input['record_digest'],
                'schema' => $input['schema'],
                'consumed_at' => '2026-08-31T00:30:00+00:00',
                'consumed' => true,
                'continuing_authority' => false,
            ],
            'status' => 'OPERATION_SCOPED_BINDING_ACTIVE',
            'validity' => $validity,
            'reconstruction' => Successor::RECONSTRUCTION_INVARIANTS,
            'operation_scoped_binding_sufficiency_established' => true,
            'original_binding_mutated' => false,
            'global_bound_active_asserted' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authority_created' => false,
            'continuing_authority' => false,
            'activated_at' => '2026-08-31T00:30:00+00:00',
            'sealed' => true,
        ]);

        return compact('principal', 'binding', 'assurance', 'boundary', 'target', 'input', 'successor');
    }

    private function targetArguments(array $fixture): array
    {
        return [
            $fixture['target'],
            $fixture['principal'],
            $fixture['binding'],
            $fixture['assurance'],
            $fixture['boundary'],
            new \DateTimeImmutable('2026-08-31T01:00:00+00:00'),
        ];
    }

    private function decisionArguments(array $fixture): array
    {
        return [
            $fixture['input'],
            ...$this->targetArguments($fixture),
        ];
    }

    private function successorArguments(array $fixture): array
    {
        return [
            $fixture['successor'],
            $fixture['input'],
            ...$this->targetArguments($fixture),
        ];
    }

    private function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function expectFailure(string $message, callable $callable): void
    {
        try {
            $callable();
            self::fail('Expected '.$message);
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString($message, $exception->getMessage());
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

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
