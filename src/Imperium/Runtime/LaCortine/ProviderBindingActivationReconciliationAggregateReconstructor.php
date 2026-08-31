<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderBindingActivationReconciliationAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'ELIGIBLE_OFFLINE_BINDING_SUCCESSOR',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];

    private ImmutableRecordStore $records;
    private ProviderBindingActivationReconciliationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderBindingActivationReconciliationContractValidator();
    }

    public function reconstruct(
        string $rootId,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        if (!$this->identifier($rootId)) {
            return $this->result('REFUSED', [], ['ROOT_IDENTIFIER_INVALID']);
        }

        $targetRead = $this->read(
            ProviderBindingActivationReconciliationFixtureStore::TARGETS,
            $rootId,
        );
        if (null !== $targetRead['classification']) {
            return $this->result($targetRead['classification'], [], $targetRead['reasons']);
        }
        $target = $targetRead['record'];

        try {
            $this->validator->assertTarget(
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $inputRead = $this->read(
            ProviderBindingActivationReconciliationFixtureStore::DECISION_INPUTS,
            $rootId,
        );
        if (null !== $inputRead['classification']) {
            return $this->result($inputRead['classification'], [], $inputRead['reasons']);
        }
        $input = $inputRead['record'];

        try {
            $this->validator->assertDecisionInput(
                $input,
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $successorRead = $this->read(
            ProviderBindingActivationReconciliationFixtureStore::LIFECYCLE_SUCCESSORS,
            $rootId,
        );
        if (null !== $successorRead['classification']) {
            return $this->result($successorRead['classification'], [], $successorRead['reasons']);
        }
        $successor = $successorRead['record'];

        try {
            $this->validator->assertSuccessor(
                $successor,
                $input,
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        return $this->result(
            'ELIGIBLE_OFFLINE_BINDING_SUCCESSOR',
            [
                'principal_activation' => $this->reference($principalActivation, 'activation_id'),
                'binding_descriptor' => $this->reference($bindingDescriptor, 'binding_id'),
                'provider_assurance_admission' => $this->reference($assurance, 'admission_id'),
                'execution_boundary' => $this->reference($boundary, 'boundary_id'),
                'reconciled_target' => $this->reference($target, 'target_id'),
                'decision_input' => $this->reference($input, 'decision_input_id'),
                'lifecycle_successor' => $this->reference($successor, 'successor_id'),
                'operation_scope' => $successor['operation_scope'],
                'replay_contention_root' => $successor['replay_contention_root'],
                'validity' => $successor['validity'],
            ],
            [],
        );
    }

    private function read(string $directory, string $rootId): array
    {
        try {
            return [
                'record' => $this->records->read($directory, $rootId),
                'classification' => null,
                'reasons' => [],
            ];
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' === $exception->getMessage()) {
                return [
                    'record' => null,
                    'classification' => 'INCOMPLETE',
                    'reasons' => ['FIXTURE_ABSENT'],
                ];
            }

            return [
                'record' => null,
                'classification' => 'CONFLICTED',
                'reasons' => [$exception->getMessage()],
            ];
        }
    }

    private function result(string $classification, array $chain, array $reasons): array
    {
        $proof = [
            'classification' => $classification,
            'chain' => $chain,
            'reasons' => $reasons,
        ];

        return [
            ...$proof,
            'proof_digest' => hash('sha256', CanonicalJson::encode($proof)),
            'read_only' => true,
            'fixture_created' => false,
            'fixture_repaired' => false,
            'artifact_replaced' => false,
            'artifact_promoted' => false,
            'production_decision_created' => false,
            'activation_transition_performed' => false,
            'provider_binding_activated' => false,
            'activation_authority_issued' => false,
            'activation_authority_consumed' => false,
            'execution_authority_created' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authority_created' => false,
            'continuing_authority' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }
}
