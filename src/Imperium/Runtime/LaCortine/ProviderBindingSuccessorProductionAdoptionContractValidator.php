<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorCreationAuthorityV2Contract as Authority;
use App\Imperium\Runtime\Imperator\ProviderBindingSuccessorProductionDecisionV2Contract as Decision;

final class ProviderBindingSuccessorProductionAdoptionContractValidator
{
    private ProviderBindingActivationReconciliationContractValidator $reconciliation;

    public function __construct()
    {
        $this->reconciliation = new ProviderBindingActivationReconciliationContractValidator();
    }

    public function assertDecision(
        array $decision,
        array $decisionAuthority,
        array $target,
        array $input,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->reconciliation->assertDecisionInput(
            $input, $target, $principal, $binding, $assurance, $boundary, $at,
        );
        $this->common($decision, Decision::REQUIRED_FIELDS, Decision::SCHEMA, 'PBA700_PRODUCTION_DECISION_INVALID');
        $this->sealedArtifact($decisionAuthority, 'authority_id', 'PBA701_DECISION_AUTHORITY_INVALID');

        $actor = $decision['competent_actor'] ?? null;
        $issuance = $decision['successor_creation_authority_issuance_target'] ?? null;
        if (!$this->identifier($decision['decision_id'] ?? null)
            || $decision['instance_id'] !== $target['instance_id']
            || !$this->reference($decision['source_decision_authority'] ?? null, $decisionAuthority, 'authority_id')
            || !$this->reference($decision['reconciled_target'] ?? null, $target, 'target_id')
            || !$this->reference($decision['reconciled_decision_input'] ?? null, $input, 'decision_input_id')
            || !$this->exact($actor, Decision::REQUIRED_ACTOR_FIELDS)
            || array_slice($actor, 0, 5, true) !== $input['actor']
            || 'DECIDE_EXACT_PROVIDER_BINDING_SUCCESSOR_PRODUCTION' !== $actor['decision_scope']
            || Decision::PERMITTED_TRANSITION !== ($decision['requested_transition'] ?? null)
            || !in_array($decision['disposition'] ?? null, Decision::DISPOSITIONS, true)
            || !$this->validity($decision['validity'] ?? null, $at)
            || !$this->exact($issuance, Decision::REQUIRED_ISSUANCE_TARGET_FIELDS)
            || !$this->identifier($issuance['authority_id'] ?? null)
            || Authority::SCHEMA !== ($issuance['authority_schema'] ?? null)
            || $issuance['successor_target'] !== $decision['reconciled_target']
            || Decision::PERMITTED_TRANSITION !== ($issuance['permitted_transition'] ?? null)
            || $issuance['replay_contention_root'] !== $target['replay_contention_root']
            || true !== ($issuance['authority_single_use'] ?? null)
            || false !== ($issuance['continuing_authority'] ?? null)
            || !$this->date($decision['decided_at'] ?? null)
            || new \DateTimeImmutable($decision['decided_at']) > $at
            || $this->containsSecret($decision)) {
            throw new \RuntimeException('PBA700_PRODUCTION_DECISION_INVALID');
        }
    }

    public function assertAuthority(
        array $authority,
        array $decision,
        array $decisionAuthority,
        array $target,
        array $input,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->assertDecision(
            $decision, $decisionAuthority, $target, $input, $principal, $binding,
            $assurance, $boundary, $at,
        );
        $this->common($authority, Authority::REQUIRED_FIELDS, Authority::SCHEMA, 'PBA710_CREATION_AUTHORITY_INVALID');
        $issuance = $decision['successor_creation_authority_issuance_target'];
        if ('AUTHORIZED' !== $decision['disposition']
            || $authority['authority_id'] !== $issuance['authority_id']
            || $authority['instance_id'] !== $decision['instance_id']
            || !$this->reference($authority['source_decision'] ?? null, $decision, 'decision_id')
            || ($authority['source_issuance_target'] ?? null) !== $issuance
            || ($authority['competent_actor'] ?? null) !== $decision['competent_actor']
            || ($authority['successor_target'] ?? null) !== $decision['reconciled_target']
            || Authority::PERMITTED_TRANSITION !== ($authority['permitted_transition'] ?? null)
            || ($authority['replay_contention_root'] ?? null) !== $issuance['replay_contention_root']
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || ($authority['validity'] ?? null) !== $decision['validity']
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || $this->containsSecret($authority)) {
            throw new \RuntimeException('PBA710_CREATION_AUTHORITY_INVALID');
        }
    }

    public function assertAdoptionTarget(
        array $adoption,
        array $successor,
        array $input,
        array $target,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->reconciliation->assertSuccessor(
            $successor, $input, $target, $principal, $binding, $assurance, $boundary, $at,
        );
        $this->common(
            $adoption,
            ProviderBindingSuccessorExecutionAdoptionTargetContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorExecutionAdoptionTargetContract::SCHEMA,
            'PBA720_ADOPTION_TARGET_INVALID',
        );
        $admission = $adoption['required_admission_contract'] ?? null;
        if (!$this->identifier($adoption['adoption_target_id'] ?? null)
            || $adoption['instance_id'] !== $successor['instance_id']
            || !$this->reference($adoption['completed_successor'] ?? null, $successor, 'successor_id')
            || $adoption['active_principal_activation'] !== $successor['active_principal_activation']
            || $adoption['provider_binding_descriptor'] !== $successor['provider_binding_descriptor']
            || $adoption['provider_assurance_admission'] !== $successor['provider_assurance_admission']
            || $adoption['execution_boundary'] !== $successor['execution_boundary']
            || $adoption['operation_scope'] !== $successor['operation_scope']
            || $adoption['replay_contention_root'] !== $successor['replay_contention_root']
            || !is_array($admission)
            || ['schema', 'version', 'status'] !== array_keys($admission)
            || 'imperium.la-cortine.governed-provider-execution-admission/v3' !== $admission['schema']
            || 3 !== $admission['version']
            || 'NOT_IMPLEMENTED' !== $admission['status']
            || !$this->requiredAdoptionInvariants($adoption)
            || $this->containsSecret($adoption)) {
            throw new \RuntimeException('PBA720_ADOPTION_TARGET_INVALID');
        }
    }

    private function requiredAdoptionInvariants(array $record): bool
    {
        foreach (ProviderBindingSuccessorExecutionAdoptionTargetContract::REQUIRED_INVARIANTS as $field => $value) {
            if (($record[$field] ?? null) !== $value) {
                return false;
            }
        }

        return true;
    }

    private function common(array $record, array $fields, string $schema, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function sealedArtifact(array $record, string $idField, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if (!$this->identifier($record[$idField] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || !$this->identifier($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || $this->containsSecret($record)) {
            throw new \RuntimeException($error);
        }
    }

    private function reference(mixed $reference, array $record, string $idField): bool
    {
        return $this->exact($reference, ['id', 'digest', 'schema'])
            && $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function validity(mixed $value, \DateTimeImmutable $at): bool
    {
        return $this->exact($value, Decision::REQUIRED_VALIDITY_FIELDS)
            && $this->date($value['effective_at'] ?? null)
            && $this->date($value['expires_at'] ?? null)
            && new \DateTimeImmutable($value['effective_at']) <= $at
            && $at < new \DateTimeImmutable($value['expires_at'])
            && null === ($value['revocation_reference'] ?? null);
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
