<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class ProviderBindingSuccessorAtomicCreationInertSeam
{
    public function __construct(
        private ProviderBindingSuccessorAtomicCreationWinnerBoundaryValidator $validator =
            new ProviderBindingSuccessorAtomicCreationWinnerBoundaryValidator(),
    ) {
    }

    public function inspect(array $boundary): array
    {
        $this->validator->assert($boundary);

        return [
            'classification' => 'READY_INERT_ATOMIC_BOUNDARY',
            'winner_boundary_id' => $boundary['winner_boundary_id'],
            'replay_contention_root' => $boundary['replay_contention_root'],
            'lock_kind' => $boundary['lock_kind'],
            'consumption_and_creation_atomic' => true,
            'authority_consumed' => false,
            'successor_created' => false,
            'partial_record_created' => false,
            'effect_started' => false,
            'continuing_authority' => false,
        ];
    }
}
