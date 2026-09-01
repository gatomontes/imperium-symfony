<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor;

/** Pure audit of caller-supplied recovery evidence. No persistence or effect dependency. */
final readonly class ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditService
{
    public const array REQUIRED_PROOFS = [
        'interruption_classifications_proved',
        'exact_replay_convergence_proved',
        'changed_evidence_refusal_proved',
        'same_root_contention_refusal_proved',
        'partial_write_refusal_proved',
        'automatic_repair_refusal_proved',
        'secret_exclusion_proved',
        'non_authority_perimeter_proved',
    ];

    public function __construct(
        private ProviderBindingSuccessorAtomicLiveTransitionReadOnlyAggregateReconstructor $reconstructor,
    ) {
    }

    public function audit(array $plan, array $evidence, array $proofs): array
    {
        $root = $plan['replay_contention_root'] ?? null;

        try {
            $this->assertProofs($proofs);
            $this->assertSecretExclusion([$plan, $evidence]);
            $aggregate = $this->reconstructor->reconstruct($plan, $evidence);
            if (true === ($aggregate['automatic_repair_performed'] ?? null)
                || true === ($aggregate['state_write_performed'] ?? null)
                || true === ($aggregate['authority_action_performed'] ?? null)
                || true === ($aggregate['provider_effect_started'] ?? null)
                || true === ($aggregate['continuing_authority'] ?? null)) {
                throw new \RuntimeException('PBL952_ADVERSARIAL_FALSE_ACTION_CLAIM');
            }
        } catch (\Throwable $error) {
            return $this->result(
                is_string($root) ? $root : null,
                'CONFLICTED',
                ['ADVERSARIAL_CONFLICT:'.$error->getMessage()],
            );
        }

        return $this->result($root, 'PASSED', [
            'RECOVERY_CLASSIFICATION_AND_READ_ONLY_DIRECTIVE_PROVED',
            'REPLAY_CONTENTION_PARTIAL_WRITE_AND_AUTOMATIC_REPAIR_ATTACKS_REFUSED',
            'SECRET_EXCLUSION_AND_NON_AUTHORITY_PERIMETER_PRESERVED',
        ]);
    }

    private function assertProofs(array $proofs): void
    {
        if (self::REQUIRED_PROOFS !== array_keys($proofs)) {
            throw new \RuntimeException('PBL950_ADVERSARIAL_PROOF_SET_INCOMPLETE');
        }
        foreach ($proofs as $proved) {
            if (true !== $proved) {
                throw new \RuntimeException('PBL951_ADVERSARIAL_PROOF_FAILED');
            }
        }
    }

    private function assertSecretExclusion(array $records): void
    {
        $walk = function (array $value) use (&$walk): void {
            foreach ($value as $key => $item) {
                if (is_string($key) && (bool) preg_match(
                    '/(?:credential|secret|api[_-]?key|access[_-]?token|capability_(?:identity|bytes|token)|authentication_material|environment[_-]?variable|process_local_capability|callback_identity|object_identity)/i',
                    $key,
                ) && false !== $item && null !== $item) {
                    throw new \RuntimeException('PBL953_SECRET_OR_CAPABILITY_MATERIAL_PRESENT');
                }
                if (is_array($item)) {
                    $walk($item);
                }
            }
        };
        $walk($records);
    }

    private function result(?string $root, string $classification, array $findings): array
    {
        return [
            'schema' => ProviderBindingSuccessorAtomicLiveTransitionAdversarialAuditResultContract::SCHEMA,
            'classification' => $classification,
            'findings' => $findings,
            'audited_root' => $root,
            'read_only' => true,
            'journal_persisted' => false,
            'live_lock_acquired' => false,
            'state_written_or_repaired' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_state_changed' => false,
            'durable_winner_or_receipt_created' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authorized' => false,
            'live_command_migrated' => false,
            'continuing_authority' => false,
        ];
    }
}
