<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationReconciledDecisionInputContract as DecisionInput;

final class ProviderBindingActivationReconciliationContractValidator
{
    public function assertTarget(
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common(
            $target,
            ProviderBindingActivationReconciledTargetContract::REQUIRED_FIELDS,
            ProviderBindingActivationReconciledTargetContract::SCHEMA,
            'PBR200_RECONCILED_TARGET_INVALID',
        );
        $this->sealedArtifact($principalActivation, 'activation_id', 'PBR201_PRINCIPAL_ACTIVATION_INVALID');
        $this->sealedArtifact($bindingDescriptor, 'binding_id', 'PBR202_BINDING_DESCRIPTOR_INVALID');
        $this->sealedArtifact($assurance, 'admission_id', 'PBR203_ASSURANCE_INVALID');
        $this->sealedArtifact($boundary, 'boundary_id', 'PBR204_BOUNDARY_INVALID');

        $scope = $target['operation_scope'] ?? null;
        $root = $target['replay_contention_root'] ?? null;
        $validity = $target['validity'] ?? null;

        if (!$this->identifier($target['target_id'] ?? null)
            || !$this->identifier($target['instance_id'] ?? null)
            || !$this->referenceMatches($target['active_principal_activation'] ?? null, $principalActivation, 'activation_id')
            || !$this->referenceMatches($target['provider_binding_descriptor'] ?? null, $bindingDescriptor, 'binding_id')
            || !$this->referenceMatches($target['provider_assurance_admission'] ?? null, $assurance, 'admission_id')
            || !$this->referenceMatches($target['execution_boundary'] ?? null, $boundary, 'boundary_id')
            || 'ACTIVE' !== ($principalActivation['status'] ?? null)
            || 'BOUND_INACTIVE' !== ($bindingDescriptor['status'] ?? null)
            || !$this->sameInstance($target['instance_id'], [$principalActivation, $bindingDescriptor, $assurance, $boundary])
            || !$this->scope($scope, $principalActivation, $bindingDescriptor)
            || !$this->root($root, $target, $scope, $principalActivation, $bindingDescriptor)
            || !$this->validity($validity, $at)
            || 'BOUND_INACTIVE' !== ($target['original_binding_status'] ?? null)
            || false !== ($target['original_binding_mutation_permitted'] ?? null)
            || false !== ($target['global_bound_active_permitted'] ?? null)
            || true !== ($target['exact_operation_scoped'] ?? null)
            || $this->containsSecretMaterial($target)) {
            throw new \RuntimeException('PBR200_RECONCILED_TARGET_INVALID');
        }
    }

