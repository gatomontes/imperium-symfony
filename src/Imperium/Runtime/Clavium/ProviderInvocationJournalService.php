<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderInvocationJournalService
{
    private const DELEGATE_CLAIMS = 'var/imperium/runtime/provider-invocations';
    private const OPERATIONAL_CLAIMS = 'var/imperium/runtime/operational-cognition-invocation-claims';
    private const JOURNAL = 'var/imperium/runtime/provider-invocation-journal';

    private MutableStateStore $state;
    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $root,
        ?AtomicTransition $atomic = null,
        ?MutableStateStore $state = null,
        ?ImmutableRecordStore $records = null,
    ) {
        $atomic ??= new AtomicTransition($root);
        $this->state = $state ?? new MutableStateStore($root, $atomic);
        $this->records = $records ?? new ImmutableRecordStore($root, $atomic);
    }

    public function start(array $claim, \DateTimeImmutable $at): array
    {
        $authoritative = $this->authoritativeClaim($claim);

        try {
            return $this->state->compareAndSwap($this->path($authoritative['claim_id']), null, [
                'schema' => 'imperium.clavium-provider-invocation-journal/v1',
                'claim' => ['id' => $authoritative['claim_id'], 'digest' => $authoritative['record_digest']],
                'idempotency_key' => $this->idempotencyKey($authoritative),
                'external_io_started' => true,
                'provider_response_identity' => null,
                'started_at' => $at->format(DATE_ATOM),
                'resolved_at' => null,
                'status' => 'INVOCATION_IN_FLIGHT',
                'automatic_replay_permitted' => false,
                'sealed' => true,
            ]);
        } catch (\RuntimeException $exception) {
            throw $this->translatePersistenceFailure($exception, 'CLV412_PROVIDER_INVOCATION_ALREADY_STARTED');
        }
    }

    public function markPreIoFailure(array $claim, string $failureCode, \DateTimeImmutable $at): array
    {
        $authoritative = $this->authoritativeClaim($claim);
        if (!preg_match('/^[A-Z][A-Z0-9_]{2,80}$/', $failureCode)) {
            throw new \RuntimeException('CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID');
        }

        try {
            return $this->state->compareAndSwap($this->path($authoritative['claim_id']), null, [
                'schema' => 'imperium.clavium-provider-invocation-journal/v1',
                'claim' => ['id' => $authoritative['claim_id'], 'digest' => $authoritative['record_digest']],
                'idempotency_key' => $this->idempotencyKey($authoritative),
                'external_io_started' => false,
                'provider_response_identity' => null,
                'failure_code' => $failureCode,
                'started_at' => null,
                'resolved_at' => $at->format(DATE_ATOM),
                'status' => 'INVOCATION_FAILED_PRE_IO_REPLAY_PROHIBITED',
                'automatic_replay_permitted' => false,
                'sealed' => true,
            ]);
        } catch (\RuntimeException $exception) {
            throw $this->translatePersistenceFailure($exception, 'CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID');
        }
    }

    public function sealResponse(array $claim, string $response, \DateTimeImmutable $at): array
    {
        return $this->transition($claim, 'INVOCATION_IN_FLIGHT', function (array $record) use ($response, $at): array {
            $record['provider_response_identity'] = 'sha256:'.hash('sha256', $response);
            $record['resolved_at'] = $at->format(DATE_ATOM);
            $record['status'] = 'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING';

            return $record;
        });
    }

    public function markUnknown(array $claim, \DateTimeImmutable $at): array
    {
        return $this->transition($claim, 'INVOCATION_IN_FLIGHT', function (array $record) use ($at): array {
            $record['resolved_at'] = $at->format(DATE_ATOM);
            $record['status'] = 'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED';

            return $record;
        });
    }

    private function transition(array $claim, string $expected, callable $change): array
    {
        $authoritative = $this->authoritativeClaim($claim);
        $path = $this->path($authoritative['claim_id']);
        try {
            $record = $this->state->read($path);
        } catch (\RuntimeException $exception) {
            throw $this->translatePersistenceFailure($exception, 'CLV413_PROVIDER_INVOCATION_JOURNAL_ABSENT');
        }
        if ($expected !== ($record['status'] ?? null)
            || ($record['claim']['digest'] ?? null) !== $authoritative['record_digest']) {
            throw new \RuntimeException('CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID');
        }
        $expectedDigest = $record['record_digest'];
        unset($record['record_digest']);

        try {
            return $this->state->compareAndSwap($path, $expectedDigest, $change($record));
        } catch (\RuntimeException $exception) {
            throw $this->translatePersistenceFailure($exception, 'CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID');
        }
    }

    private function authoritativeClaim(array $claim): array
    {
        $id = $claim['claim_id'] ?? null;
        if (!is_string($id)) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }
        $operational = (bool) preg_match('/^operational-cognition-invocation-claim-[a-f0-9]{20}$/', $id);
        $delegate = (bool) preg_match('/^provider-invocation-[a-f0-9]{20}$/', $id);
        if (!$operational && !$delegate) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }
        try {
            $authoritative = $this->records->read($operational ? self::OPERATIONAL_CLAIMS : self::DELEGATE_CLAIMS, $id);
        } catch (\RuntimeException) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }
        $expectedStatus = $operational
            ? 'OPERATIONAL_INVOCATION_CLAIMED_DURABLE_PRE_IO'
            : 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO';
        $authority = $operational ? 'cognition_authority_consumption' : 'turn_authority_consumption';
        if (CanonicalJson::encode($claim) !== CanonicalJson::encode($authoritative)
            || $expectedStatus !== ($authoritative['status'] ?? null)
            || true !== ($authoritative['lease_consumption']['consumed'] ?? null)
            || true !== ($authoritative[$authority]['consumed'] ?? null)
            || false !== ($authoritative['provider_request']['external_io_started'] ?? null)
            || false !== ($authoritative['recovery']['automatic_replay_permitted'] ?? null)) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }

        return $authoritative;
    }

    private function idempotencyKey(array $claim): string
    {
        $key = $claim['provider_request']['idempotency_key']
            ?? $claim['provider_request']['idempotency_identity']
            ?? null;
        if (!is_string($key) || '' === $key) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }

        return $key;
    }

    private function path(string $claimId): string
    {
        return self::JOURNAL.'/'.$claimId.'.json';
    }

    private function translatePersistenceFailure(\RuntimeException $exception, string $conflict): \RuntimeException
    {
        return match ($exception->getMessage()) {
            'PST121_MUTABLE_STATE_COMPARE_AND_SWAP_CONFLICT' => new \RuntimeException($conflict, 0, $exception),
            'PST122_MUTABLE_STATE_ABSENT' => new \RuntimeException($conflict, 0, $exception),
            'PST123_MUTABLE_STATE_TAMPERED' => new \RuntimeException('CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID', 0, $exception),
            'PST124_MUTABLE_STATE_COMMIT_FAILED' => new \RuntimeException('CLV415_PROVIDER_INVOCATION_JOURNAL_STORAGE_FAILED', 0, $exception),
            default => $exception,
        };
    }
}
