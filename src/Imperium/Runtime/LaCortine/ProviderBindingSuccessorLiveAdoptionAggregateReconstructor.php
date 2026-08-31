<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final readonly class ProviderBindingSuccessorLiveAdoptionAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'ABSENT',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
        'EXACT_LIVE_ADOPTION_WINNER',
    ];

    public function __construct(
        private ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator $validator =
            new ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator(),
        private ProviderBindingSuccessorLiveAdoptionInterruptionProofService $proof =
            new ProviderBindingSuccessorLiveAdoptionInterruptionProofService(),
    ) {
    }

    public function reconstruct(?array $boundary, ?array $winnerProof): array
    {
        if (null === $boundary && null === $winnerProof) {
            return $this->result('ABSENT', [], ['WINNER_EVIDENCE_ABSENT']);
        }

        if (null === $boundary || null === $winnerProof) {
            return $this->result('INCOMPLETE', [], ['WINNER_EVIDENCE_INCOMPLETE']);
        }

        try {
            $this->validator->assert($boundary);
        } catch (\RuntimeException|\InvalidArgumentException $error) {
            return $this->result('REFUSED', [], [$error->getMessage()]);
        }

        if ('INTERRUPTED_BEFORE_COMMIT_NO_WINNER'
                === ($winnerProof['classification'] ?? null)
            && 0 === ($winnerProof['winner_count'] ?? null)
            && false === ($winnerProof['immutable_commit_observed'] ?? null)) {
            return $this->result(
                'INCOMPLETE',
                [],
                ['IMMUTABLE_WINNER_NOT_COMMITTED'],
            );
        }

        try {
            $this->proof->replay($winnerProof, $boundary);
        } catch (\RuntimeException|\InvalidArgumentException $error) {
            return $this->result('CONFLICTED', [], [$error->getMessage()]);
        }

        return $this->result(
            'EXACT_LIVE_ADOPTION_WINNER',
            [
                'winner_boundary' => [
                    'id' => $boundary['winner_boundary_id'],
                    'digest' => $boundary['record_digest'],
                    'schema' => $boundary['schema'],
                ],
                'adoption_decision' => $boundary['adoption_decision'],
                'authority_source' => $boundary['authority_source'],
                'custody_source' => $boundary['custody_source'],
                'completed_successor' => $boundary['completed_successor'],
                'v3_admission' => $boundary['v3_admission'],
                'adoption_join' => $boundary['adoption_join'],
                'original_binding' => $boundary['original_binding'],
                'successor_binding_target' => $boundary['successor_binding_target'],
                'replay_contention_root' => $boundary['replay_contention_root'],
                'winner_proof_digest' => $winnerProof['proof_digest'],
            ],
            [],
        );
    }

    private function result(string $classification, array $chain, array $reasons): array
    {
        $aggregate = [
            'classification' => $classification,
            'chain' => $chain,
            'reasons' => $reasons,
        ];

        return [
            ...$aggregate,
            'aggregate_digest' => hash('sha256', CanonicalJson::encode($aggregate)),
            'read_only' => true,
            'evidence_created' => false,
            'evidence_repaired' => false,
            'evidence_replaced' => false,
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
}
