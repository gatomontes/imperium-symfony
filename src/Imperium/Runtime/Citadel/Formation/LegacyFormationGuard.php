<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

use App\Imperium\Runtime\SystemClock;

/** No schema-name, missing-origin, timestamp or development-actor exemption.
 * Historical imports are deployment-owned exact-byte inventories. They preserve
 * original authority limits and confer no Citadel understanding or drafting grant.
 */
final readonly class LegacyFormationGuard
{
    private FormationJournal $journal;

    public function __construct(string $root) { $this->journal = new FormationJournal($root); }

    /** Installation/migration operation; deliberately not a normal command.
     * The owner must review an exact inventory, not auto-grandfather directory contents.
     */
    public function registerHistoricalInventory(array $inventory, array $decision): void
    {
        $this->journal->change(function (array &$state) use ($inventory, $decision): void {
            (new FormationSignatures($this->journal, new SystemClock()))->verify($state, $decision, 'REGISTER_HISTORICAL_FORMATION_EVIDENCE', $inventory);
            if (!FormationJournal::keys($inventory, ['source', 'records']) || !is_string($inventory['source'])
                || '' === trim($inventory['source']) || !is_array($inventory['records']) || [] === $inventory['records']) {
                throw new \RuntimeException('CMF110_HISTORICAL_INVENTORY_INVALID');
            }
            foreach ($inventory['records'] as $record) {
                if (!FormationJournal::keys($record, ['kind', 'digest']) || !in_array($record['kind'], ['proceeding', 'turn', 'dossier', 'protected-input'], true)
                    || !preg_match('/^[a-f0-9]{64}$/D', $record['digest'])) { throw new \RuntimeException('CMF110_HISTORICAL_INVENTORY_INVALID'); }
                $state['historical_inventory'][$record['kind']][$record['digest']] = ['inventory' => $inventory, 'decision' => $decision];
            }
        });
    }

    public function requireHistorical(array $record, string $kind): void
    {
        $state = $this->journal->read()['state'];
        $entry = $state['historical_inventory'][$kind][FormationJournal::digest($record)] ?? null;
        if (!is_array($entry)) { throw new \RuntimeException('CMF111_FRESH_INPUT_REQUIRES_CITADEL_FORMATION'); }
        // Validate enrollment authenticity at its issuance time. Reading a historical
        // object after expiry does not revive its authority or its signing key.
        $at = $entry['decision']['payload']['issued_at'];
        $clock = new class($at) implements \App\Imperium\Runtime\Clock {
            public function __construct(private int $at) {}
            public function now(): \DateTimeImmutable { return new \DateTimeImmutable('@'.$this->at); }
        };
        (new FormationSignatures($this->journal, $clock))->verify($state, $entry['decision'], 'REGISTER_HISTORICAL_FORMATION_EVIDENCE', $entry['inventory']);
    }

    /** Reconstruct only the deterministic legacy view of an explicitly imported
     * protected input. This carries its original inventory proof into temporary
     * scratch; it cannot import another mission or enlarge its disclosed terms.
     */
    public function bindProtectedScratch(string $scratch, array $mission, array $disclosures, string $proceedingId): void
    {
        $input = ['mission' => $mission, 'disclosures' => $disclosures];
        $this->requireHistorical($input, 'protected-input');
        $source = $this->journal->read()['state'];
        $entry = $source['historical_inventory']['protected-input'][FormationJournal::digest($input)];
        $proceeding = ['proceeding_id' => $proceedingId, 'instance_id' => 'protected-runtime'];
        $turn = ['seneschal' => ['disposition' => 'MISSION_PLAN_DRAFTED', 'mission_plan' => ['objective' => 'Exact bounded Git object inspection.', 'protected_mission' => $mission]], 'sequence' => 1];
        $turn['record_digest'] = FormationJournal::digest($turn);
        (new FormationJournal($scratch))->change(function (array &$state) use ($source, $entry, $proceeding, $turn, $input): void {
            if (isset($state['citadel_id']) && $state['citadel_id'] !== $source['citadel_id']) { throw new \RuntimeException('CMF114_HISTORICAL_SCRATCH_NOT_EMPTY'); }
            $state = ['citadel_id' => $source['citadel_id'], 'trust' => $source['trust'],
                'revoked_decisions' => $source['revoked_decisions'] ?? [],
                'historical_inventory' => ['protected-input' => $source['historical_inventory']['protected-input'], 'proceeding' => [FormationJournal::digest($proceeding) => $entry],
                    'turn' => [FormationJournal::digest($turn) => $entry]],
                'historical_protected_input' => $input];
        });
    }

    public function requireDossier(array $dossier): void
    {
        if (isset($dossier['mission_plan']['protected_mission'])) {
            $this->requireHistorical(['mission' => $dossier['mission_plan']['protected_mission'], 'disclosures' => $dossier['disclosures'] ?? []], 'protected-input');
            return;
        }
        $this->requireHistorical($dossier, 'dossier');
    }

    public function copyHistoryToScratch(string $scratch): void
    {
        $source = $this->journal->read()['state'];
        (new FormationJournal($scratch))->change(function (array &$state) use ($source): void {
            if ([] !== $state) { throw new \RuntimeException('CMF114_HISTORICAL_SCRATCH_NOT_EMPTY'); }
            $state = array_intersect_key($source, array_flip(['citadel_id', 'trust', 'revoked_decisions', 'historical_inventory']));
        });
    }

    public function requireProtectedDisclosures(array $plan, array $disclosures): void
    {
        if (!isset($plan['protected_mission'])) { return; }
        $source = $this->journal->read()['state']['historical_protected_input'] ?? null;
        if (!is_array($source) || FormationJournal::digest($source) !== FormationJournal::digest(['mission' => $plan['protected_mission'], 'disclosures' => $disclosures])) {
            throw new \RuntimeException('CMF111_FRESH_INPUT_REQUIRES_CITADEL_FORMATION');
        }
    }
}
