<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;

final class ProviderExecutorPrincipalActivationContractValidator
{
    public function assertDecision(
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common(
            $decision,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalActivationDecisionContract::SCHEMA,
            'PEA700_ACTIVATION_DECISION_INVALID',
        );
        $this->assertAttestation($attestation, $boundary, $at);
        $this->assertAssurance($assurance, $at);

        $scope = $decision['scope'] ?? null;
        $actor = $decision['actor'] ?? null;
        $validity = $decision['validity'] ?? null;
        if (!$this->identifier($decision['decision_id'] ?? null)
            || !$this->identifier($decision['instance_id'] ?? null)
            || !$this->reference($decision['source_authority'] ?? null)
            || !$this->matches(
                $decision['principal_attestation'] ?? null,
                $attestation,
                'principal_attestation_id',
            )
            || !$this->matches(
                $decision['provider_assurance_admission'] ?? null,
                $assurance,
                'admission_id',
            )
            || !$this->exact(
                $actor,
                ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTOR_FIELDS,
            )
            || !$this->identifier($actor['principal_id'] ?? null)
            || !$this->identifier($actor['office'] ?? null)
            || !$this->identifier($actor['seat'] ?? null)
            || !$this->identifier($actor['binding_id'] ?? null)
            || !is_int($actor['generation'] ?? null)
            || ($actor['generation'] ?? 0) < 1
            || !$this->scopeMatches($scope, $attestation, $assurance, $boundary)
            || !in_array(
                $decision['disposition'] ?? null,
                ProviderExecutorPrincipalActivationDecisionContract::DISPOSITIONS,
                true,
            )
            || !is_string($decision['rationale'] ?? null)
            || '' === trim($decision['rationale'])
            || !is_string($decision['limitations'] ?? null)
            || '' === trim($decision['limitations'])
            || !$this->validity($validity, $at)
            || !$this->date($decision['decided_at'] ?? null)
            || $decision['decided_at'] !== $validity['effective_at']
            || false !== ($decision['external_action_performed'] ?? null)) {
            throw new \RuntimeException('PEA700_ACTIVATION_DECISION_INVALID');
        }

        if ('AUTHORIZED' === $decision['disposition']) {
            $this->assertActivationAuthority(
                $decision['activation_authority'] ?? null,
                $attestation,
                $validity,
            );
        } elseif (null !== ($decision['activation_authority'] ?? null)) {
            throw new \RuntimeException('PEA700_ACTIVATION_DECISION_INVALID');
        }
    }

    public function assertActivation(
        array $activation,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common(
            $activation,
            ProviderExecutorPrincipalActivationContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalActivationContract::SCHEMA,
            'PEA710_PRINCIPAL_ACTIVATION_INVALID',
        );

        $activatedAt = $this->dateValue($activation['activated_at'] ?? null);
        if (null === $activatedAt) {
            throw new \RuntimeException('PEA710_PRINCIPAL_ACTIVATION_INVALID');
        }
        $this->assertDecision($decision, $attestation, $assurance, $boundary, $activatedAt);
        if ('AUTHORIZED' !== ($decision['disposition'] ?? null)) {
            throw new \RuntimeException('PEA710_PRINCIPAL_ACTIVATION_INVALID');
        }

        $authority = $decision['activation_authority'];
        $consumed = $activation['consumed_activation_authority'] ?? null;
        $scope = $activation['scope'] ?? null;
        $validity = $activation['validity'] ?? null;
        $reconstruction = $activation['reconstruction'] ?? null;

        if (!$this->identifier($activation['principal_activation_id'] ?? null)
            || $decision['instance_id'] !== ($activation['instance_id'] ?? null)
            || !$this->matches($activation['source_decision'] ?? null, $decision, 'decision_id')
            || !$this->matches(
                $activation['provider_assurance_admission'] ?? null,
                $assurance,
                'admission_id',
            )
            || !$this->matches(
                $activation['execution_boundary'] ?? null,
                $boundary,
                'boundary_id',
            )
            || !$this->matches(
                $activation['principal_attestation'] ?? null,
                $attestation,
                'principal_attestation_id',
            )
            || !$this->consumedAuthority($consumed, $authority, $decision)
            || ProviderExecutorPrincipalActivationContract::REQUIRED_PRINCIPAL_FIELDS
                !== array_keys($activation['principal'] ?? [])
            || $attestation['principal'] !== $activation['principal']
            || !$this->activationScope($scope, $decision['scope'])
            || !$this->exact(
                $validity,
                ProviderExecutorPrincipalActivationContract::REQUIRED_VALIDITY_FIELDS,
            )
            || $decision['validity'] !== $validity
            || !$this->exact(
                $reconstruction,
                ProviderExecutorPrincipalActivationContract::REQUIRED_RECONSTRUCTION_FIELDS,
            )
            || [true, true, false, false] !== array_values($reconstruction)
            || !in_array(
                $activation['status'] ?? null,
                ProviderExecutorPrincipalActivationContract::STATUSES,
                true,
            )
            || $activation['activated_at'] !== $validity['effective_at']
            || $activation['activated_at'] !== $consumed['consumed_at']
            || !$this->activationStatus($activation['status'], $validity, $at)) {
            throw new \RuntimeException('PEA710_PRINCIPAL_ACTIVATION_INVALID');
        }
    }

