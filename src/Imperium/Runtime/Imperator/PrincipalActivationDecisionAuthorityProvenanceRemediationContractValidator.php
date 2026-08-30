<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class PrincipalActivationDecisionAuthorityProvenanceRemediationContractValidator
{
    public function assertScopeGrant(array $grant, array $sourcePrincipal, \DateTimeImmutable $at): void
    {
        $this->common($grant, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_FIELDS, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::SCHEMA, 'PAD200_SCOPE_GRANT_INVALID');
        $this->common($sourcePrincipal, ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, 'PAD201_SOURCE_PRINCIPAL_INVALID');

        $source = $grant['source_principal'] ?? null;
        $successor = $grant['successor_principal'] ?? null;
        if (!$this->identifier($grant['grant_id'] ?? null)
            || !$this->identifier($grant['instance_id'] ?? null)
            || !$this->operatorRoot($grant['operator_root'] ?? null)
            || !$this->principalReference($source)
            || !$this->principalReference($successor)
            || $source['id'] === $successor['id']
            || $source['generation'] + 1 !== $successor['generation']
            || !$this->matches($source, $sourcePrincipal, 'principal_version_id')
            || $grant['instance_id'] !== $sourcePrincipal['instance_id']
            || 'ACTIVE' !== $sourcePrincipal['status']
            || !$this->exact($sourcePrincipal['authority_scope'] ?? null, ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS)
            || !$this->exact($grant['scope_delta'] ?? null, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_SCOPE_DELTA_FIELDS)
            || true !== $grant['scope_delta']['provider_executor_principal_activation_decision_authority']
            || !$this->scopePreserved($grant['preserved_scope'] ?? null, $sourcePrincipal['authority_scope'])
            || ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::PERMITTED_TRANSITION !== ($grant['permitted_transition'] ?? null)
            || !is_string($grant['rationale'] ?? null)
            || '' === trim($grant['rationale'])
            || true !== ($grant['authority_single_use'] ?? null)
            || true !== ($grant['authority_exercisable'] ?? null)
            || true !== ($grant['issuance_winner_required'] ?? null)
            || true !== ($grant['consumption_winner_required'] ?? null)
            || !$this->activeWindow($grant['issued_at'] ?? null, $grant['expires_at'] ?? null, $at)
            || null !== ($grant['revocation'] ?? null)
            || false !== ($grant['consumed'] ?? null)
            || false !== ($grant['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PAD200_SCOPE_GRANT_INVALID');
        }
    }

    public function assertScopeSuccessor(array $successor, array $grant): void
    {
        $this->common($successor, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::REQUIRED_FIELDS, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::SCHEMA, 'PAD210_SCOPE_SUCCESSOR_INVALID');

        if (!$this->identifier($successor['successor_transition_id'] ?? null)
            || !$this->identifier($successor['instance_id'] ?? null)
            || !$this->reference($successor['scope_grant'] ?? null)
            || !$this->matches($successor['scope_grant'], $grant, 'grant_id')
            || !$this->principalReference($successor['source_principal'] ?? null)
            || !$this->principalReference($successor['successor_principal'] ?? null)
            || $successor['source_principal'] !== $grant['source_principal']
            || $successor['successor_principal'] !== $grant['successor_principal']
            || $successor['instance_id'] !== $grant['instance_id']
            || $successor['source_generation'] !== $successor['source_principal']['generation']
            || $successor['successor_generation'] !== $successor['successor_principal']['generation']
            || $successor['source_generation'] + 1 !== $successor['successor_generation']
            || true !== ($successor['identity_preserved'] ?? null)
            || true !== ($successor['binding_preserved'] ?? null)
            || ($successor['scope_delta'] ?? null) !== $grant['scope_delta']
            || ($successor['preserved_scope'] ?? null) !== $grant['preserved_scope']
            || ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::INITIAL_STATUS !== ($successor['initial_status'] ?? null)
            || true !== ($successor['activation_required'] ?? null)
            || true !== ($successor['separate_activation_authority_required'] ?? null)
            || true !== ($successor['transition_winner_required'] ?? null)
            || !$this->date($successor['committed_at'] ?? null)
            || true !== ($successor['grant_consumed'] ?? null)
            || false !== ($successor['source_principal_mutated'] ?? null)
            || false !== ($successor['source_principal_superseded'] ?? null)
            || false !== ($successor['decision_issuance_authorization_created'] ?? null)
            || false !== ($successor['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PAD210_SCOPE_SUCCESSOR_INVALID');
        }
    }

    public function assertIssuanceAuthorization(
        array $authorization,
        array $successor,
        array $activationDisposition,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common($authorization, ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::REQUIRED_FIELDS, ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::SCHEMA, 'PAD220_ISSUANCE_AUTHORIZATION_INVALID');
        $this->common($successor, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::REQUIRED_FIELDS, ImperatorProviderExecutorPrincipalActivationDecisionScopeSuccessorContract::SCHEMA, 'PAD221_SCOPE_SUCCESSOR_INVALID');
        $this->common($activationDisposition, ImperatorPrincipalLifecycleDispositionContract::REQUIRED_FIELDS, ImperatorPrincipalLifecycleDispositionContract::SCHEMA, 'PAD222_ACTIVATION_DISPOSITION_INVALID');
        $this->sealedArtifact($attestation, 'principal_attestation_id', 'PAD223_ATTESTATION_INVALID');
        $this->sealedArtifact($assurance, 'admission_id', 'PAD224_ASSURANCE_INVALID');
        $this->sealedArtifact($boundary, 'boundary_id', 'PAD225_BOUNDARY_INVALID');

        $issuer = $authorization['issuer_principal'] ?? null;
        $successorPrincipal = $successor['successor_principal'] ?? null;
        if (!$this->identifier($authorization['issuance_authorization_id'] ?? null)
            || !$this->identifier($authorization['instance_id'] ?? null)
            || !$this->principalReference($issuer)
            || $issuer !== $successorPrincipal
            || !$this->reference($authorization['scope_successor'] ?? null)
            || !$this->matches($authorization['scope_successor'], $successor, 'successor_transition_id')
            || !$this->reference($authorization['activation_disposition'] ?? null)
            || !$this->matches($authorization['activation_disposition'], $activationDisposition, 'disposition_id')
            || !$this->reference($authorization['principal_attestation'] ?? null)
            || !$this->matches($authorization['principal_attestation'], $attestation, 'principal_attestation_id')
            || !$this->reference($authorization['provider_assurance_admission'] ?? null)
            || !$this->matches($authorization['provider_assurance_admission'], $assurance, 'admission_id')
            || !$this->reference($authorization['execution_boundary'] ?? null)
            || !$this->matches($authorization['execution_boundary'], $boundary, 'boundary_id')
            || $authorization['instance_id'] !== $successor['instance_id']
            || $authorization['instance_id'] !== ($activationDisposition['instance_id'] ?? null)
            || $authorization['instance_id'] !== ($attestation['instance_id'] ?? null)
            || $authorization['instance_id'] !== ($assurance['instance_id'] ?? null)
            || $authorization['instance_id'] !== ($boundary['instance_id'] ?? null)
            || 'PENDING_ACTIVATION' !== ($activationDisposition['source_status'] ?? null)
            || 'ACTIVATE' !== ($activationDisposition['disposition'] ?? null)
            || !$this->reference($activationDisposition['source_principal_version'] ?? null)
            || !$this->sameReference($activationDisposition['source_principal_version'], $successorPrincipal)
            || false !== ($activationDisposition['authority_scope_changed'] ?? null)
            || true !== ($activationDisposition['historical_attribution_preserved'] ?? null)
            || true !== ($activationDisposition['caller_authority_issuance_permitted_after_effective_at'] ?? null)
            || false !== ($activationDisposition['external_action_performed'] ?? null)
            || !$this->date($activationDisposition['effective_at'] ?? null)
            || new \DateTimeImmutable($activationDisposition['effective_at']) > $at
            || !$this->identifier($authorization['decision_id'] ?? null)
            || !$this->identifier($authorization['activation_authority_id'] ?? null)
            || ProviderExecutorPrincipalActivationDecisionIssuanceAuthorizationContract::PERMITTED_TRANSITION !== ($authorization['permitted_transition'] ?? null)
            || true !== ($authorization['authority_single_use'] ?? null)
            || true !== ($authorization['authority_exercisable'] ?? null)
            || true !== ($authorization['issuance_winner_required'] ?? null)
            || true !== ($authorization['consumption_winner_required'] ?? null)
            || !$this->activeWindow($authorization['issued_at'] ?? null, $authorization['expires_at'] ?? null, $at)
            || null !== ($authorization['revocation'] ?? null)
            || false !== ($authorization['consumed'] ?? null)
            || false !== ($authorization['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PAD220_ISSUANCE_AUTHORIZATION_INVALID');
        }
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
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function operatorRoot(mixed $value): bool
    {
        return $this->exact($value, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_OPERATOR_ROOT_FIELDS)
            && $this->identifier($value['operator_id'])
            && $this->digest($value['source_identity_digest'])
            && $this->identifier($value['decision_id'])
            && $this->digest($value['decision_digest']);
    }

    private function principalReference(mixed $value): bool
    {
        return $this->exact($value, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_PRINCIPAL_FIELDS)
            && $this->identifier($value['id'])
            && $this->digest($value['digest'])
            && $this->identifier($value['schema'])
            && is_int($value['generation'])
            && $value['generation'] > 0;
    }

    private function reference(mixed $value): bool
    {
        return $this->exact($value, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_REFERENCE_FIELDS)
            && $this->identifier($value['id'])
            && $this->digest($value['digest'])
            && $this->identifier($value['schema']);
    }

    private function matches(array $reference, array $record, string $idField): bool
    {
        return ($reference['id'] ?? null) === ($record[$idField] ?? null)
            && ($reference['digest'] ?? null) === ($record['record_digest'] ?? null)
            && ($reference['schema'] ?? null) === ($record['schema'] ?? null);
    }

    private function sameReference(array $left, array $right): bool
    {
        return $left['id'] === $right['id']
            && $left['digest'] === $right['digest']
            && $left['schema'] === $right['schema'];
    }

    private function scopePreserved(mixed $value, array $source): bool
    {
        if (!$this->exact($value, ImperatorProviderExecutorPrincipalActivationDecisionScopeGrantContract::REQUIRED_PRESERVED_SCOPE_FIELDS)) {
            return false;
        }
        foreach ($value as $field => $preserved) {
            if ($preserved !== $source[$field]) {
                return false;
            }
        }
        return true;
    }

    private function activeWindow(mixed $issuedAt, mixed $expiresAt, \DateTimeImmutable $at): bool
    {
        if (!$this->date($issuedAt) || !$this->date($expiresAt)) {
            return false;
        }
        $issued = new \DateTimeImmutable($issuedAt);
        $expires = new \DateTimeImmutable($expiresAt);
        return $issued <= $at && $at < $expires && $expires <= $issued->modify('+15 minutes');
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
