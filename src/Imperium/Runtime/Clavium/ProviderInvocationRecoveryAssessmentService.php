<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderInvocationRecoveryAssessmentService
{
    private const CLAIMS = 'var/imperium/runtime/provider-invocations';
    private const JOURNAL = 'var/imperium/runtime/provider-invocation-journal';
    private const TURNS = 'var/imperium/operational/delegate-mission-bounded-cognition-turns';

    private ImmutableRecordStore $records;
    private MutableStateStore $state;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $root,
        ?AtomicTransition $atomic = null,
        ?ImmutableRecordStore $records = null,
        ?MutableStateStore $state = null,
    ) {
        $atomic ??= new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $atomic);
        $this->state = $state ?? new MutableStateStore($root, $atomic);
    }

    public function assess(string $claimId): array
    {
        if (!preg_match('/^provider-invocation-[a-f0-9]{20}$/', $claimId)) {
            throw new \RuntimeException('CLV420_PROVIDER_RECOVERY_CLAIM_INVALID');
        }
        try {
            $claim = $this->records->read(self::CLAIMS, $claimId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('CLV420_PROVIDER_RECOVERY_CLAIM_INVALID');
        }

        $turn = $this->completedTurn($claimId, $claim['record_digest']);
        if (null !== $turn) {
            return $this->result($claim, 'TURN_PERSISTED_NO_RECOVERY_REQUIRED', false, false, $turn['turn_id']);
        }

        try {
            $journal = $this->state->read(self::JOURNAL.'/'.$claimId.'.json');
        } catch (\RuntimeException $exception) {
            if ('PST122_MUTABLE_STATE_ABSENT' === $exception->getMessage()) {
                return $this->result($claim, 'CLAIMED_WITHOUT_JOURNAL_GOVERNED_RESOLUTION_REQUIRED', true, false);
            }
            throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID', 0, $exception);
        }
        if (($journal['claim']['digest'] ?? null) !== $claim['record_digest']) {
            throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID');
        }

        return match ($journal['status'] ?? null) {
            'INVOCATION_IN_FLIGHT' => $this->result($claim, 'PROVIDER_OUTCOME_UNKNOWN_GOVERNED_RESOLUTION_REQUIRED', true, true),
            'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING' => $this->result($claim, 'RESPONSE_RECEIVED_TURN_PERSISTENCE_RECOVERY_REQUIRED', true, false),
            'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED' => $this->result($claim, 'UNKNOWN_OUTCOME_RECORDED_GOVERNED_RESOLUTION_REQUIRED', true, true),
            'INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED' => $this->result($claim, 'PRE_IO_FAILURE_RECORDED_FRESH_AUTHORIZATION_REQUIRED', true, false),
            default => throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID'),
        };
    }

    private function completedTurn(string $claimId, string $claimDigest): ?array
    {
        $directory = $this->root.'/'.self::TURNS;
        foreach (glob($directory.'/*.json') ?: [] as $path) {
            $id = basename($path, '.json');
            try {
                $turn = $this->records->read(self::TURNS, $id);
            } catch (\RuntimeException $exception) {
                throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID', 0, $exception);
            }
            if (($turn['source_invocation_claim']['id'] ?? null) === $claimId) {
                if (($turn['source_invocation_claim']['digest'] ?? null) !== $claimDigest) {
                    throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID');
                }

                return $turn;
            }
        }

        return null;
    }

    private function result(array $claim, string $status, bool $resolutionRequired, bool $outcomeMayBeUnknown, ?string $turnId = null): array
    {
        return [
            'claim' => ['id' => $claim['claim_id'], 'digest' => $claim['record_digest']],
            'turn_id' => $turnId,
            'status' => $status,
            'automatic_replay_permitted' => false,
            'governed_resolution_required' => $resolutionRequired,
            'provider_outcome_may_be_unknown' => $outcomeMayBeUnknown,
        ];
    }
}
