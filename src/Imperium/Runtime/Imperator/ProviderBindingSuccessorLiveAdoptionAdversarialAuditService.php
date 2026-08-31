<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorLiveAdoptionAggregateReconstructor;

/** Pure audit of caller-supplied live-adoption evidence. */
final readonly class ProviderBindingSuccessorLiveAdoptionAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'before_commit_no_winner_proved',
        'after_commit_one_winner_proved',
        'exact_replay_converged',
        'changed_evidence_conflicted',
        'same_root_contention_proved',
        'expiry_refusal_proved',
        'revocation_refusal_proved',
        'partial_state_exclusion_proved',
        'secret_exclusion_proved',
        'false_v3_refusal_proved',
        'effect_exclusion_proved',
        'non_authority_perimeter_proved',
    ];

    public function __construct(
        private ProviderBindingSuccessorLiveAdoptionAggregateReconstructor $reconstructor =
            new ProviderBindingSuccessorLiveAdoptionAggregateReconstructor(),
    ) {
    }

    public function audit(
        array $boundary,
        array $winnerProof,
        array $aggregate,
        array $lifecycle,
        array $proofs,
        \DateTimeImmutable $at,
    ): array {
        $root = $boundary['replay_contention_root'] ?? null;

        if (!$this->lifecycleEligible($lifecycle, $at)) {
            return $this->result(
                is_string($root) ? $root : null,
                'REFUSED',
                ['LIFECYCLE_EXPIRED_REVOKED_OR_NOT_EFFECTIVE'],
                $at,
            );
        }

        try {
            $this->assertProofs($proofs);
            $this->assertSecretExclusion([$boundary, $winnerProof, $aggregate, $lifecycle]);

            $expected = $this->reconstructor->reconstruct($boundary, $winnerProof);
            if ('EXACT_LIVE_ADOPTION_WINNER' !== $expected['classification']
                || $expected !== $aggregate) {
                throw new \RuntimeException(
                    'PBL610_LIVE_ADOPTION_AGGREGATE_NOT_EXACT',
                );
            }

            foreach ([
                'evidence_created',
                'evidence_repaired',
                'evidence_replaced',
                'authority_issued',
                'authority_consumed',
                'execution_admitted',
                'successor_adopted',
                'binding_transitioned',
                'credential_or_capability_handled',
                'provider_invoked',
                'external_io_started',
                'provider_effect_started',
                'retry_authority_created',
                'continuing_authority',
            ] as $field) {
                if (false !== ($aggregate[$field] ?? null)) {
                    throw new \RuntimeException(
                        'PBL611_FALSE_LIVE_TRANSITION_OR_EFFECT_CLAIM',
                    );
                }
            }

            if (false !== ($boundary['partial_record_created'] ?? null)
                || false !== ($boundary['effect_started'] ?? null)
                || false !== ($boundary['authority_consumed'] ?? null)
                || false !== ($boundary['execution_admitted'] ?? null)
                || false !== ($boundary['successor_adopted'] ?? null)
                || false !== ($boundary['binding_transitioned'] ?? null)) {
                throw new \RuntimeException(
                    'PBL612_FALSE_BOUNDARY_STATE_CLAIM',
                );
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
                'CHANGED_EVIDENCE_AND_SAME_ROOT_CONTENTION_CONFLICT_PROVED',
                'EXPIRY_REVOCATION_PARTIAL_STATE_AND_SECRET_ATTACKS_REFUSED',
                'FALSE_V3_EFFECT_AND_LIVE_TRANSITION_CLAIMS_REFUSED',
                'NON_AUTHORITY_PERIMETER_PRESERVED',
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
            throw new \RuntimeException(
                'PBL601_LIVE_ADOPTION_ADVERSARIAL_PROOF_SET_INCOMPLETE',
            );
        }

        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException(
                    'PBL602_LIVE_ADOPTION_ADVERSARIAL_PROOF_FAILED',
                );
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
                    throw new \RuntimeException(
                        'PBL603_LIVE_ADOPTION_SECRET_MATERIAL_PRESENT',
                    );
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
                ProviderBindingSuccessorLiveAdoptionAdversarialAuditResultContract::SCHEMA,
            'classification' => $classification,
            'findings' => $findings,
            'audited_root' => $root,
            'audited_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'artifact_replaced' => false,
            'decision_performed' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_transitioned' => false,
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
