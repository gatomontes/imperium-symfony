<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\PrincipalActivationDecisionAuthorityProvenanceProductionContract;
use App\Imperium\Runtime\Imperator\ProviderExecutorPrincipalActivationDecisionContract;

final class ProviderExecutorPrincipalActivationCanonicalContractValidator
{
    public function assertResolutionAdmission(
        array $admission,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->common(
            $admission,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::SCHEMA,
            'PRA200_RESOLUTION_ADMISSION_INVALID',
        );
        $this->common(
            $production,
            PrincipalActivationDecisionAuthorityProvenanceProductionContract::REQUIRED_FIELDS,
            PrincipalActivationDecisionAuthorityProvenanceProductionContract::SCHEMA,
            'PRA201_PRODUCTION_INVALID',
        );
        $this->common(
            $decision,
            ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalActivationDecisionContract::SCHEMA,
            'PRA202_DECISION_INVALID',
        );
        $this->sealedArtifact($attestation, 'principal_attestation_id', 'PRA203_ATTESTATION_INVALID');
        $this->sealedArtifact($assurance, 'admission_id', 'PRA204_ASSURANCE_INVALID');
        $this->sealedArtifact($boundary, 'boundary_id', 'PRA205_BOUNDARY_INVALID');

        if (!$this->identifier($admission['resolution_admission_id'] ?? null)
            || !$this->identifier($admission['instance_id'] ?? null)
            || !$this->referenceMatches($admission['provenance_production'] ?? null, $production, 'production_id')
            || !$this->referenceMatches($admission['production_decision'] ?? null, $decision, 'decision_id')
            || !$this->referenceMatches($admission['principal_attestation'] ?? null, $attestation, 'principal_attestation_id')
            || !$this->referenceMatches($admission['provider_assurance_admission'] ?? null, $assurance, 'admission_id')
            || !$this->referenceMatches($admission['execution_boundary'] ?? null, $boundary, 'boundary_id')
            || !$this->referenceMatches($production['activation_decision'] ?? null, $decision, 'decision_id')
            || $admission['instance_id'] !== ($production['instance_id'] ?? null)
            || $admission['instance_id'] !== ($decision['instance_id'] ?? null)
            || $admission['instance_id'] !== ($attestation['instance_id'] ?? null)
            || $admission['instance_id'] !== ($assurance['instance_id'] ?? null)
            || $admission['instance_id'] !== ($boundary['instance_id'] ?? null)
            || !$this->decisionEligible($decision, $at)
            || !$this->targetMatchesDecision($admission['activation_target'] ?? null, $decision)
            || !$this->authorityMatchesDecision($admission['activation_authority'] ?? null, $decision)
            || !$this->rootMatches(
                $admission['replay_contention_root'] ?? null,
                $admission,
                $production,
                $decision,
            )
            || !$this->date($admission['admitted_at'] ?? null)
            || true !== ($admission['exact_replay_only'] ?? null)
            || true !== ($admission['changed_evidence_conflicts'] ?? null)
            || false !== ($admission['resolution_required'] ?? null)
            || false !== ($admission['activation_performed'] ?? null)
            || false !== ($admission['authority_consumed'] ?? null)
            || false !== ($admission['continuing_authority'] ?? null)
            || false !== ($production['provider_executor_principal_activated'] ?? null)
            || false !== ($production['provider_binding_activated'] ?? null)
            || false !== ($production['activation_authority_consumed'] ?? null)
            || false !== ($production['credential_or_capability_handled'] ?? null)
            || false !== ($production['provider_invoked'] ?? null)
            || false !== ($production['external_action_performed'] ?? null)
            || false !== ($production['continuing_authority'] ?? null)
            || $this->containsSecretMaterial($admission)) {
            throw new \RuntimeException('PRA200_RESOLUTION_ADMISSION_INVALID');
        }
    }

    public function assertActivationInput(
        array $input,
        array $admission,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): void {
        $this->assertResolutionAdmission(
            $admission,
            $production,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );
        $this->common(
            $input,
            ProviderExecutorPrincipalActivationCanonicalInputContract::REQUIRED_FIELDS,
            ProviderExecutorPrincipalActivationCanonicalInputContract::SCHEMA,
            'PRA210_ACTIVATION_INPUT_INVALID',
        );

        if (!$this->identifier($input['input_id'] ?? null)
            || $input['instance_id'] !== $admission['instance_id']
            || !$this->referenceMatches($input['resolution_admission'] ?? null, $admission, 'resolution_admission_id')
            || ($input['provenance_production'] ?? null) !== $admission['provenance_production']
            || ($input['production_decision'] ?? null) !== $admission['production_decision']
            || ($input['principal_attestation'] ?? null) !== $admission['principal_attestation']
            || ($input['provider_assurance_admission'] ?? null) !== $admission['provider_assurance_admission']
            || ($input['execution_boundary'] ?? null) !== $admission['execution_boundary']
            || ($input['activation_target'] ?? null) !== $admission['activation_target']
            || ($input['activation_authority'] ?? null) !== $admission['activation_authority']
            || ($input['replay_contention_root'] ?? null) !== $admission['replay_contention_root']
            || true !== ($input['exact_replay_only'] ?? null)
            || true !== ($input['changed_evidence_conflicts'] ?? null)
            || $this->containsSecretMaterial($input)) {
            throw new \RuntimeException('PRA210_ACTIVATION_INPUT_INVALID');
        }
    }

