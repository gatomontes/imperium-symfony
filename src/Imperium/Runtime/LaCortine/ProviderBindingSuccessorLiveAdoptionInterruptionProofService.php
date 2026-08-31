<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final readonly class ProviderBindingSuccessorLiveAdoptionInterruptionProofService
{
    public const string CUT_BEFORE_COMMIT = 'BEFORE_IMMUTABLE_COMMIT';
    public const string CUT_AFTER_COMMIT = 'AFTER_IMMUTABLE_COMMIT';

    public function __construct(
        private ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator $validator =
            new ProviderBindingSuccessorLiveAdoptionAtomicWinnerBoundaryValidator(),
    ) {
    }

    public function prove(
        array $boundary,
        array $lifecycle,
        \DateTimeImmutable $at,
        string $cut,
    ): array {
        $this->assertLifecycle($lifecycle, $at);
        $this->validator->assert($boundary);

        if (self::CUT_BEFORE_COMMIT === $cut) {
            return [
                'classification' => 'INTERRUPTED_BEFORE_COMMIT_NO_WINNER',
                'replay_contention_root' => $boundary['replay_contention_root'],
                'boundary_digest' => $boundary['record_digest'],
                'winner_count' => 0,
                'immutable_commit_observed' => false,
                'partial_record_created' => false,
                'authority_consumed' => false,
                'execution_admitted' => false,
                'successor_adopted' => false,
                'binding_transitioned' => false,
                'effect_started' => false,
            ];
        }

        if (self::CUT_AFTER_COMMIT !== $cut) {
            throw new \InvalidArgumentException(
                'PBL400_LIVE_ADOPTION_INTERRUPTION_CUT_INVALID',
            );
        }

        $proof = [
            'classification' => 'INTERRUPTED_AFTER_COMMIT_ONE_WINNER',
            'replay_contention_root' => $boundary['replay_contention_root'],
            'boundary_digest' => $boundary['record_digest'],
            'winner_count' => 1,
            'immutable_commit_observed' => true,
            'partial_record_created' => false,
            'authority_consumed' => true,
            'execution_admitted' => true,
            'successor_adopted' => true,
            'binding_transitioned' => true,
            'effect_started' => false,
        ];
        $proof['proof_digest'] = hash('sha256', CanonicalJson::encode($proof));

        return $proof;
    }

    public function replay(array $prior, array $boundary): array
    {
        $this->validator->assert($boundary);

        if ('INTERRUPTED_AFTER_COMMIT_ONE_WINNER' !== ($prior['classification'] ?? null)
            || 1 !== ($prior['winner_count'] ?? null)
            || true !== ($prior['immutable_commit_observed'] ?? null)
            || $prior['replay_contention_root'] !== $boundary['replay_contention_root']
            || $prior['boundary_digest'] !== $boundary['record_digest']) {
            throw new \RuntimeException(
                'PBL410_LIVE_ADOPTION_SAME_ROOT_CONTENTION_CONFLICT',
            );
        }

        $plain = $prior;
        $digest = $plain['proof_digest'] ?? null;
        unset($plain['proof_digest']);
        if (!is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException(
                'PBL411_LIVE_ADOPTION_PROOF_TAMPERED',
            );
        }

        return $prior;
    }

    private function assertLifecycle(array $lifecycle, \DateTimeImmutable $at): void
    {
        if (['effective_at', 'expires_at', 'revocation_reference']
                !== array_keys($lifecycle)
            || !is_string($lifecycle['effective_at'])
            || !is_string($lifecycle['expires_at'])
            || new \DateTimeImmutable($lifecycle['effective_at']) > $at
            || $at >= new \DateTimeImmutable($lifecycle['expires_at'])
            || null !== $lifecycle['revocation_reference']) {
            throw new \RuntimeException(
                'PBL420_LIVE_ADOPTION_AUTHORITY_EXPIRED_OR_REVOKED',
            );
        }
    }
}
