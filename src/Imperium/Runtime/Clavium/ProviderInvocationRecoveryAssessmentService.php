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
    private const RESPONSES = 'var/imperium/runtime/provider-response-envelopes';

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
            'INVOCATION_IN_FLIGHT' => $this->inFlightResult($claim),
            'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING' => $this->responseResult($claim, $journal),
            'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED' => $this->result($claim, 'UNKNOWN_OUTCOME_RECORDED_GOVERNED_RESOLUTION_REQUIRED', true, true),
            'INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED' => $this->result($claim, 'PRE_IO_FAILURE_RECORDED_FRESH_AUTHORIZATION_REQUIRED', true, false),
            default => throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID'),
        };
    }

    private function inFlightResult(array $claim): array
    {
        try {
            $envelope = $this->records->read(self::RESPONSES, $claim['claim_id']);
        } catch (\RuntimeException) {
            return $this->result($claim, 'PROVIDER_OUTCOME_UNKNOWN_GOVERNED_RESOLUTION_REQUIRED', true, true);
        }
        $this->assertEnvelope($claim, $envelope);

        return $this->result($claim, 'RESPONSE_ENVELOPE_SEALED_PENDING_JOURNAL_AND_TURN_RECOVERY', true, false);
    }

    private function responseResult(array $claim, array $journal): array
    {
        try {
            $envelope = $this->records->read(self::RESPONSES, $claim['claim_id']);
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('CLV422_PROVIDER_RESPONSE_ENVELOPE_ABSENT', 0, $exception);
        }
        $this->assertEnvelope($claim, $envelope);
        if (($journal['provider_response_identity'] ?? null) !== $envelope['provider_response_identity']) {
            throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID');
        }

        return $this->result($claim, 'RESPONSE_ENVELOPE_AVAILABLE_FOR_TURN_PERSISTENCE_RECOVERY', true, false);
    }

    private function assertEnvelope(array $claim, array $envelope): void
    {
        if (($envelope['claim']['digest'] ?? null) !== $claim['record_digest']
            || false !== ($envelope['automatic_provider_replay_permitted'] ?? null)
            || true === ($envelope['credential_material_present'] ?? null)) {
            throw new \RuntimeException('CLV421_PROVIDER_RECOVERY_EVIDENCE_INVALID');
        }
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
