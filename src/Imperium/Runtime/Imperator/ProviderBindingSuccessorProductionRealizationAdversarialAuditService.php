<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\LaCortine\GovernedProviderExecutionSuccessorAdmissionV3Contract;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAdoptionBoundaryContractValidator;

/** Pure audit of caller-supplied boundary evidence. No persistence or effect dependency. */
final readonly class ProviderBindingSuccessorProductionRealizationAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'before_commit_no_winner_proved',
        'after_commit_one_winner_proved',
        'exact_replay_converged',
        'changed_evidence_conflicted',
        'same_root_contention_proved',
        'expiry_refusal_proved',
        'revocation_refusal_proved',
        'secret_exclusion_proved',
        'v3_not_implemented_proved',
        'non_authority_perimeter_proved',
    ];

    public function audit(
        array $decision,
        array $join,
        array $successor,
        array $adoptionTarget,
        array $v3Admission,
        array $lifecycle,
        array $proofs,
        \DateTimeImmutable $at,
    ): array {
        $root = $decision['replay_contention_root'] ?? null;

        if (!$this->lifecycleEligible($lifecycle, $at)) {
            return $this->result(
                is_string($root) ? $root : null,
                'REFUSED',
                ['LIFECYCLE_EXPIRED_REVOKED_OR_NOT_EFFECTIVE'],
                $at,
            );
        }

        try {
            (new ProviderBindingSuccessorAdoptionBoundaryContractValidator())
                ->assertExactChain(
                    $decision,
                    $join,
                    $successor,
                    $adoptionTarget,
                    $v3Admission,
                );
            $this->assertProofs($proofs);
            $this->assertSecretExclusion([
                $decision,
                $join,
                $successor,
                $adoptionTarget,
                $v3Admission,
                $lifecycle,
            ]);

            if (GovernedProviderExecutionSuccessorAdmissionV3Contract::STATUS
                    !== ($v3Admission['status'] ?? null)
                || false !== ($v3Admission['execution_admitted'] ?? null)
                || false !== ($v3Admission['live_adoption_performed'] ?? null)
                || false !== ($decision['decision_performed'] ?? null)
                || false !== ($join['join_performed'] ?? null)) {
                throw new \RuntimeException('PBR600_FALSE_PRODUCTION_CLAIM');
            }
        } catch (\Throwable $error) {
            return $this->result(
                is_string($root) ? $root : null,
                'CONFLICTED',
                ['ADVERSARIAL_CONFLICT:'.$error->getMessage()],
                $at,
            );
        }

        return $this->result(
            $root,
            'PASSED',
            [
                'INTERRUPTION_CUTS_AND_EXACT_REPLAY_PROVED',
                'SAME_ROOT_CONTENTION_AND_CHANGED_EVIDENCE_CONFLICT_PROVED',
                'EXPIRY_REVOCATION_AND_SECRET_ATTACKS_REFUSED',
                'V3_REMAINS_NOT_IMPLEMENTED_AND_NON_AUTHORITY_PRESERVED',
            ],
            $at,
        );
    }

    private function lifecycleEligible(array $lifecycle, \DateTimeImmutable $at): bool
    {
        if (['effective_at', 'expires_at', 'revocation_reference']
            !== array_keys($lifecycle)
            || !$this->date($lifecycle['effective_at'] ?? null)
            || !$this->date($lifecycle['expires_at'] ?? null)) {
            return false;
        }

        return new \DateTimeImmutable($lifecycle['effective_at']) <= $at
            && $at < new \DateTimeImmutable($lifecycle['expires_at'])
            && null === $lifecycle['revocation_reference'];
    }

    private function assertProofs(array $proofs): void
    {
        if (self::REQUIRED_PROOFS !== array_keys($proofs)) {
            throw new \RuntimeException('PBR601_ADVERSARIAL_PROOF_SET_INCOMPLETE');
        }

        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException('PBR602_ADVERSARIAL_PROOF_FAILED');
            }
        }
    }

    private function assertSecretExclusion(array $records): void
    {
        $walk = function (array $value) use (&$walk): void {
            foreach ($value as $key => $item) {
                if (is_string($key) && (bool) preg_match(
                    '/(?:credential_(?:bytes|reference|secret|token)|capability_(?:identity|bytes|token)|api[_-]?key|access[_-]?token|authentication_material|environment[_-]?variable|process_local_capability|callback_identity|object_identity)/i',
                    $key,
                ) && false !== $item && null !== $item) {
                    throw new \RuntimeException('PBR603_SECRET_MATERIAL_PRESENT');
                }

                if (is_array($item)) {
                    $walk($item);
                }
            }
        };

        $walk($records);
    }

    private function result(
        ?string $root,
        string $classification,
        array $findings,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' =>
                ProviderBindingSuccessorProductionRealizationAdversarialAuditResultContract::SCHEMA,
            'classification' => $classification,
            'findings' => $findings,
            'audited_root' => $root,
            'audited_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'artifact_promoted' => false,
            'decision_performed' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'successor_created' => false,
            'adoption_decided' => false,
            'join_performed' => false,
            'execution_admitted' => false,
            'live_adoption_performed' => false,
            'binding_activated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authority_created' => false,
            'continuing_authority' => false,
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
