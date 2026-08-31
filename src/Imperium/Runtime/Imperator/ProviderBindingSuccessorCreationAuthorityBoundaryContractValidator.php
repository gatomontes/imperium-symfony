<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderBindingSuccessorCreationAuthorityDurableCustodyBoundaryContract as Custody;

final class ProviderBindingSuccessorCreationAuthorityBoundaryContractValidator
{
    public function assertIssuanceBoundary(array $issuance): void
    {
        $this->sealed(
            $issuance,
            ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract::SCHEMA,
            'PBR200_SUCCESSOR_AUTHORITY_ISSUANCE_BOUNDARY_INVALID',
        );

        if (!$this->identifier($issuance['issuance_boundary_id'] ?? null)
            || !$this->identifier($issuance['instance_id'] ?? null)
            || !$this->reference($issuance['exact_principal'] ?? null)
            || !$this->reference($issuance['decision_issuer'] ?? null)
            || ProviderBindingSuccessorProductionDecisionV2Contract::SCHEMA
                !== ($issuance['decision_schema'] ?? null)
            || ProviderBindingSuccessorCreationAuthorityV2Contract::SCHEMA
                !== ($issuance['authority_schema'] ?? null)
            || ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract::PERMITTED_TRANSITION
                !== ($issuance['permitted_transition'] ?? null)
            || !$this->identifier($issuance['replay_contention_root'] ?? null)
            || !$this->reference($issuance['custody_target'] ?? null)
            || true !== ($issuance['authority_single_use'] ?? null)
            || false !== ($issuance['authority_exercisable'] ?? null)
            || false !== ($issuance['authority_issued'] ?? null)
            || false !== ($issuance['continuing_authority'] ?? null)
            || ProviderBindingSuccessorCreationAuthorityIssuanceBoundaryContract::STATUS
                !== ($issuance['status'] ?? null)
            || $this->containsSecret($issuance)) {
            throw new \RuntimeException('PBR200_SUCCESSOR_AUTHORITY_ISSUANCE_BOUNDARY_INVALID');
        }
    }

    public function assertCustodyBoundary(array $custody): void
    {
        $this->sealed(
            $custody,
            Custody::REQUIRED_FIELDS,
            Custody::SCHEMA,
            'PBR210_SUCCESSOR_AUTHORITY_CUSTODY_BOUNDARY_INVALID',
        );

        $consumer = $custody['authorized_consumer'] ?? null;
        if (!$this->identifier($custody['custody_boundary_id'] ?? null)
            || !$this->identifier($custody['instance_id'] ?? null)
            || ProviderBindingSuccessorCreationAuthorityV2Contract::SCHEMA
                !== ($custody['authority_schema'] ?? null)
            || 'exact_replay_contention_root' !== ($custody['custody_key_kind'] ?? null)
            || !$this->identifier($custody['replay_contention_root'] ?? null)
            || !is_array($consumer)
            || Custody::REQUIRED_CONSUMER_FIELDS !== array_keys($consumer)
            || !$this->identifier($consumer['service'] ?? null)
            || ProviderBindingSuccessorCreationAuthorityV2Contract::PERMITTED_TRANSITION
                !== ($consumer['transition'] ?? null)
            || true !== ($consumer['same_root_lock_required'] ?? null)
            || true !== ($custody['single_authority'] ?? null)
            || false !== ($custody['authority_present'] ?? null)
            || false !== ($custody['authority_consumed'] ?? null)
            || false !== ($custody['secret_material_persisted'] ?? null)
            || false !== ($custody['process_local_identity_persisted'] ?? null)
            || false !== ($custody['continuing_authority'] ?? null)
            || Custody::STATUS !== ($custody['status'] ?? null)
            || $this->containsSecret($custody)) {
            throw new \RuntimeException('PBR210_SUCCESSOR_AUTHORITY_CUSTODY_BOUNDARY_INVALID');
        }
    }

    public function assertJoin(array $issuance, array $custody): void
    {
        $this->assertIssuanceBoundary($issuance);
        $this->assertCustodyBoundary($custody);

        if ($issuance['instance_id'] !== $custody['instance_id']
            || $issuance['authority_schema'] !== $custody['authority_schema']
            || $issuance['replay_contention_root'] !== $custody['replay_contention_root']
            || $issuance['custody_target']['id'] !== $custody['custody_boundary_id']
            || $issuance['custody_target']['digest'] !== $custody['record_digest']
            || $issuance['custody_target']['schema'] !== $custody['schema']) {
            throw new \RuntimeException('PBR220_SUCCESSOR_AUTHORITY_BOUNDARY_JOIN_INVALID');
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