    public function assertDecisionInput(
        array $input,
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->assertTarget($target, $principalActivation, $bindingDescriptor, $assurance, $boundary, $at);
        $this->common(
            $input,
            DecisionInput::REQUIRED_FIELDS,
            DecisionInput::SCHEMA,
            'PBR210_DECISION_INPUT_INVALID',
        );

        $actor = $input['actor'] ?? null;
        $basis = $input['basis'] ?? null;
        $authority = $input['activation_authority'] ?? null;
        $scope = $target['operation_scope'];

        if (!$this->identifier($input['decision_input_id'] ?? null)
            || $input['instance_id'] !== $target['instance_id']
            || !$this->exact($actor, DecisionInput::REQUIRED_ACTOR_FIELDS)
            || $actor['principal_id'] !== $scope['principal_id']
            || $actor['generation'] !== $scope['principal_generation']
            || $actor['binding_id'] !== ($bindingDescriptor['binding_id'] ?? null)
            || !$this->referenceMatches($input['successor_target'] ?? null, $target, 'target_id')
            || !$this->exact($basis, DecisionInput::REQUIRED_BASIS_FIELDS)
            || $basis['active_principal_activation'] !== $target['active_principal_activation']
            || $basis['provider_binding_descriptor'] !== $target['provider_binding_descriptor']
            || $basis['provider_assurance_admission'] !== $target['provider_assurance_admission']
            || $basis['execution_boundary'] !== $target['execution_boundary']
            || $basis['operation_scope'] !== $target['operation_scope']
            || $basis['replay_contention_root'] !== $target['replay_contention_root']
            || DecisionInput::PERMITTED_TRANSITION !== ($input['requested_transition'] ?? null)
            || !in_array($input['disposition'] ?? null, DecisionInput::DISPOSITIONS, true)
            || !$this->exact($authority, DecisionInput::REQUIRED_AUTHORITY_FIELDS)
            || !$this->identifier($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || DecisionInput::PERMITTED_TRANSITION !== ($authority['permitted_transition'] ?? null)
            || $authority['target_digest'] !== $target['record_digest']
            || $authority['effective_at'] !== $target['validity']['effective_at']
            || $authority['expires_at'] !== $target['validity']['expires_at']
            || null !== ($authority['revocation_reference'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || !$this->date($input['decided_at'] ?? null)
            || new \DateTimeImmutable($input['decided_at']) > $at
            || $this->containsSecretMaterial($input)) {
            throw new \RuntimeException('PBR210_DECISION_INPUT_INVALID');
        }
    }

    public function assertSuccessor(
        array $successor,
        array $input,
        array $target,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->assertDecisionInput(
            $input,
            $target,
            $principalActivation,
            $bindingDescriptor,
            $assurance,
            $boundary,
            $at,
        );
        $this->common(
            $successor,
            ProviderBindingActivationReconciledLifecycleSuccessorContract::REQUIRED_FIELDS,
            ProviderBindingActivationReconciledLifecycleSuccessorContract::SCHEMA,
            'PBR220_LIFECYCLE_SUCCESSOR_INVALID',
        );

        $consumed = $successor['consumed_activation_authority'] ?? null;
        $reconstruction = $successor['reconstruction'] ?? null;

        if (!$this->identifier($successor['successor_id'] ?? null)
            || $successor['instance_id'] !== $target['instance_id']
            || 'AUTHORIZED' !== $input['disposition']
            || !$this->referenceMatches($successor['source_decision'] ?? null, $input, 'decision_input_id')
            || !$this->referenceMatches($successor['successor_target'] ?? null, $target, 'target_id')
            || $successor['active_principal_activation'] !== $target['active_principal_activation']
            || $successor['provider_binding_descriptor'] !== $target['provider_binding_descriptor']
            || $successor['provider_assurance_admission'] !== $target['provider_assurance_admission']
            || $successor['execution_boundary'] !== $target['execution_boundary']
            || $successor['operation_scope'] !== $target['operation_scope']
            || $successor['replay_contention_root'] !== $target['replay_contention_root']
            || !$this->exact(
                $consumed,
                ProviderBindingActivationReconciledLifecycleSuccessorContract::REQUIRED_CONSUMED_AUTHORITY_FIELDS,
            )
            || $consumed['id'] !== $input['activation_authority']['authority_id']
            || $consumed['digest'] !== $input['record_digest']
            || $consumed['schema'] !== $input['schema']
            || !$this->date($consumed['consumed_at'] ?? null)
            || true !== ($consumed['consumed'] ?? null)
            || false !== ($consumed['continuing_authority'] ?? null)
            || 'OPERATION_SCOPED_BINDING_ACTIVE' !== ($successor['status'] ?? null)
            || $successor['validity'] !== $target['validity']
            || !$this->exact(
                $reconstruction,
                ProviderBindingActivationReconciledLifecycleSuccessorContract::REQUIRED_RECONSTRUCTION_FIELDS,
            )
            || ProviderBindingActivationReconciledLifecycleSuccessorContract::RECONSTRUCTION_INVARIANTS
                !== $reconstruction
            || !$this->requiredInvariants($successor)
            || !$this->date($successor['activated_at'] ?? null)
            || $successor['activated_at'] !== $consumed['consumed_at']
            || new \DateTimeImmutable($successor['activated_at']) > $at
            || $this->containsSecretMaterial($successor)) {
            throw new \RuntimeException('PBR220_LIFECYCLE_SUCCESSOR_INVALID');
        }
    }

    private function requiredInvariants(array $successor): bool
    {
        foreach (ProviderBindingActivationReconciledLifecycleSuccessorContract::REQUIRED_INVARIANTS as $field => $value) {
            if (($successor[$field] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    private function scope(mixed $scope, array $principal, array $binding): bool
    {
        return $this->exact($scope, ProviderBindingActivationReconciledTargetContract::REQUIRED_OPERATION_SCOPE_FIELDS)
            && $this->identifier($scope['provider_id'] ?? null)
            && $this->identifier($scope['operation'] ?? null)
            && $scope['principal_id'] === ($principal['principal_id'] ?? null)
            && $scope['principal_generation'] === ($principal['generation'] ?? null)
            && $scope['process_boundary_id'] === ($principal['process_boundary_id'] ?? null)
            && $scope['provider_id'] === ($binding['provider_id'] ?? null)
            && false === $scope['provider_substitution_permitted']
            && false === $scope['operation_substitution_permitted']
            && false === $scope['principal_generation_substitution_permitted']
            && false === $scope['binding_substitution_permitted'];
    }

    private function root(mixed $root, array $target, array $scope, array $principal, array $binding): bool
    {
        return $this->exact($root, ProviderBindingActivationReconciledTargetContract::REQUIRED_ROOT_FIELDS)
            && $this->identifier($root['root_id'] ?? null)
            && $root['instance_id'] === $target['instance_id']
            && $root['principal_activation_id'] === ($principal['activation_id'] ?? null)
            && $root['binding_id'] === ($binding['binding_id'] ?? null)
            && $root['provider_id'] === $scope['provider_id']
            && $root['operation'] === $scope['operation'];
    }

    private function validity(mixed $validity, \DateTimeImmutable $at): bool
    {
        return $this->exact($validity, ProviderBindingActivationReconciledTargetContract::REQUIRED_VALIDITY_FIELDS)
            && $this->date($validity['effective_at'] ?? null)
            && $this->date($validity['expires_at'] ?? null)
            && new \DateTimeImmutable($validity['effective_at']) <= $at
            && $at < new \DateTimeImmutable($validity['expires_at'])
            && null === ($validity['revocation_reference'] ?? null);
    }

    private function common(array $record, array $fields, string $schema, string $failure): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function sealedArtifact(array $record, string $idField, string $failure): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if (!$this->identifier($record[$idField] ?? null)
            || !$this->identifier($record['schema'] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || $this->containsSecretMaterial($record)) {
            throw new \RuntimeException($failure);
        }
    }

    private function referenceMatches(mixed $reference, array $record, string $idField): bool
    {
        return $this->exact($reference, ProviderBindingActivationReconciledTargetContract::REQUIRED_REFERENCE_FIELDS)
            && $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function sameInstance(string $instanceId, array $records): bool
    {
        foreach ($records as $record) {
            if ($instanceId !== ($record['instance_id'] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function containsSecretMaterial(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $item) {
            if (is_string($key)
                && (bool) preg_match(
                    '/(?:credential_(?:bytes|reference|secret|token)|capability_(?:identity|bytes|token)|api[_-]?key|access[_-]?token|authentication_material|environment[_-]?variable)/i',
                    $key,
                )) {
                return true;
            }
            if ($this->containsSecretMaterial($item)) {
                return true;
            }
        }

        return false;
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        return false !== $date && $date->format(DATE_ATOM) === $value;
    }
}
