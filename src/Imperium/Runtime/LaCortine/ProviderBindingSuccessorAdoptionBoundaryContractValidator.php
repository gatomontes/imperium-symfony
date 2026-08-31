<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract as Decision;

final class ProviderBindingSuccessorAdoptionBoundaryContractValidator
{
    public function assertDecision(array $decision): void
    {
        $this->sealed(
            $decision,
            Decision::REQUIRED_FIELDS,
            Decision::SCHEMA,
            'PBR500_SUCCESSOR_ADOPTION_DECISION_BOUNDARY_INVALID',
        );

        if (!$this->identifier($decision['decision_boundary_id'] ?? null)
            || !$this->identifier($decision['instance_id'] ?? null)
            || !$this->reference($decision['exact_principal'] ?? null)
            || !$this->reference($decision['completed_successor'] ?? null)
            || !$this->reference($decision['adoption_target'] ?? null)
            || !$this->reference($decision['v3_admission'] ?? null)
            || !is_array($decision['operation_scope'] ?? null)
            || [] === $decision['operation_scope']
            || !$this->identifier($decision['replay_contention_root'] ?? null)
            || !$this->invariants($decision, Decision::INVARIANTS)
            || $this->containsSecret($decision)) {
            throw new \RuntimeException('PBR500_SUCCESSOR_ADOPTION_DECISION_BOUNDARY_INVALID');
        }
    }

    public function assertJoin(array $join): void
    {
        $this->sealed(
            $join,
            ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract::SCHEMA,
            'PBR510_SUCCESSOR_TO_V3_JOIN_BOUNDARY_INVALID',
        );

        if (!$this->identifier($join['join_boundary_id'] ?? null)
            || !$this->identifier($join['instance_id'] ?? null)
            || !$this->reference($join['adoption_decision'] ?? null)
            || !$this->reference($join['completed_successor'] ?? null)
            || !$this->reference($join['adoption_target'] ?? null)
            || !$this->reference($join['v3_admission'] ?? null)
            || !is_array($join['operation_scope'] ?? null)
            || [] === $join['operation_scope']
            || !$this->identifier($join['replay_contention_root'] ?? null)
            || !$this->invariants(
                $join,
                ProviderBindingSuccessorToV3AdoptionJoinBoundaryContract::INVARIANTS,
            )
            || $this->containsSecret($join)) {
            throw new \RuntimeException('PBR510_SUCCESSOR_TO_V3_JOIN_BOUNDARY_INVALID');
        }
    }

    public function assertExactChain(
        array $decision,
        array $join,
        array $successor,
        array $adoptionTarget,
        array $v3Admission,
    ): void {
        $this->assertDecision($decision);
        $this->assertJoin($join);

        if (!$this->matches($decision['completed_successor'], $successor, 'successor_id')
            || !$this->matches($decision['adoption_target'], $adoptionTarget, 'adoption_target_id')
            || !$this->matches($decision['v3_admission'], $v3Admission, 'admission_boundary_id')
            || !$this->matches($join['adoption_decision'], $decision, 'decision_boundary_id')
            || $join['completed_successor'] !== $decision['completed_successor']
            || $join['adoption_target'] !== $decision['adoption_target']
            || $join['v3_admission'] !== $decision['v3_admission']
            || $join['instance_id'] !== $decision['instance_id']
            || $join['operation_scope'] !== $decision['operation_scope']
            || $join['replay_contention_root'] !== $decision['replay_contention_root']
            || $decision['replay_contention_root'] !== $successor['replay_contention_root']
            || $decision['replay_contention_root'] !== $adoptionTarget['replay_contention_root']
            || $decision['replay_contention_root'] !== $v3Admission['replay_contention_root']
            || GovernedProviderExecutionSuccessorAdmissionV3Contract::STATUS
                !== ($v3Admission['status'] ?? null)
            || false !== ($v3Admission['execution_admitted'] ?? null)
            || false !== ($v3Admission['live_adoption_performed'] ?? null)) {
            throw new \RuntimeException('PBR520_SUCCESSOR_ADOPTION_CHAIN_INVALID');
        }
    }

    private function sealed(array $record, array $fields, string $schema, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);

        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function invariants(array $record, array $invariants): bool
    {
        foreach ($invariants as $field => $value) {
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
