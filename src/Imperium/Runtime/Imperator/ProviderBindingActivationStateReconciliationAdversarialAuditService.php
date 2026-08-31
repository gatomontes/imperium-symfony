<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingActivationReconciliationContractValidator;

/** Pure audit of caller-supplied offline evidence. No persistence or effect dependency. */
final readonly class ProviderBindingActivationStateReconciliationAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'immutable_integrity_proved',
        'exact_lineage_proved',
        'lifecycle_eligibility_proved',
        'expiry_refusal_proved',
        'revocation_refusal_proved',
        'substitution_refusal_proved',
        'secret_exclusion_proved',
        'before_commit_absence_proved',
        'after_commit_winner_proved',
        'exact_replay_converged',
        'changed_evidence_conflicted',
        'same_root_contention_proved',
        'reconstruction_read_only_proved',
        'non_authority_perimeter_proved',
    ];

    public function audit(
        array $reconstruction,
        array $target,
        array $input,
        array $successor,
        array $principalActivation,
        array $bindingDescriptor,
        array $assurance,
        array $boundary,
        array $proofs,
        \DateTimeImmutable $at,
    ): array {
        $root = $target['replay_contention_root'] ?? [];
        $rootId = $root['root_id'] ?? null;
        $validity = $target['validity'] ?? [];

        if (!is_string($rootId)
            || !$this->date($validity['effective_at'] ?? null)
            || !$this->date($validity['expires_at'] ?? null)
            || $at < new \DateTimeImmutable($validity['effective_at'])
            || $at >= new \DateTimeImmutable($validity['expires_at'])
            || null !== ($validity['revocation_reference'] ?? null)
            || null !== ($input['activation_authority']['revocation_reference'] ?? null)) {
            return $this->result(
                is_string($rootId) ? $rootId : null,
                'REFUSED',
                ['LIFECYCLE_REVOKED_EXPIRED_OR_NOT_EFFECTIVE'],
                $at,
            );
        }

        try {
            $validator = new ProviderBindingActivationReconciliationContractValidator();
            $validator->assertTarget(
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
            $validator->assertDecisionInput(
                $input,
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
            $validator->assertSuccessor(
                $successor,
                $input,
                $target,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
                $at,
            );
            $this->assertReconstruction(
                $reconstruction,
                $target,
                $input,
                $successor,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
            );
            $this->assertProofs($proofs);
            $this->assertSecretExclusion([
                $reconstruction,
                $target,
                $input,
                $successor,
                $principalActivation,
                $bindingDescriptor,
                $assurance,
                $boundary,
            ]);
        } catch (\Throwable $error) {
            return $this->result(
                is_string($rootId) ? $rootId : null,
                'CONFLICTED',
                ['ADVERSARIAL_CONFLICT:'.$error->getMessage()],
                $at,
            );
        }

        return $this->result(
            $rootId,
            'PASSED',
            [
                'IMMUTABLE_INTEGRITY_LINEAGE_AND_LIFECYCLE_EXACT',
                'INTERRUPTION_REPLAY_AND_SAME_ROOT_CONTENTION_PROVED',
                'SECRET_EXCLUSION_AND_NON_AUTHORITY_PERIMETER_PRESERVED',
                'READ_ONLY_RECONSTRUCTION_READY_FOR_TERMINAL_AUDIT',
            ],
            $at,
        );
    }

    private function assertReconstruction(
        array $reconstruction,
        array $target,
        array $input,
        array $successor,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
    ): void {
        $expectedChain = [
            'principal_activation' => $this->reference($principal, 'activation_id'),
            'binding_descriptor' => $this->reference($binding, 'binding_id'),
            'provider_assurance_admission' => $this->reference($assurance, 'admission_id'),
            'execution_boundary' => $this->reference($boundary, 'boundary_id'),
            'reconciled_target' => $this->reference($target, 'target_id'),
            'decision_input' => $this->reference($input, 'decision_input_id'),
            'lifecycle_successor' => $this->reference($successor, 'successor_id'),
            'operation_scope' => $successor['operation_scope'],
            'replay_contention_root' => $successor['replay_contention_root'],
            'validity' => $successor['validity'],
        ];
        $proof = [
            'classification' => 'ELIGIBLE_OFFLINE_BINDING_SUCCESSOR',
            'chain' => $expectedChain,
            'reasons' => [],
        ];

        if ('ELIGIBLE_OFFLINE_BINDING_SUCCESSOR' !== ($reconstruction['classification'] ?? null)
            || $expectedChain !== ($reconstruction['chain'] ?? null)
            || [] !== ($reconstruction['reasons'] ?? null)
            || hash('sha256', CanonicalJson::encode($proof))
                !== ($reconstruction['proof_digest'] ?? null)
            || true !== ($reconstruction['read_only'] ?? null)) {
            throw new \RuntimeException('PBR500_RECONSTRUCTION_NOT_EXACT_ELIGIBLE');
        }

        foreach ([
            'fixture_created',
            'fixture_repaired',
            'artifact_replaced',
            'artifact_promoted',
            'production_decision_created',
            'activation_transition_performed',
            'provider_binding_activated',
            'activation_authority_issued',
            'activation_authority_consumed',
            'execution_authority_created',
            'credential_or_capability_handled',
            'provider_invoked',
            'external_io_started',
            'provider_effect_started',
            'retry_authority_created',
            'continuing_authority',
        ] as $field) {
            if (false !== ($reconstruction[$field] ?? null)) {
                throw new \RuntimeException('PBR501_RECONSTRUCTION_NON_AUTHORITY_VIOLATED:'.$field);
            }
        }
    }

    private function assertProofs(array $proofs): void
    {
        if (self::REQUIRED_PROOFS !== array_keys($proofs)) {
            throw new \RuntimeException('PBR502_ADVERSARIAL_PROOF_SET_INCOMPLETE');
        }
        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException('PBR503_ADVERSARIAL_PROOF_FAILED');
            }
        }
    }

    private function assertSecretExclusion(array $records): void
    {
        $forbidden = [
            'credential_secret',
            'credential_bytes',
            'credential_reference',
            'capability_identity',
            'capability_bytes',
            'api_key',
            'access_token',
            'refresh_token',
            'password',
            'authentication_material',
            'environment_variable',
            'process_local_capability',
        ];
        $walk = function (array $value) use (&$walk, $forbidden): void {
            foreach ($value as $key => $item) {
                $normalized = strtolower((string) $key);
                foreach ($forbidden as $fragment) {
                    if (str_contains($normalized, $fragment)
                        && false !== $item
                        && null !== $item) {
                        throw new \RuntimeException('PBR504_SECRET_MATERIAL_PRESENT');
                    }
                }
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };
        $walk($records);
    }

    private function result(
        ?string $rootId,
        string $classification,
        array $findings,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => ProviderBindingActivationStateReconciliationAdversarialAuditResultContract::SCHEMA,
            'classification' => $classification,
            'findings' => $findings,
            'audited_root' => $rootId,
            'audited_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'artifact_replaced' => false,
            'artifact_promoted' => false,
            'production_decision_created' => false,
            'activation_transition_performed' => false,
            'binding_activated' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authority_created' => false,
            'continuing_authority' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
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
