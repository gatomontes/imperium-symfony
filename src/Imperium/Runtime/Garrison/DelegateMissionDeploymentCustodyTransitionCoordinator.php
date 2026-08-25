<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\MutableStateStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionDeploymentCustodyTransitionCoordinator
{
    private const TRANSACTIONS = 'var/imperium/runtime/delegate-mission-deployment-custody-transitions';
    private const TRANSITIONS = 'var/imperium/offices/garrison/delegate-mission-operational-custody-transitions';

    private AtomicTransition $atomic;
    private ImmutableRecordStore $records;
    private MutableStateStore $state;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?AtomicTransition $atomic = null,
        ?ImmutableRecordStore $records = null,
        ?MutableStateStore $state = null,
        private ?DeploymentCustodyTransitionFaultInjector $faults = null,
    ) {
        $this->atomic = $atomic ?? new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $this->atomic);
        $this->state = $state ?? new MutableStateStore($root, $this->atomic);
    }

    public function resumeForAuthorization(string $authorizationId): ?array
    {
        foreach (glob($this->root.'/'.self::TRANSACTIONS.'/*.json') ?: [] as $file) {
            $transaction = $this->state->read(self::TRANSACTIONS.'/'.basename($file));
            if (($transaction['authorization_id'] ?? null) === $authorizationId) {
                return $this->resume($transaction);
            }
        }

        return null;
    }

    public function run(
        string $authorizationId,
        string $transitionId,
        array $transition,
        array $priorCustody,
        array $deployedCustody,
    ): array {
        return $this->atomic->run('delegate-deployment-custody:'.hash('sha256', $authorizationId), function () use ($authorizationId, $transitionId, $transition, $priorCustody, $deployedCustody): array {
            $path = self::TRANSACTIONS.'/'.$transitionId.'.json';
            $inputs = compact('authorizationId', 'transitionId', 'transition', 'priorCustody', 'deployedCustody');
            try {
                $transaction = $this->state->read($path);
                ReplayFingerprint::requireMatch($transaction['replay_fingerprint'] ?? null, $inputs, 'GA249_DELEGATE_MISSION_CUSTODY_CONFLICT');
            } catch (\RuntimeException $exception) {
                if ('PST122_MUTABLE_STATE_ABSENT' !== $exception->getMessage()) {
                    throw $exception;
                }
                $transaction = $this->state->compareAndSwap($path, null, [
                    'schema' => 'imperium.garrison-delegate-mission-deployment-custody-transaction/v1',
                    'transition_id' => $transitionId,
                    'authorization_id' => $authorizationId,
                    'replay_fingerprint' => ReplayFingerprint::of($inputs),
                    'prior_custody' => $priorCustody,
                    'deployed_custody' => $deployedCustody,
                    'transition' => $transition,
                    'checkpoint' => 'PREPARED',
                    'automatic_rollback_permitted' => false,
                ]);
                $this->faults?->after('PREPARED');
            }
            if ('PREPARED' === $transaction['checkpoint']) {
                $this->advanceCustody($priorCustody, $deployedCustody);
                $transaction = $this->checkpoint($path, $transaction, 'CUSTODY_DEPLOYED');
                $this->faults?->after('CUSTODY_DEPLOYED');
            }
            if ('CUSTODY_DEPLOYED' === $transaction['checkpoint']) {
                $record = $this->records->put(self::TRANSITIONS, $transitionId, $transition);
                $transaction = $this->checkpoint($path, $transaction, 'TRANSITION_RECORDED');
                $this->faults?->after('TRANSITION_RECORDED');
            } else {
                $record = $this->records->read(self::TRANSITIONS, $transitionId);
            }
            if ('TRANSITION_RECORDED' === $transaction['checkpoint']) {
                $this->checkpoint($path, $transaction, 'COMPLETE');
                $this->faults?->after('COMPLETE');
            }

            return $record;
        });
    }

    private function advanceCustody(array $prior, array $next): void
    {
        $path = 'var/imperium/offices/garrison/custody/'.$next['custody_id'].'.json';
        $current = $this->state->read($path);
        if ($current === $next) {
            return;
        }
        if ($current !== $prior) {
            throw new \RuntimeException('GA249_DELEGATE_MISSION_CUSTODY_CONFLICT');
        }
        $digest = $prior['record_digest'];
        unset($next['record_digest']);
        $this->state->compareAndSwap($path, $digest, $next);
    }

    private function checkpoint(string $path, array $transaction, string $checkpoint): array
    {
        $digest = $transaction['record_digest'];
        unset($transaction['record_digest']);
        $transaction['checkpoint'] = $checkpoint;

        return $this->state->compareAndSwap($path, $digest, $transaction);
    }

    private function resume(array $transaction): array
    {
        return $this->run(
            $transaction['authorization_id'],
            $transaction['transition_id'],
            $transaction['transition'],
            $transaction['prior_custody'],
            $transaction['deployed_custody'],
        );
    }
}
