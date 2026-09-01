<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorAtomicLiveTransitionAuthorityDurableCustodyBoundaryContract as Custody;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorAtomicLiveTransitionAuthorityProcessLocalDeliveryBoundaryContract as Delivery;

final class ProviderBindingSuccessorAtomicLiveTransitionAuthorityBoundaryContractValidator
{
    public function assertIssuance(array $issuance, array $decision): void
    {
        $this->sealed(
            $decision,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionDecisionResultContract::SCHEMA,
            'PBL800_ATOMIC_TRANSITION_SOURCE_DECISION_INVALID',
        );
        $this->sealed(
            $issuance,
            ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract::SCHEMA,
            'PBL810_ATOMIC_TRANSITION_AUTHORITY_ISSUANCE_INVALID',
        );

        $target = $decision['authority_issuance_target'] ?? null;
        if ('AUTHORIZED' !== ($decision['disposition'] ?? null)
            || true !== ($decision['decision_performed'] ?? null)
            || true !== ($decision['authority_empty'] ?? null)
            || false !== ($decision['live_transition_performed'] ?? null)
            || ($issuance['source_decision'] ?? null)
                !== $this->referenceFor($decision, 'decision_id')
            || ($issuance['source_issuance_target'] ?? null) !== $target
            || ($issuance['instance_id'] ?? null) !== $decision['instance_id']
            || ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract::SCHEMA
                !== ($issuance['authority_schema'] ?? null)
            || ($issuance['replay_contention_root'] ?? null)
                !== $decision['replay_contention_root']
            || !$this->reference($issuance['custody_target'] ?? null)
            || !$this->reference($issuance['delivery_target'] ?? null)
            || true !== ($issuance['authority_single_use'] ?? null)
            || false !== ($issuance['authority_exercisable'] ?? null)
            || false !== ($issuance['authority_issued'] ?? null)
            || false !== ($issuance['continuing_authority'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionAuthorityIssuanceBoundaryContract::STATUS
                !== ($issuance['status'] ?? null)
            || !$this->identifier($issuance['issuance_boundary_id'] ?? null)
            || $this->containsSecret($issuance)) {
            throw new \RuntimeException(
                'PBL810_ATOMIC_TRANSITION_AUTHORITY_ISSUANCE_INVALID',
            );
        }
    }

    public function assertCustody(array $custody): void
    {
        $this->sealed(
            $custody,
            Custody::REQUIRED_FIELDS,
            Custody::SCHEMA,
            'PBL820_ATOMIC_TRANSITION_AUTHORITY_CUSTODY_INVALID',
        );

        $consumer = $custody['authorized_consumer'] ?? null;
        if (!$this->identifier($custody['custody_boundary_id'] ?? null)
            || !$this->identifier($custody['instance_id'] ?? null)
            || ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract::SCHEMA
                !== ($custody['authority_schema'] ?? null)
            || 'exact_replay_contention_root'
                !== ($custody['custody_key_kind'] ?? null)
            || !$this->identifier($custody['replay_contention_root'] ?? null)
            || !$this->consumer($consumer)
            || ProviderBindingSuccessorAtomicLiveTransitionAuthorityContract::PERMITTED_TRANSITION
                !== $consumer['transition']
            || Delivery::SCHEMA !== ($custody['delivery_schema'] ?? null)
            || true !== ($custody['single_authority'] ?? null)
            || false !== ($custody['authority_present'] ?? null)
            || false !== ($custody['authority_consumed'] ?? null)
            || false !== ($custody['secret_material_persisted'] ?? null)
            || false !== ($custody['process_local_identity_persisted'] ?? null)
            || false !== ($custody['continuing_authority'] ?? null)
            || Custody::STATUS !== ($custody['status'] ?? null)
            || $this->containsSecret($custody)) {
            throw new \RuntimeException(
                'PBL820_ATOMIC_TRANSITION_AUTHORITY_CUSTODY_INVALID',
            );
        }
    }

    public function assertDelivery(array $delivery, array $custody): void
    {
        $this->assertCustody($custody);
        $this->sealed(
            $delivery,
            Delivery::REQUIRED_FIELDS,
            Delivery::SCHEMA,
            'PBL830_ATOMIC_TRANSITION_AUTHORITY_DELIVERY_INVALID',
        );

        if (!$this->identifier($delivery['delivery_boundary_id'] ?? null)
            || ($delivery['instance_id'] ?? null) !== $custody['instance_id']
            || ($delivery['authority_schema'] ?? null) !== $custody['authority_schema']
            || ($delivery['custody_source'] ?? null)
                !== $this->referenceFor($custody, 'custody_boundary_id')
            || ($delivery['authorized_consumer'] ?? null)
                !== $custody['authorized_consumer']
            || ($delivery['replay_contention_root'] ?? null)
                !== $custody['replay_contention_root']
            || Delivery::DELIVERY_KIND !== ($delivery['delivery_kind'] ?? null)
            || false !== ($delivery['authority_delivered'] ?? null)
            || false !== ($delivery['process_local_identity_materialized'] ?? null)
            || false !== ($delivery['secret_material_present'] ?? null)
            || false !== ($delivery['durable_delivery_material_persisted'] ?? null)
            || false !== ($delivery['continuing_authority'] ?? null)
            || Delivery::STATUS !== ($delivery['status'] ?? null)
            || $this->containsSecret($delivery)) {
            throw new \RuntimeException(
                'PBL830_ATOMIC_TRANSITION_AUTHORITY_DELIVERY_INVALID',
            );
        }
    }

    public function assertJoin(
        array $issuance,
        array $custody,
        array $delivery,
        array $decision,
    ): void {
        $this->assertIssuance($issuance, $decision);
        $this->assertDelivery($delivery, $custody);

        if ($issuance['instance_id'] !== $custody['instance_id']
            || $issuance['authority_schema'] !== $custody['authority_schema']
            || $issuance['replay_contention_root']
                !== $custody['replay_contention_root']
            || $issuance['custody_target']
                !== $this->referenceFor($custody, 'custody_boundary_id')
            || $issuance['delivery_target']
                !== $this->referenceFor($delivery, 'delivery_boundary_id')) {
            throw new \RuntimeException(
                'PBL840_ATOMIC_TRANSITION_AUTHORITY_BOUNDARY_JOIN_INVALID',
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

    private function consumer(mixed $value): bool
    {
        return is_array($value)
            && Custody::REQUIRED_CONSUMER_FIELDS === array_keys($value)
            && $this->identifier($value['service'] ?? null)
            && is_string($value['transition'] ?? null)
            && true === ($value['same_root_lock_required'] ?? null);
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
            ) && false !== $item && null !== $item) {
                return true;
            }
            if ($this->containsSecret($item)) {
                return true;
            }
        }

        return false;
    }
}