    private function decisionEligible(array $decision, \DateTimeImmutable $at): bool
    {
        $validity = $decision['validity'] ?? null;
        $authority = $decision['activation_authority'] ?? null;

        return 'AUTHORIZED' === ($decision['disposition'] ?? null)
            && false === ($decision['external_action_performed'] ?? null)
            && $this->exact($validity, ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_VALIDITY_FIELDS)
            && $this->exact(
                $authority,
                ProviderExecutorPrincipalActivationDecisionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
            )
            && $this->date($validity['effective_at'] ?? null)
            && $this->date($validity['expires_at'] ?? null)
            && new \DateTimeImmutable($validity['effective_at']) <= $at
            && $at < new \DateTimeImmutable($validity['expires_at'])
            && null === ($validity['revocation_reference'] ?? null)
            && true === ($authority['authority_single_use'] ?? null)
            && true === ($authority['authority_exercisable'] ?? null)
            && false === ($authority['consumed'] ?? null)
            && false === ($authority['continuing_authority'] ?? null)
            && $authority['expires_at'] === $validity['expires_at'];
    }

    private function targetMatchesDecision(mixed $target, array $decision): bool
    {
        $actor = $decision['actor'] ?? [];
        $scope = $decision['scope'] ?? [];

        return $this->exact(
            $target,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_TARGET_FIELDS,
        )
            && $target['principal_id'] === ($actor['principal_id'] ?? null)
            && $target['binding_id'] === ($actor['binding_id'] ?? null)
            && $target['generation'] === ($actor['generation'] ?? null)
            && $target['process_boundary_id'] === ($scope['process_boundary_id'] ?? null)
            && $target['provider_id'] === ($scope['provider_id'] ?? null)
            && $target['operation'] === ($scope['operation'] ?? null);
    }

    private function authorityMatchesDecision(mixed $authority, array $decision): bool
    {
        $source = $decision['activation_authority'] ?? [];
        $validity = $decision['validity'] ?? [];

        return $this->exact(
            $authority,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_ACTIVATION_AUTHORITY_FIELDS,
        )
            && $authority['authority_id'] === ($source['authority_id'] ?? null)
            && $authority['decision_digest'] === ($decision['record_digest'] ?? null)
            && $authority['target_attestation_digest'] === ($source['target_attestation_digest'] ?? null)
            && $authority['effective_at'] === ($validity['effective_at'] ?? null)
            && $authority['expires_at'] === ($validity['expires_at'] ?? null)
            && $authority['revocation_reference'] === ($validity['revocation_reference'] ?? null)
            && true === $authority['authority_single_use']
            && true === $authority['authority_exercisable']
            && false === $authority['consumed']
            && false === $authority['continuing_authority'];
    }

    private function rootMatches(mixed $root, array $admission, array $production, array $decision): bool
    {
        $actor = $decision['actor'] ?? [];
        $scope = $decision['scope'] ?? [];
        $authority = $decision['activation_authority'] ?? [];

        return $this->exact(
            $root,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REPLAY_CONTENTION_ROOT_FIELDS,
        )
            && $this->identifier($root['root_id'] ?? null)
            && $root['instance_id'] === $admission['instance_id']
            && $root['principal_id'] === ($actor['principal_id'] ?? null)
            && $root['principal_generation'] === ($actor['generation'] ?? null)
            && $root['process_boundary_id'] === ($scope['process_boundary_id'] ?? null)
            && $root['production_id'] === ($production['production_id'] ?? null)
            && $root['decision_id'] === ($decision['decision_id'] ?? null)
            && $root['authority_id'] === ($authority['authority_id'] ?? null);
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

    private function referenceMatches(mixed $reference, array $record, string $idField): bool
    {
        return $this->exact(
            $reference,
            ProviderExecutorPrincipalActivationCanonicalResolutionAdmissionContract::REQUIRED_REFERENCE_FIELDS,
        )
            && $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function containsSecretMaterial(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $item) {
            if (is_string($key)
                && (bool) preg_match('/(?:credential|capability|secret|api[_-]?key|access[_-]?token|environment[_-]?variable)/i', $key)) {
                return true;
            }
            if ($this->containsSecretMaterial($item)) {
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
