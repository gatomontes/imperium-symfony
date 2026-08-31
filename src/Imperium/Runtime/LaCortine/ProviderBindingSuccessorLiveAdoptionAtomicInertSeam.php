<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderBindingSuccessorLiveAdoptionAtomicInertSeam
{
    public function __construct(
        private ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator $validator =
            new ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator(),
    ) {
    }

    public function inspect(array $boundary): array
    {
        $this->validator->assert($boundary);

        return [
            'classification' => 'READY_INERT_LIVE_ADOPTION_ATOMIC_BOUNDARY',
            'winner_boundary_id' => $boundary['winner_boundary_id'],
            'replay_contention_root' => $boundary['replay_contention_root'],
            'lock_kind' => $boundary['lock_kind'],
            'admission_consumption_adoption_and_binding_atomic' => true,
            'authority_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_transitioned' => false,
            'partial_record_created' => false,
            'effect_started' => false,
            'continuing_authority' => false,
        ];
    }
}
