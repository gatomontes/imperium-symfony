<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final class GovernedProviderExecutionSuccessorAdmissionV3ContractValidator
{
    /** Shape check only: effective admission additionally requires a native combined commit. */
    public function assertResult(array $result): void
    {
        $plain = $result; unset($plain['record_digest']);
        if (($result['record_digest'] ?? null) !== hash('sha256', CanonicalJson::encode($plain))
            || ($result['status'] ?? null) !== GovernedProviderExecutionSuccessorAdmissionV3Contract::RESULT_STATUS
            || true !== ($result['execution_admitted'] ?? null) || true !== ($result['live_adoption_performed'] ?? null)) {
            throw new \RuntimeException('PBR420_SUCCESSOR_ADMISSION_V3_RESULT_INVALID');
        }
        $boundary = $plain;
        $boundary['status'] = GovernedProviderExecutionSuccessorAdmissionV3Contract::STATUS;
        $boundary['execution_admitted'] = false; $boundary['live_adoption_performed'] = false;
        $boundary['record_digest'] = hash('sha256', CanonicalJson::encode($boundary));
        $this->assert($boundary);
    }

    public function assert(array $admission): void
    {
        $digest = $admission['record_digest'] ?? null;
        $plain = $admission;
        unset($plain['record_digest']);

        if (GovernedProviderExecutionSuccessorAdmissionV3Contract::REQUIRED_FIELDS
                !== array_keys($admission)
            || GovernedProviderExecutionSuccessorAdmissionV3Contract::SCHEMA
                !== ($admission['schema'] ?? null)
            || true !== ($admission['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || !$this->identifier($admission['admission_boundary_id'] ?? null)
            || !$this->identifier($admission['instance_id'] ?? null)
            || !$this->reference($admission['completed_successor'] ?? null)
            || !$this->reference($admission['atomic_creation_winner'] ?? null)
            || !$this->reference($admission['adoption_target'] ?? null)
            || !$this->reference($admission['executor_principal'] ?? null)
            || !$this->reference($admission['execution_boundary'] ?? null)
            || !is_array($admission['operation_scope'] ?? null)
            || [] === $admission['operation_scope']
            || !$this->identifier($admission['replay_contention_root'] ?? null)
            || !$this->invariants($admission)
            || $this->containsSecret($admission)) {
            throw new \RuntimeException('PBR400_SUCCESSOR_ADMISSION_V3_BOUNDARY_INVALID');
        }
    }

    public function assertJoins(
        array $admission,
        array $completedSuccessor,
        array $atomicWinner,
        array $adoptionTarget,
    ): void {
        $this->assert($admission);

        if (!$this->matches(
            $admission['completed_successor'],
            $completedSuccessor,
            'successor_id',
        )
            || !$this->matches(
                $admission['atomic_creation_winner'],
                $atomicWinner,
                'winner_boundary_id',
            )
            || !$this->matches(
                $admission['adoption_target'],
                $adoptionTarget,
                'adoption_target_id',
            )
            || $admission['instance_id'] !== $completedSuccessor['instance_id']
            || $admission['operation_scope'] !== $completedSuccessor['operation_scope']
            || $admission['replay_contention_root']
                !== $completedSuccessor['replay_contention_root']
            || $admission['replay_contention_root']
                !== $atomicWinner['replay_contention_root']
            || $admission['replay_contention_root']
                !== $adoptionTarget['replay_contention_root']) {
            throw new \RuntimeException('PBR410_SUCCESSOR_ADMISSION_V3_JOIN_INVALID');
        }
    }

    private function invariants(array $record): bool
    {
        foreach (GovernedProviderExecutionSuccessorAdmissionV3Contract::INVARIANTS as $field => $value) {
            if (($record[$field] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    private function matches(array $reference, array $record, string $idField): bool
    {
        return $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function reference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema'] === array_keys($value)
            && $this->identifier($value['id'] ?? null)
            && is_string($value['digest'] ?? null)
            && (bool) preg_match('/^[a-f0-9]{64}$/', $value['digest'])
            && $this->identifier($value['schema'] ?? null);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }

    private function containsSecret(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $item) {
            if (is_string($key) && (bool) preg_match(
                '/(?:credential_(?:bytes|reference|secret|token)|capability_(?:identity|bytes|token)|api[_-]?key|access[_-]?token|authentication_material|environment[_-]?variable|callback_identity|object_identity)/i',
                $key,
            )) {
                return true;
            }
            if ($this->containsSecret($item)) {
                return true;
            }
        }

        return false;
    }
}
