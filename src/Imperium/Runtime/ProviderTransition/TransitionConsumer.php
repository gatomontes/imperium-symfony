<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** No provider dependency, credential resolution or continuing execution capability. */
final readonly class TransitionConsumer
{
    public function __construct(
        private TransitionStore $store,
        private TransitionAuthority $custody,
    ) {
    }

    /** Trusted orchestration supplies the clock; requests contain only the pinned grant digest. */
    public function execute(string $requestDigest, int $at): array
    {
        return $this->store->locked(function () use ($requestDigest, $at): array {
            $grant = $this->custody->grant();
            $pin = TransitionContract::digest($grant);
            if (!hash_equals($pin, $requestDigest)) { throw new \RuntimeException('EAT_REQUEST_SUBSTITUTION'); }
            $authority = $this->store->read('authority');
            if ($authority !== $this->custody->expected($grant)) { throw new \RuntimeException('EAT_AUTHORITY_LINEAGE_INVALID'); }
            $committed = $this->store->read('commit');
            if (null !== $committed) {
                self::assertCommit($committed, $grant, $authority);
                throw new \RuntimeException('EAT_ALREADY_COMMITTED_READ_ONLY_REPLAY');
            }
            foreach (['journal', 'commit', 'authority', 'refusal'] as $name) {
                if ($this->store->pending($name)) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            }
            if (null !== $this->store->read('journal')) { throw new \RuntimeException('UNKNOWN_REPLAY_PROHIBITED'); }
            if (null !== $this->store->read('refusal')) { throw new \RuntimeException('EAT_TERMINAL_REFUSAL'); }
            try {
                TransitionContract::current($grant, $at);
                $this->custody->assertNotRevoked();
            } catch (\RuntimeException $error) {
                $this->store->put('refusal', ['schema' => TransitionContract::SCHEMA.'/refusal',
                    'grant' => $pin, 'reason' => $error->getMessage(), 'continuing_authority' => false]);
                throw $error;
            }
            $this->store->put('journal', ['schema' => TransitionContract::SCHEMA.'/journal',
                'grant' => $pin, 'root' => TransitionContract::root($grant),
                'authority' => TransitionContract::digest($authority), 'state' => 'PREPARED']);
            // All seven outcomes become visible in one rename. No earlier file consumes authority.
            return $this->store->put('commit', self::aggregate($grant, $authority, $at));
        });
    }

    public static function aggregate(array $grant, array $authority, int $at): array
    {
        $root = TransitionContract::root($grant);
        $consumption = ['authority' => TransitionContract::digest($authority), 'root' => $root,
            'consumer' => TransitionContract::CONSUMER, 'authority_consumed' => true, 'at' => $at];
        $admission = ['schema' => 'imperium.provider-successor-executable-admission/v3', 'root' => $root,
            'successor' => $grant['successor_digest'], 'creation' => $grant['successor_creation'],
            'principal_activation' => $grant['principal_activation'], 'assurance' => $grant['assurance'],
            'execution_boundary' => $grant['execution_boundary'], 'operation' => $grant['operation'],
            'consumption' => TransitionContract::digest($consumption), 'execution_admitted' => true];
        $join = ['successor' => $grant['successor_digest'], 'admission' => TransitionContract::digest($admission),
            'operation' => $grant['operation'], 'adopted' => true];
        $source = ['binding' => $grant['binding_digest'], 'descriptor_status' => 'BOUND_INACTIVE',
            'original_binding_mutated' => false, 'operation' => $grant['operation'], 'superseded_for_operation' => true];
        $successor = ['successor' => $grant['successor_digest'], 'join' => TransitionContract::digest($join),
            'operation' => $grant['operation'], 'status' => 'OPERATION_SCOPED_BINDING_ACTIVE'];
        $records = ['authority_consumption' => $consumption, 'v3_admission' => $admission,
            'adoption_join' => $join, 'source_binding_transition' => $source, 'successor_binding_activation' => $successor];
        $winner = ['root' => $root, 'grant' => TransitionContract::digest($grant),
            'records' => TransitionContract::digest($records), 'at' => $at];
        $records['winner_target'] = $winner;
        $records['receipt_target'] = ['root' => $root, 'winner' => TransitionContract::digest($winner),
            'grant' => TransitionContract::digest($grant), 'outcome' => 'COMMITTED',
            'provider_effect_started' => false, 'continuing_authority' => false];
        return ['schema' => TransitionContract::SCHEMA, 'grant' => TransitionContract::digest($grant),
            'root' => $root, 'at' => $at, 'records' => $records];
    }

    public static function assertCommit(array $commit, array $grant, array $authority): void
    {
        if (!is_int($commit['at'] ?? null)) { throw new \RuntimeException('EAT_COMMIT_INVALID'); }
        TransitionContract::current($grant, $commit['at']);
        if ($commit !== self::aggregate($grant, $authority, $commit['at'])) {
            throw new \RuntimeException('EAT_COMMIT_INVALID');
        }
    }
}
