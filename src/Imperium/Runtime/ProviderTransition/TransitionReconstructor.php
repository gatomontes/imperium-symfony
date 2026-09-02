<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Independent read-only record joins. Never calls the producer or acquires a lock. */
final readonly class TransitionReconstructor
{
    public function __construct(private TransitionStore $store, private string $grantPin)
    {
    }

    public function reconstruct(): array
    {
        try {
            $grant = $this->store->read('grant');
            if (null === $grant) { return $this->result('ABSENT'); }
            TransitionContract::grant($grant, $this->grantPin);
            if ($grant['storage'] !== $this->store->identity()) { throw new \RuntimeException(); }
            $authority = $this->store->read('authority');
            $journal = $this->store->read('journal');
            $commit = $this->store->read('commit');
            $refusal = $this->store->read('refusal');
            foreach (['grant', 'authority', 'journal', 'commit', 'refusal', 'revocation'] as $name) {
                if ($this->store->pending($name)) { return $this->result('UNKNOWN_REPLAY_PROHIBITED'); }
            }
            if (null !== $commit) {
                if (null === $authority || null === $journal || null !== $refusal || null !== $this->store->read('revocation')) {
                    throw new \RuntimeException();
                }
                $this->verify($grant, $authority, $journal, $commit);
                return $this->result('COMMITTED', $commit['records']['receipt_target']);
            }
            if (null !== $refusal) {
                if (null !== $journal || $refusal !== ['schema' => TransitionContract::SCHEMA.'/refusal',
                    'grant' => $this->grantPin, 'reason' => $refusal['reason'] ?? null, 'continuing_authority' => false]
                    || !in_array($refusal['reason'], ['EAT_AUTHORITY_NOT_CURRENT', 'EAT_AUTHORITY_REVOKED'], true)) {
                    throw new \RuntimeException();
                }
                return $this->result('REFUSED');
            }
            if (null !== $journal && (null === $authority || $journal !== [
                'schema' => TransitionContract::SCHEMA.'/journal', 'grant' => $this->grantPin,
                'root' => TransitionContract::root($grant), 'authority' => TransitionContract::digest($authority),
                'state' => 'PREPARED'])) { throw new \RuntimeException(); }
            return $this->result(null !== $journal ? 'INCOMPLETE' : 'ABSENT');
        } catch (\Throwable) {
            return $this->result('UNKNOWN_REPLAY_PROHIBITED');
        }
    }

    private function verify(array $g, array $a, array $j, array $c): void
    {
        $root = TransitionContract::root($g);
        $decision = ['schema' => TransitionContract::SCHEMA.'/decision', 'principal' => $g['principal'],
            'generation' => $g['generation'], 'principal_activation' => $g['principal_activation'],
            'scope' => TransitionContract::SCOPE, 'grant' => $this->grantPin, 'root' => $root,
            'disposition' => 'AUTHORIZED', 'continuing_authority' => false];
        if ($a !== ['schema' => TransitionContract::SCHEMA.'/authority', 'grant' => $this->grantPin,
            'root' => $root, 'decision' => $decision, 'consumer' => TransitionContract::CONSUMER, 'authority_single_use' => true]
            || $j !== ['schema' => TransitionContract::SCHEMA.'/journal', 'grant' => $this->grantPin,
                'root' => $root, 'authority' => TransitionContract::digest($a), 'state' => 'PREPARED']) {
            throw new \RuntimeException();
        }
        TransitionContract::keys($c, ['schema', 'grant', 'root', 'at', 'records']);
        if ($c['schema'] !== TransitionContract::SCHEMA || $c['grant'] !== $this->grantPin
            || $c['root'] !== $root || !is_int($c['at']) || !is_array($c['records'])) { throw new \RuntimeException(); }
        TransitionContract::current($g, $c['at']);
        $r = $c['records'];
        TransitionContract::keys($r, TransitionContract::WRITE_SET);
        $expected = [
            'authority_consumption' => ['authority' => TransitionContract::digest($a), 'root' => $root,
                'consumer' => TransitionContract::CONSUMER, 'authority_consumed' => true, 'at' => $c['at']],
            'v3_admission' => ['schema' => 'imperium.provider-successor-executable-admission/v3', 'root' => $root,
                'successor' => $g['successor_digest'], 'creation' => $g['successor_creation'],
                'principal_activation' => $g['principal_activation'], 'assurance' => $g['assurance'],
                'execution_boundary' => $g['execution_boundary'], 'operation' => $g['operation'],
                'consumption' => TransitionContract::digest($r['authority_consumption']), 'execution_admitted' => true],
            'adoption_join' => ['successor' => $g['successor_digest'], 'admission' => TransitionContract::digest($r['v3_admission']),
                'operation' => $g['operation'], 'adopted' => true],
            'source_binding_transition' => ['binding' => $g['binding_digest'], 'descriptor_status' => 'BOUND_INACTIVE',
                'original_binding_mutated' => false, 'operation' => $g['operation'], 'superseded_for_operation' => true],
            'successor_binding_activation' => ['successor' => $g['successor_digest'], 'join' => TransitionContract::digest($r['adoption_join']),
                'operation' => $g['operation'], 'status' => 'OPERATION_SCOPED_BINDING_ACTIVE'],
        ];
        $winner = ['root' => $root, 'grant' => $this->grantPin, 'records' => TransitionContract::digest($expected), 'at' => $c['at']];
        $expected['winner_target'] = $winner;
        $expected['receipt_target'] = ['root' => $root, 'winner' => TransitionContract::digest($winner),
            'grant' => $this->grantPin, 'outcome' => 'COMMITTED', 'provider_effect_started' => false, 'continuing_authority' => false];
        if ($r !== $expected) { throw new \RuntimeException(); }
    }

    private function result(string $status, ?array $receipt = null): array
    {
        return ['status' => $status, 'receipt' => $receipt, 'read_only' => true,
            'automatic_repair_permitted' => false, 'retry_authorized' => false, 'provider_effect_started' => false];
    }
}