    private function assertAttestation(
        array $attestation,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common(
            $boundary,
            ProviderExecutionBoundaryContract::REQUIRED_FIELDS,
            ProviderExecutionBoundaryContract::SCHEMA,
            'PEA701_EXECUTION_BOUNDARY_INVALID',
        );
        $this->common(
            $attestation,
            ProviderExecutorPrincipalContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalContract::SCHEMA,
            'PEA702_PRINCIPAL_ATTESTATION_INVALID',
        );

        $principal = $attestation['principal'] ?? null;
        $competence = $attestation['competence'] ?? null;
        $validity = $attestation['validity'] ?? null;
        if ('DEFINED_INERT' !== ($boundary['status'] ?? null)
            || !$this->matches(
                $attestation['execution_boundary'] ?? null,
                $boundary,
                'boundary_id',
            )
            || !$this->identifier($attestation['principal_attestation_id'] ?? null)
            || !$this->identifier($attestation['instance_id'] ?? null)
            || $boundary['instance_id'] !== $attestation['instance_id']
            || !$this->reference($attestation['source_attestation'] ?? null)
            || !$this->exact($principal, ProviderExecutorPrincipalContract::REQUIRED_PRINCIPAL_FIELDS)
            || !$this->identifier($principal['principal_id'] ?? null)
            || !$this->identifier($principal['infrastructure_role'] ?? null)
            || !$this->identifier($principal['binding_id'] ?? null)
            || !is_int($principal['generation'] ?? null)
            || ($principal['generation'] ?? 0) < 1
            || $boundary['boundary_id'] !== ($principal['process_boundary_id'] ?? null)
            || !$this->exact(
                $competence,
                ProviderExecutorPrincipalContract::REQUIRED_COMPETENCE_FIELDS,
            )
            || !$this->identifier($competence['operation'] ?? null)
            || !$this->identifier($competence['provider_id'] ?? null)
            || !$this->identifier($competence['adapter_id'] ?? null)
            || !$this->identifier($competence['credential_family'] ?? null)
            || true !== ($competence['same_process_execution_required'] ?? null)
            || !$this->exact(
                $validity,
                ProviderExecutorPrincipalContract::REQUIRED_VALIDITY_FIELDS,
            )
            || !$this->date($validity['effective_at'] ?? null)
            || !$this->date($validity['expires_at'] ?? null)
            || new \DateTimeImmutable($validity['effective_at']) > $at
            || $at >= new \DateTimeImmutable($validity['expires_at'])
            || null !== ($validity['revocation_reference'] ?? null)
            || 'ATTESTED_INERT' !== ($attestation['status'] ?? null)
            || !$this->date($attestation['attested_at'] ?? null)) {
            throw new \RuntimeException('PEA702_PRINCIPAL_ATTESTATION_INVALID');
        }
    }

    private function assertAssurance(array $assurance, \DateTimeImmutable $at): void
    {
        $this->common(
            $assurance,
            ProviderAssuranceEvidenceAdmissionContract::REQUIRED_FIELDS,
            ProviderAssuranceEvidenceAdmissionContract::SCHEMA,
            'PEA703_ASSURANCE_ADMISSION_INVALID',
        );
        $validity = $assurance['validity'] ?? null;
        if (!$this->identifier($assurance['admission_id'] ?? null)
            || 'agentmail' !== ($assurance['provider_id'] ?? null)
            || 'email.send' !== ($assurance['operation'] ?? null)
            || 'EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY' !== ($assurance['status'] ?? null)
            || !$this->exact(
                $validity,
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_VALIDITY_FIELDS,
            )
            || !$this->date($validity['effective_at'] ?? null)
            || !$this->date($validity['review_due_at'] ?? null)
            || new \DateTimeImmutable($validity['effective_at']) > $at
            || $at >= new \DateTimeImmutable($validity['review_due_at'])
            || null !== ($validity['supersession_reference'] ?? null)
            || null !== ($validity['revocation_reference'] ?? null)) {
            throw new \RuntimeException('PEA703_ASSURANCE_ADMISSION_INVALID');
        }
    }

