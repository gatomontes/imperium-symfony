<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Guildhall;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ProceedingStore;

final readonly class GuildhallDeliberationService
{
    private string $acceptanceDirectory;
    private string $occupancyDirectory;
    private string $outputDirectory;
    private string $checkpointDirectory;

    public function __construct(
        string $projectDir,
        private ProceedingStore $proceedings,
        private GuildhallCognitionGateway $cognition,
    ) {
        $this->acceptanceDirectory = $projectDir.'/var/imperium/offices/guildhall/acceptances';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/guildhall/occupancy';
        $this->outputDirectory = $projectDir.'/var/imperium/offices/guildhall/deliberations';
        $this->checkpointDirectory = $projectDir.'/var/imperium/offices/guildhall/deliberation-checkpoints';
    }

    public function deliberate(string $acceptanceId, ?callable $progress = null): array
    {
        if (!preg_match('/^guildhall-acceptance-[a-f0-9]{20}$/', $acceptanceId)) {
            throw new \InvalidArgumentException('G60_ACCEPTANCE_INVALID: exact Guildhall acceptance identity is required.');
        }
        $acceptance = $this->read($this->acceptanceDirectory.'/'.$acceptanceId.'.json', 'G61_ACCEPTANCE_ABSENT');
        if (!$this->digestMatches($acceptance)
            || 'imperium.guildhall-commission-acceptance/v1' !== ($acceptance['schema'] ?? null)
            || 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION' !== ($acceptance['disposition'] ?? null)
            || true !== ($acceptance['recipient_acceptance'] ?? null)
            || true !== ($acceptance['deliberation_authority'] ?? null)
            || true !== ($acceptance['personnel_disposition_authority'] ?? null)
            || true === ($acceptance['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('G62_ACCEPTANCE_INVALID: exact bounded Guildmaster acceptance is required.');
        }
        $existing = [];
        foreach (glob($this->outputDirectory.'/guildhall-determination-*.json') ?: [] as $path) {
            $candidate = $this->read($path, 'G68_DELIBERATION_REPLAY_CONFLICT');
            if ($acceptanceId === ($candidate['acceptance_id'] ?? null)) {
                $existing[] = $candidate;
            }
        }
        if (1 === count($existing) && $this->digestMatches($existing[0])) {
            return $existing[0];
        }
        if ([] !== $existing) {
            throw new \RuntimeException('G68_DELIBERATION_REPLAY_CONFLICT: acceptance has an invalid or ambiguous determination.');
        }
        $bindingId = $acceptance['binding_id'] ?? null;
        $binding = is_string($bindingId) ? $this->read($this->occupancyDirectory.'/'.$bindingId.'.json', 'G63_OCCUPANCY_ABSENT') : [];
        if (!$this->digestMatches($binding)
            || ($acceptance['binding_digest'] ?? null) !== ($binding['record_digest'] ?? null)
            || true !== ($binding['binding_atomic'] ?? null)
            || 4 !== count($binding['bindings'] ?? [])
        ) {
            throw new \RuntimeException('G64_OCCUPANCY_CHANGED: accepted Guildhall cohort is unavailable or changed.');
        }
        $expectedSeats = ['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'];
        $occupancy = [];
        foreach ($expectedSeats as $seat) {
            $occupant = $binding['bindings'][$seat] ?? null;
            if (!is_array($occupant)
                || $seat !== ($occupant['seat'] ?? null)
                || 1 !== ($occupant['occupancy_generation'] ?? null)
                || 'BOUND_PENDING_COMMISSION_ACCEPTANCE' !== ($occupant['status'] ?? null)
            ) {
                throw new \RuntimeException('G64_OCCUPANCY_CHANGED: '.$seat.' is not the accepted bound occupant.');
            }
            $occupancy[$seat] = ['manifestation_id' => $occupant['manifestation_id'], 'occupancy_generation' => 1];
        }

        $proceedingId = $acceptance['proceeding_id'] ?? null;
        $commission = null;
        foreach (is_string($proceedingId) ? $this->proceedings->commissions($proceedingId) : [] as $candidate) {
            if (($acceptance['commission_id'] ?? null) === ($candidate['commission_id'] ?? null)) {
                $commission = $candidate;
            }
        }
        if (!is_array($commission)
            || !$this->digestMatches($commission)
            || ($acceptance['commission_digest'] ?? null) !== ($commission['record_digest'] ?? null)
            || 'planning-only' !== ($commission['phase'] ?? null)
            || true === ($commission['execution_authority'] ?? null)
        ) {
            throw new \RuntimeException('G65_COMMISSION_CHANGED: accepted planning commission is unavailable or changed.');
        }
        $translation = $commission['translation_boundary'] ?? null;
        if ('FUNCTIONAL_CAPABILITIES' !== ($commission['source_language'] ?? null)
            || !is_array($commission['source_capability_requirements'] ?? null)
            || [] === $commission['source_capability_requirements']
            || !is_array($translation)
            || 'CAPABILITY_TO_PROFESSION' !== ($translation['name'] ?? null)
            || 'guildhall.guildmaster' !== ($translation['authority'] ?? null)
            || false !== ($translation['curia_profession_selection_authority'] ?? null)
            || false !== ($translation['curia_persona_selection_authority'] ?? null)
            || true !== ($translation['guildhall_profession_determination_authority'] ?? null)
            || true !== ($translation['guildhall_persona_suitability_authority'] ?? null)
        ) {
            throw new \RuntimeException('G70_TRANSLATION_BOUNDARY_INVALID: Guildhall requires an exact capability-only demand and exclusive translation authority.');
        }
        $turnSequence = $commission['authority']['plan_turn'] ?? null;
        $turn = is_int($turnSequence) ? $this->proceedings->turn($proceedingId, $turnSequence) : null;
        $plan = is_array($turn) ? ($turn['seneschal']['mission_plan'] ?? null) : null;
        if (!is_array($turn)
            || !is_array($plan)
            || ($commission['authority']['plan_digest'] ?? null) !== ($turn['record_digest'] ?? null)
        ) {
            throw new \RuntimeException('G66_MISSION_PLAN_CHANGED: exact commissioned Mission Plan is unavailable.');
        }

        $checkpointPath = $this->checkpointDirectory.'/'.$acceptanceId.'.json';
        $completed = is_file($checkpointPath) ? $this->read($checkpointPath, 'G69_CHECKPOINT_INVALID') : [];
        if ([] !== $completed
            && (!$this->digestMatches($completed)
                || $acceptanceId !== ($completed['acceptance_id'] ?? null)
                || $acceptance['record_digest'] !== ($completed['acceptance_digest'] ?? null)
                || $turn['record_digest'] !== ($completed['mission_plan_digest'] ?? null))
        ) {
            throw new \RuntimeException('G69_CHECKPOINT_INVALID: deliberation checkpoint is stale or changed.');
        }
        $decision = $this->cognition->deliberate(
            $plan,
            $acceptance['authorized_scope'] ?? [],
            $occupancy,
            $completed['decision'] ?? [],
            $progress,
            fn (array $partial): array => $this->persistCheckpoint($checkpointPath, [
                'schema' => 'imperium.guildhall-deliberation-checkpoint/v1',
                'acceptance_id' => $acceptanceId,
                'acceptance_digest' => $acceptance['record_digest'],
                'mission_plan_digest' => $turn['record_digest'],
                'decision' => $partial,
            ]),
        );
        $guildmaster = $decision['guildmaster'] ?? null;
        if (!is_array($guildmaster)) {
            throw new \RuntimeException('G67_DELIBERATION_INVALID: Guildmaster synthesis is absent.');
        }
        $record = [
            'schema' => 'imperium.guildhall-profession-determination/v1',
            'determination_id' => 'guildhall-determination-'.substr(hash('sha256', CanonicalJson::encode([$acceptanceId, $acceptance['record_digest'], $turn['record_digest'], $decision])), 0, 20),
            'instance_id' => $acceptance['instance_id'],
            'proceeding_id' => $proceedingId,
            'commission_id' => $acceptance['commission_id'],
            'commission_digest' => $acceptance['commission_digest'],
            'acceptance_id' => $acceptanceId,
            'acceptance_digest' => $acceptance['record_digest'],
            'binding_id' => $bindingId,
            'occupancy' => $occupancy,
            'mission_plan_digest' => $turn['record_digest'],
            'source_language' => 'FUNCTIONAL_CAPABILITIES',
            'source_capability_requirements' => $commission['source_capability_requirements'],
            'translation_boundary' => $translation,
            'committee_dispositions' => $decision['committee'] ?? null,
            'guildmaster_synthesis' => $guildmaster,
            'status' => 'PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED',
            'final_personnel_disposition' => false,
            'garrison_inventory_authority' => true,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ];

        return $this->persist($record);
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }
        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function digestMatches(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);
        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    private function persist(array $record): array
    {
        if (!is_dir($this->outputDirectory) && !mkdir($this->outputDirectory, 0770, true) && !is_dir($this->outputDirectory)) {
            throw new \RuntimeException('Guildhall deliberation directory cannot be created.');
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $path = $this->outputDirectory.'/'.$record['determination_id'].'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'G68_DELIBERATION_REPLAY_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($record)) {
                throw new \RuntimeException('G68_DELIBERATION_REPLAY_CONFLICT: determination identity is already bound differently.');
            }
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall determination cannot be committed atomically.');
        }
        return $record;
    }

    private function persistCheckpoint(string $path, array $checkpoint): array
    {
        if (!is_dir($this->checkpointDirectory) && !mkdir($this->checkpointDirectory, 0770, true) && !is_dir($this->checkpointDirectory)) {
            throw new \RuntimeException('Guildhall checkpoint directory cannot be created.');
        }
        $checkpoint['record_digest'] = hash('sha256', CanonicalJson::encode($checkpoint));
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($checkpoint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Guildhall checkpoint cannot be committed atomically.');
        }
        return $checkpoint;
    }
}
