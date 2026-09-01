<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class ProviderBindingSuccessorAtomicLiveTransitionDecisionContractValidator
{
    public function assertInput(array $input): void
    {
        $this->sealed(
            $input,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract::SCHEMA,
            'PBL700_ATOMIC_TRANSITION_DECISION_INPUT_INVALID',
        );

        if (!$this->identifier($input['input_id'] ?? null)
            || !$this->identifier($input['instance_id'] ?? null)
            || !$this->reference($input['exact_principal'] ?? null)
            || !$this->reference($input['source_binding'] ?? null)
            || !$this->reference($input['successor_binding_target'] ?? null)
            || !$this->reference($input['adoption_decision'] ?? null)
            || !$this->reference($input['v3_admission'] ?? null)
            || !$this->reference($input['adoption_join'] ?? null)
            || !is_array($input['operation_scope'] ?? null)
            || [] === $input['operation_scope']
            || !$this->identifier($input['replay_contention_root'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract::DECISION_SCOPE
                !== ($input['decision_scope'] ?? null)
            || true !== ($input['exact_combined_transition_required'] ?? null)
            || true !== ($input['authority_empty'] ?? null)
            || false !== ($input['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionPrincipalInputContract::STATUS
                !== ($input['status'] ?? null)
            || $this->containsSecret($input)) {
            throw new \RuntimeException(
                'PBL700_ATOMIC_TRANSITION_DECISION_INPUT_INVALID',
            );
        }
    }

    public function assertProducer(array $producer, array $input): void
    {
        $this->assertInput($input);
        $this->sealed(
            $producer,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract::SCHEMA,
            'PBL710_ATOMIC_TRANSITION_DECISION_PRODUCER_INVALID',
        );

        $inputReference = $producer['principal_input'] ?? null;
        if (!$this->identifier($producer['producer_id'] ?? null)
            || !$this->reference($inputReference)
            || $inputReference !== $this->referenceFor($input, 'input_id')
            || $producer['instance_id'] !== $input['instance_id']
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::SCHEMA
                !== ($producer['decision_result_schema'] ?? null)
            || $producer['decision_scope'] !== $input['decision_scope']
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract::PERMITTED_DISPOSITIONS
                !== ($producer['permitted_dispositions'] ?? null)
            || $producer['operation_scope'] !== $input['operation_scope']
            || $producer['replay_contention_root'] !== $input['replay_contention_root']
            || true !== ($producer['authority_empty'] ?? null)
            || false !== ($producer['decision_production_performed'] ?? null)
            || false !== ($producer['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionProducerContract::STATUS
                !== ($producer['status'] ?? null)
            || $this->containsSecret($producer)) {
            throw new \RuntimeException(
                'PBL710_ATOMIC_TRANSITION_DECISION_PRODUCER_INVALID',
            );
        }
    }

    public function assertResult(
        array $result,
        array $producer,
        array $input,
    ): void {
        $this->assertProducer($producer, $input);
        $this->sealed(
            $result,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::SCHEMA,
            'PBL720_ATOMIC_TRANSITION_DECISION_RESULT_INVALID',
        );

        $target = $result['authority_issuance_target'] ?? null;
        if (!$this->identifier($result['decision_id'] ?? null)
            || $result['instance_id'] !== $input['instance_id']
            || ($result['producer'] ?? null)
                !== $this->referenceFor($producer, 'producer_id')
            || ($result['principal_input'] ?? null)
                !== $this->referenceFor($input, 'input_id')
            || ($result['exact_principal'] ?? null) !== $input['exact_principal']
            || ($result['source_binding'] ?? null) !== $input['source_binding']
            || ($result['successor_binding_target'] ?? null)
                !== $input['successor_binding_target']
            || ($result['adoption_decision'] ?? null)
                !== $input['adoption_decision']
            || ($result['v3_admission'] ?? null) !== $input['v3_admission']
            || ($result['adoption_join'] ?? null) !== $input['adoption_join']
            || !is_array($target)
            || ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::REQUIRED_ISSUANCE_TARGET_FIELDS
                !== array_keys($target)
            || !$this->identifier($target['authority_id'] ?? null)
            || !$this->identifier($target['authority_schema'] ?? null)
            || !$this->identifier($target['consumer_service'] ?? null)
            || !$this->identifier($target['permitted_transition'] ?? null)
            || $target['replay_contention_root'] !== $input['replay_contention_root']
            || true !== ($target['single_use'] ?? null)
            || $result['operation_scope'] !== $input['operation_scope']
            || $result['replay_contention_root'] !== $input['replay_contention_root']
            || $result['decision_scope'] !== $input['decision_scope']
            || !in_array(
                $result['disposition'] ?? null,
                ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::PERMITTED_DISPOSITIONS,
                true,
            )
            || true !== ($result['decision_performed'] ?? null)
            || true !== ($result['authority_empty'] ?? null)
            || false !== ($result['live_transition_performed'] ?? null)
            || false !== ($result['continuing_authority'] ?? null)
            || $this->containsSecret($result)) {
            throw new \RuntimeException(
                'PBL720_ATOMIC_TRANSITION_DECISION_RESULT_INVALID',
            );
        }
    }

    private function sealed(
        array $record,
        array $fields,
        string $schema,
        string $error,
    ): void {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);

        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !is_string($digest)
            || !preg_match('/^[a-f0-9]{64}$/', $digest)
            || !hash_equals(
                $digest,
                hash('sha256', CanonicalJson::encode($plain)),
            )) {
            throw new \RuntimeException($error);
        }
    }

    private function referenceFor(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
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
                '/(?:credential|capability|api[_-]?key|access[_-]?token|authentication_material|environment[_-]?variable|callback_identity|object_identity)/i',
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