    private function scopeMatches(
        mixed $scope,
        array $attestation,
        array $assurance,
        array $boundary,
    ): bool {
        return $this->exact(
            $scope,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_SCOPE_FIELDS,
        )
            && $scope['provider_id'] === $assurance['provider_id']
            && $scope['provider_id'] === $attestation['competence']['provider_id']
            && $scope['operation'] === $assurance['operation']
            && $scope['operation'] === $attestation['competence']['operation']
            && $scope['execution_boundary_id'] === $boundary['boundary_id']
            && $scope['principal_id'] === $attestation['principal']['principal_id']
            && $scope['principal_generation'] === $attestation['principal']['generation']
            && $scope['process_boundary_id'] === $attestation['principal']['process_boundary_id']
            && true === $scope['same_process_execution_required'];
    }

    private function assertActivationAuthority(
        mixed $authority,
        array $attestation,
        array $validity,
    ): void {
        if (!$this->exact(
            $authority,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        )
            || !$this->identifier($authority['authority_id'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || true !== ($authority['authority_exercisable'] ?? null)
            || !$this->identifier($authority['issuer_service'] ?? null)
            || ProviderExecutorPrincipalActivationDecisionContract::PERMITTED_TRANSITION
                !== ($authority['permitted_transition'] ?? null)
            || $attestation['record_digest'] !== ($authority['target_attestation_digest'] ?? null)
            || $validity['expires_at'] !== ($authority['expires_at'] ?? null)
            || false !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)) {
            throw new \RuntimeException('PEA700_ACTIVATION_DECISION_INVALID');
        }
    }

    private function consumedAuthority(mixed $consumed, array $authority, array $decision): bool
    {
        return $this->exact(
            $consumed,
            ProviderExecutorPrincipalActivationContract::REQUIRED_CONSUMED_AUTHORITY_FIELDS,
        )
            && $consumed['id'] === $authority['authority_id']
            && $consumed['digest'] === $decision['record_digest']
            && $consumed['schema'] === $decision['schema']
            && $this->date($consumed['consumed_at'] ?? null)
            && true === $consumed['consumed']
            && false === $consumed['continuing_authority'];
    }

    private function activationScope(mixed $scope, array $decisionScope): bool
    {
        return $this->exact(
            $scope,
            ProviderExecutorPrincipalActivationContract::REQUIRED_SCOPE_FIELDS,
        )
            && $scope['provider_id'] === $decisionScope['provider_id']
            && $scope['operation'] === $decisionScope['operation']
            && true === $scope['same_process_execution_required']
            && false === $scope['provider_substitution_permitted']
            && false === $scope['operation_substitution_permitted']
            && false === $scope['principal_generation_substitution_permitted'];
    }

    private function activationStatus(string $status, array $validity, \DateTimeImmutable $at): bool
    {
        $effective = new \DateTimeImmutable($validity['effective_at']);
        $expires = new \DateTimeImmutable($validity['expires_at']);
        $revocation = $validity['revocation_reference'];

        return match ($status) {
            'ACTIVE' => $effective <= $at && $at < $expires && null === $revocation,
            'EXPIRED' => $expires <= $at && null === $revocation,
            'REVOKED' => $this->reference($revocation),
            default => false,
        };
    }

    private function validity(mixed $value, \DateTimeImmutable $at): bool
    {
        return $this->exact(
            $value,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_VALIDITY_FIELDS,
        )
            && $this->date($value['effective_at'] ?? null)
            && $this->date($value['expires_at'] ?? null)
            && new \DateTimeImmutable($value['effective_at']) === $at
            && $at < new \DateTimeImmutable($value['expires_at'])
            && null === ($value['revocation_reference'] ?? null);
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

    private function matches(mixed $reference, array $record, string $idField): bool
    {
        return $this->reference($reference)
            && $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function reference(mixed $value): bool
    {
        return $this->exact(
            $value,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_REFERENCE_FIELDS,
        )
            && $this->identifier($value['id'] ?? null)
            && $this->digest($value['digest'] ?? null)
            && is_string($value['schema'] ?? null)
            && str_ends_with($value['schema'], '/v1');
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        return null !== $this->dateValue($value);
    }

    private function dateValue(mixed $value): ?\DateTimeImmutable
    {
        if (!is_string($value)) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        return false !== $date && $date->format(DATE_ATOM) === $value ? $date : null;
    }
}
