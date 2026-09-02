<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class PrincipalActivationDecisionAuthorityProvenanceBatch5BValidator
{
    public function assertSuccessorPrincipal(array $principal, array $source, array $transition): void
    {
        $this->common($principal, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionV3Contract::SCHEMA, 'PAD5B00_SUCCESSOR_PRINCIPAL_INVALID');
        $this->common($source, ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, 'PAD5B01_SOURCE_PRINCIPAL_INVALID');
        $this->common($transition, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::REQUIRED_FIELDS, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::SCHEMA, 'PAD5B02_SCOPE_SUCCESSOR_INVALID');

        $sourceReference = $transition['source_principal'] ?? null;
        $successorReference = $transition['successor_principal'] ?? null;
        $scope = $principal['authority_scope'] ?? null;
        $sourceScope = $source['authority_scope'] ?? null;
        $lifecycle = $principal['lifecycle'] ?? null;

        if (!$this->principalReference($sourceReference)
            || !$this->principalReference($successorReference)
            || !$this->matchesPrincipal($sourceReference, $source)
            || !$this->matchesPrincipal($successorReference, $principal)
            || ($source['instance_id'] ?? null) !== ($principal['instance_id'] ?? null)
            || ($source['principal_id'] ?? null) !== ($principal['principal_id'] ?? null)
            || ($source['binding_id'] ?? null) !== ($principal['binding_id'] ?? null)
            || ($source['identity'] ?? null) !== ($principal['identity'] ?? null)
            || ($source['principal_generation'] ?? null) + 1 !== ($principal['principal_generation'] ?? null)
            || ($transition['source_generation'] ?? null) !== $source['principal_generation']
            || ($transition['successor_generation'] ?? null) !== $principal['principal_generation']
            || !$this->exact($scope, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_AUTHORITY_SCOPE_FIELDS)
            || !$this->exact($sourceScope, ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS)
            || array_slice($scope, 0, 5, true) !== $sourceScope
            || true !== $scope['provider_executor_principal_activation_decision_authority']
            || !$this->exact($lifecycle, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_LIFECYCLE_FIELDS)
            || !$this->reference($lifecycle['prior_version'] ?? null)
            || !$this->sameReference($lifecycle['prior_version'], $sourceReference)
            || null !== $lifecycle['effective_at']
            || null !== $lifecycle['superseding_version']
            || null !== $lifecycle['current_disposition']
            || 'PENDING_ACTIVATION' !== ($principal['status'] ?? null)
            || false !== ($principal['credential_reference_persisted'] ?? null)
            || false !== ($principal['credential_secret_persisted'] ?? null)
            || false !== ($principal['serialized_capability_persisted'] ?? null)) {
            throw new \RuntimeException('PAD5B00_SUCCESSOR_PRINCIPAL_INVALID');
        }
    }

    public function assertProductionEnvelope(
        array $envelope,
        array $authorization,
        array $principal,
        ?array $executorAttestation = null,
    ): void {
        $this->common($envelope, ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::REQUIRED_FIELDS, ProviderExecutorPrincipalActivationDecisionProductionEnvelopeContract::SCHEMA, 'PAD5B10_PRODUCTION_ENVELOPE_INVALID');
        $this->common($authorization, ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::REQUIRED_FIELDS, ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::SCHEMA, 'PAD5B11_ISSUANCE_AUTHORIZATION_INVALID');
        $this->common($principal, ImperatorRuntimePrincipalVersionV3Contract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionV3Contract::SCHEMA, 'PAD5B12_SUCCESSOR_PRINCIPAL_INVALID');

        $actor = $envelope['actor'] ?? null;
        $scope = $envelope['scope'] ?? null;
        $authority = $envelope['activation_authority'] ?? null;
        $validity = $envelope['validity'] ?? null;
        $limitations = $envelope['limitations'] ?? null;
        $targetPrincipal = $principal['principal_id'];
        $targetGeneration = $principal['principal_generation'];
        if (null !== $executorAttestation) {
            $plainAttestation = $executorAttestation; unset($plainAttestation['record_digest']);
            if (($executorAttestation['record_digest'] ?? null) !== hash('sha256', CanonicalJson::encode($plainAttestation))
                || !$this->matches($authorization['principal_attestation'], $executorAttestation, 'principal_attestation_id')
                || ($executorAttestation['instance_id'] ?? null) !== $principal['instance_id']) {
                throw new \RuntimeException('PAD5B13_NATIVE_EXECUTOR_ATTESTATION_INVALID');
            }
            $targetPrincipal = $executorAttestation['principal']['principal_id'];
            $targetGeneration = $executorAttestation['principal']['generation'];
        }

        if (!$this->identifier($envelope['production_envelope_id'] ?? null)
            || ($envelope['instance_id'] ?? null) !== ($authorization['instance_id'] ?? null)
            || ($envelope['instance_id'] ?? null) !== ($principal['instance_id'] ?? null)
            || !$this->reference($envelope['issuance_authorization'] ?? null)
            || !$this->matches($envelope['issuance_authorization'], $authorization, 'issuance_authorization_id')
            || ($envelope['source_authority'] ?? null) !== $envelope['issuance_authorization']
            || ($envelope['issuer_principal'] ?? null) !== ($authorization['issuer_principal'] ?? null)
            || !$this->matchesPrincipal($envelope['issuer_principal'], $principal)
            || ($envelope['principal_attestation'] ?? null) !== ($authorization['principal_attestation'] ?? null)
            || ($envelope['provider_assurance_admission'] ?? null) !== ($authorization['provider_assurance_admission'] ?? null)
            || ($envelope['execution_boundary'] ?? null) !== ($authorization['execution_boundary'] ?? null)
            || !$this->exact($actor, ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTOR_FIELDS)
            || ($actor['principal_id'] ?? null) !== $principal['principal_id']
            || ($actor['binding_id'] ?? null) !== $principal['binding_id']
            || ($actor['generation'] ?? null) !== $principal['principal_generation']
            || !$this->identifier($actor['office'] ?? null)
            || !$this->identifier($actor['seat'] ?? null)
            || !$this->exact($scope, ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS)
            || !$this->identifier($scope['provider_id'] ?? null)
            || !$this->identifier($scope['operation'] ?? null)
            || ($scope['execution_boundary_id'] ?? null) !== $authorization['execution_boundary']['id']
            || ($scope['principal_id'] ?? null) !== $targetPrincipal
            || ($scope['principal_generation'] ?? null) !== $targetGeneration
            || !$this->identifier($scope['process_boundary_id'] ?? null)
            || true !== ($scope['same_process_execution_required'] ?? null)
            || !in_array($envelope['disposition'] ?? null, ProviderExecutorPrincipalActivationDecisionContract::DISPOSITIONS, true)
            || !is_string($envelope['rationale'] ?? null)
            || '' === trim($envelope['rationale'])
            || !is_array($limitations)
            || array_filter($limitations, static fn (mixed $value): bool => !is_string($value) || '' === trim($value)) !== []
            || !$this->exact($authority, ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS)
            || ($authority['authority_id'] ?? null) !== $authorization['activation_authority_id']
            || ($authority['authority_single_use'] ?? null) !== $authorization['authority_single_use']
            || ($authority['authority_exercisable'] ?? null) !== $authorization['authority_exercisable']
            || ($authority['permitted_transition'] ?? null) !== ProviderExecutorPrincipalActivationDecisionContract::PERMITTED_TRANSITION
            || ($authority['target_attestation_digest'] ?? null) !== $authorization['principal_attestation']['digest']
            || ($authority['expires_at'] ?? null) !== $authorization['expires_at']
            || ($authority['consumed'] ?? null) !== $authorization['consumed']
            || ($authority['continuing_authority'] ?? null) !== $authorization['continuing_authority']
            || !$this->identifier($authority['issuer_service'] ?? null)
            || !$this->exact($validity, ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_VALIDITY_FIELDS)
            || !$this->date($validity['effective_at'] ?? null)
            || ($validity['expires_at'] ?? null) !== $authorization['expires_at']
            || ($validity['revocation_reference'] ?? null) !== $authorization['revocation']
            || ($envelope['decision_id'] ?? null) !== $authorization['decision_id']
            || ($envelope['permitted_transition'] ?? null) !== $authorization['permitted_transition']) {
            throw new \RuntimeException('PAD5B10_PRODUCTION_ENVELOPE_INVALID');
        }
    }

    private function common(array $record, array $fields, string $schema, string $failure): void
    {
        $plain = $record;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function matchesPrincipal(array $reference, array $record): bool
    {
        return ($reference['id'] ?? null) === ($record['principal_version_id'] ?? null)
            && ($reference['digest'] ?? null) === ($record['record_digest'] ?? null)
            && ($reference['schema'] ?? null) === ($record['schema'] ?? null)
            && ($reference['generation'] ?? null) === ($record['principal_generation'] ?? null);
    }

    private function matches(array $reference, array $record, string $idField): bool
    {
        return ($reference['id'] ?? null) === ($record[$idField] ?? null)
            && ($reference['digest'] ?? null) === ($record['record_digest'] ?? null)
            && ($reference['schema'] ?? null) === ($record['schema'] ?? null);
    }

    private function principalReference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema', 'generation'] === array_keys($value)
            && $this->identifier($value['id'])
            && $this->digest($value['digest'])
            && $this->identifier($value['schema'])
            && is_int($value['generation'])
            && 0 < $value['generation'];
    }

    private function reference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema'] === array_keys($value)
            && $this->identifier($value['id'])
            && $this->digest($value['digest'])
            && $this->identifier($value['schema']);
    }

    private function sameReference(array $left, array $right): bool
    {
        return $left['id'] === $right['id']
            && $left['digest'] === $right['digest']
            && $left['schema'] === $right['schema'];
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
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
