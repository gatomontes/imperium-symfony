<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorProductionAdoptionContractValidator;

/** Pure audit of caller-supplied offline evidence. No persistence or effect dependency. */
final readonly class ProviderBindingSuccessorProductionAdoptionAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'immutable_integrity_proved',
        'acyclic_v2_lineage_proved',
        'defective_v1_refusal_proved',
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
        'v3_admission_not_implemented_proved',
        'non_authority_perimeter_proved',
    ];

    public function audit(
        array $reconstruction,
        array $decision,
        array $authority,
        array $adoption,
        array $decisionAuthority,
        array $target,
        array $input,
        array $successor,
        array $principal,
        array $binding,
        array $assurance,
        array $boundary,
        array $proofs,
        \DateTimeImmutable $at,
    ): array {
        $rootId = $target['replay_contention_root']['root_id'] ?? null;
        $validity = $decision['validity'] ?? [];
        if (!is_string($rootId)
            || !$this->date($validity['effective_at'] ?? null)
            || !$this->date($validity['expires_at'] ?? null)
            || $at < new \DateTimeImmutable($validity['effective_at'])
            || $at >= new \DateTimeImmutable($validity['expires_at'])
            || null !== ($validity['revocation_reference'] ?? null)
            || null !== ($authority['validity']['revocation_reference'] ?? null)) {
            return $this->result(
                is_string($rootId) ? $rootId : null,
                'REFUSED',
                ['LIFECYCLE_REVOKED_EXPIRED_OR_NOT_EFFECTIVE'],
                $at,
            );
        }

        try {
            $validator = new ProviderBindingSuccessorProductionAdoptionContractValidator();
            $validator->assertDecision(
                $decision, $decisionAuthority, $target, $input, $principal,
                $binding, $assurance, $boundary, $at,
            );
            $validator->assertAuthority(
                $authority, $decision, $decisionAuthority, $target, $input,
                $principal, $binding, $assurance, $boundary, $at,
            );
            $validator->assertAdoptionTarget(
                $adoption, $successor, $input, $target, $principal, $binding,
                $assurance, $boundary, $at,
            );
            $this->assertReconstruction(
                $reconstruction, $decisionAuthority, $target, $input, $successor,
                $decision, $authority, $adoption,
            );
            $this->assertProofs($proofs);
            $this->assertSecretExclusion([
                $reconstruction, $decision, $authority, $adoption,
                $decisionAuthority, $target, $input, $successor, $principal,
                $binding, $assurance, $boundary,
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
                'IMMUTABLE_INTEGRITY_AND_ACYCLIC_V2_LINEAGE_EXACT',
                'V1_SUBSTITUTION_EXPIRY_REVOCATION_AND_SECRET_ATTACKS_REFUSED',
                'INTERRUPTION_REPLAY_AND_SAME_ROOT_CONTENTION_PROVED',
                'READ_ONLY_RECONSTRUCTION_AND_NON_AUTHORITY_PERIMETER_PRESERVED',
                'V3_EXECUTION_ADMISSION_REMAINS_NOT_IMPLEMENTED',
            ],
            $at,
        );
    }

    private function assertReconstruction(
        array $reconstruction,
        array $decisionAuthority,
        array $target,
        array $input,
        array $successor,
        array $decision,
        array $authority,
        array $adoption,
    ): void {
        $expected = [
            'decision_authority' => $this->reference($decisionAuthority, 'authority_id'),
            'reconciled_target' => $this->reference($target, 'target_id'),
            'reconciled_decision_input' => $this->reference($input, 'decision_input_id'),
            'completed_successor' => $this->reference($successor, 'successor_id'),
            'production_decision' => $this->reference($decision, 'decision_id'),
            'successor_creation_authority' => $this->reference($authority, 'authority_id'),
            'adoption_target' => $this->reference($adoption, 'adoption_target_id'),
            'operation_scope' => $successor['operation_scope'],
            'replay_contention_root' => $successor['replay_contention_root'],
            'required_admission_contract' => $adoption['required_admission_contract'],
        ];
        $proof = [
            'classification' => 'ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE',
            'chain' => $expected,
            'reasons' => [],
        ];
        if ('ELIGIBLE_OFFLINE_PRODUCTION_ADOPTION_EVIDENCE'
                !== ($reconstruction['classification'] ?? null)
            || $expected !== ($reconstruction['chain'] ?? null)
            || [] !== ($reconstruction['reasons'] ?? null)
            || hash('sha256', CanonicalJson::encode($proof))
                !== ($reconstruction['proof_digest'] ?? null)
            || true !== ($reconstruction['read_only'] ?? null)
            || 'NOT_IMPLEMENTED'
                !== ($reconstruction['chain']['required_admission_contract']['status'] ?? null)) {
            throw new \RuntimeException('PBA900_RECONSTRUCTION_NOT_EXACT_ELIGIBLE');
        }

        foreach ([
            'fixture_created', 'fixture_repaired', 'artifact_replaced',
            'artifact_promoted', 'production_decision_created',
            'successor_creation_authority_issued',
            'successor_creation_authority_consumed', 'successor_created',
            'adoption_decided', 'live_adoption_performed',
            'execution_admission_changed', 'provider_binding_activated',
            'credential_or_capability_handled', 'provider_invoked',
            'external_io_started', 'provider_effect_started',
            'retry_authority_created', 'continuing_authority',
        ] as $field) {
            if (false !== ($reconstruction[$field] ?? null)) {
                throw new \RuntimeException('PBA901_RECONSTRUCTION_NON_AUTHORITY_VIOLATED:'.$field);
            }
        }
    }

    private function assertProofs(array $proofs): void
    {
        if (self::REQUIRED_PROOFS !== array_keys($proofs)) {
            throw new \RuntimeException('PBA902_ADVERSARIAL_PROOF_SET_INCOMPLETE');
        }
        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException('PBA903_ADVERSARIAL_PROOF_FAILED');
            }
        }
    }

    private function assertSecretExclusion(array $records): void
    {
        $forbidden = [
            'credential_secret', 'credential_bytes', 'credential_reference',
            'capability_identity', 'capability_bytes', 'api_key', 'access_token',
            'refresh_token', 'password', 'authentication_material',
            'environment_variable', 'process_local_capability',
            'callback_identity', 'object_identity',
        ];
        $walk = function (array $value) use (&$walk, $forbidden): void {
            foreach ($value as $key => $item) {
                foreach ($forbidden as $fragment) {
                    if (str_contains(strtolower((string) $key), $fragment)
                        && false !== $item && null !== $item) {
                        throw new \RuntimeException('PBA904_SECRET_MATERIAL_PRESENT');
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
            'schema' => ProviderBindingSuccessorProductionAdoptionAdversarialAuditResultContract::SCHEMA,
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
            'authority_issued' => false,
            'authority_consumed' => false,
            'successor_created' => false,
            'adoption_decided' => false,
            'live_adoption_performed' => false,
            'execution_admission_changed' => false,
            'binding_activated' => false,
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
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
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
