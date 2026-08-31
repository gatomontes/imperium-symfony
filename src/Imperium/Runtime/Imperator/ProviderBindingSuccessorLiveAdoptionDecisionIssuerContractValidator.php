<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class ProviderBindingSuccessorLiveAdoptionDecisionIssuerContractValidator
{
    public function assertPrincipal(array $principal): void
    {
        $this->sealed(
            $principal,
            ProviderBindingSuccessorLiveAdoptionDecisionPrincipalContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorLiveAdoptionDecisionPrincipalContract::SCHEMA,
            'PBL100_LIVE_ADOPTION_DECISION_PRINCIPAL_INVALID',
        );

        if (!$this->identifier($principal['principal_id'] ?? null)
            || !$this->identifier($principal['instance_id'] ?? null)
            || !$this->identifier($principal['office'] ?? null)
            || !$this->identifier($principal['seat'] ?? null)
            || !$this->identifier($principal['binding_id'] ?? null)
            || !is_int($principal['generation'] ?? null)
            || $principal['generation'] < 1
            || ProviderBindingSuccessorLiveAdoptionDecisionPrincipalContract::DECISION_SCOPE
                !== ($principal['decision_scope'] ?? null)
            || !$this->reference($principal['source_principal_activation'] ?? null)
            || !is_array($principal['operation_scope'] ?? null)
            || [] === $principal['operation_scope']
            || !$this->identifier($principal['replay_contention_root'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionDecisionPrincipalContract::STATUS
                !== ($principal['status'] ?? null)
            || false !== ($principal['decision_authority_held'] ?? null)
            || false !== ($principal['continuing_authority'] ?? null)
            || $this->containsSecret($principal)) {
            throw new \RuntimeException(
                'PBL100_LIVE_ADOPTION_DECISION_PRINCIPAL_INVALID',
            );
        }
    }

    public function assertIssuer(array $issuer, array $principal): void
    {
        $this->assertPrincipal($principal);
        $this->sealed(
            $issuer,
            ProviderBindingSuccessorLiveAdoptionDecisionIssuerContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorLiveAdoptionDecisionIssuerContract::SCHEMA,
            'PBL110_LIVE_ADOPTION_DECISION_ISSUER_INVALID',
        );

        $reference = $issuer['exact_principal'] ?? null;
        if (!$this->identifier($issuer['issuer_id'] ?? null)
            || !$this->reference($reference)
            || $reference['id'] !== $principal['principal_id']
            || $reference['digest'] !== $principal['record_digest']
            || $reference['schema'] !== $principal['schema']
            || $issuer['instance_id'] !== $principal['instance_id']
            || ProviderBindingSuccessorExecutionAdoptionDecisionBoundaryContract::SCHEMA
                !== ($issuer['decision_schema'] ?? null)
            || ProviderBindingSuccessorLiveAdoptionDecisionIssuerContract::PERMITTED_TRANSITION
                !== ($issuer['permitted_transition'] ?? null)
            || $issuer['decision_scope'] !== $principal['decision_scope']
            || $issuer['operation_scope'] !== $principal['operation_scope']
            || $issuer['replay_contention_root'] !== $principal['replay_contention_root']
            || true !== ($issuer['authority_empty'] ?? null)
            || false !== ($issuer['decision_production_performed'] ?? null)
            || false !== ($issuer['continuing_authority'] ?? null)
            || $this->containsSecret($issuer)) {
            throw new \RuntimeException(
                'PBL110_LIVE_ADOPTION_DECISION_ISSUER_INVALID',
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
