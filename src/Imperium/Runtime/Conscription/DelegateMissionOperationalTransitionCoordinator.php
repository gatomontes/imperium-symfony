<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\CodexImperiiStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionOperationalTransitionCoordinator
{
    private const string QUALIFIED = 'DELEGATE_MISSION_PROFILE_OPERATIONALLY_QUALIFIED_PENDING_MANIFESTATION_ASSEMBLY';
    private const string ASSEMBLED = 'DELEGATE_MISSION_OPERATIONAL_MANIFESTATION_ASSEMBLED_PENDING_MISSION_SEAT_BINDING';
    private const string BOUND = 'DELEGATE_MISSION_MANIFESTATION_BOUND_PENDING_DEPLOYMENT_AUTHORIZATION';

    private CodexImperiiStore $codex;
    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        ?CodexImperiiStore $codex = null,
        ?ImmutableRecordStore $records = null,
        private ?OperationalTransitionFaultInjector $faults = null,
    ) {
        $atomic = new AtomicTransition($root);
        $this->codex = $codex ?? new CodexImperiiStore($root, $atomic);
        $this->records = $records ?? new ImmutableRecordStore($root, $atomic);
    }

    public function commitQualification(string $id, array $qualification): array
    {
        $this->requireId($id, $qualification, 'qualification_id');
        $record = $this->records->put(
            'var/imperium/offices/conscription/delegate-mission-operational-profile-qualifications',
            $id,
            $qualification,
        );

        return $this->recordQualification($record);
    }

    public function commitAssembly(string $id, array $assembly): array
    {
        $this->requireId($id, $assembly, 'assembly_id');
        $record = $this->records->put(
            'var/imperium/offices/conscription/delegate-mission-operational-manifestation-assemblies',
            $id,
            $assembly,
        );

        return $this->recordAssembly($record);
    }

    public function commitBinding(string $id, array $binding): array
    {
        $this->requireId($id, $binding, 'binding_id');
        $record = $this->records->put('var/imperium/mission/occupancy', $id, $binding);

        return $this->recordBinding($record);
    }

    public function recordQualification(array $qualification): array
    {
        $this->validate(
            $qualification,
            'imperium.conscription-delegate-mission-operational-profile-qualification/v1',
            'qualification_id',
            self::QUALIFIED,
        );
        $this->faults?->at('BEFORE_QUALIFICATION_INDEXED');
        $this->codex->initialize(
            $qualification['instance_id'],
            self::QUALIFIED,
            [$this->folium(
                $qualification,
                $qualification['qualification_id'],
                'conscription',
                'operational-profile-qualification',
                'var/imperium/offices/conscription/delegate-mission-operational-profile-qualifications/'.$qualification['qualification_id'].'.json',
                1,
            )],
        );
        $this->faults?->at('QUALIFICATION_INDEXED');

        return $qualification;
    }

    public function recordAssembly(array $assembly): array
    {
        $this->validate(
            $assembly,
            'imperium.conscription-delegate-mission-operational-manifestation-assembly/v1',
            'assembly_id',
            self::ASSEMBLED,
        );
        $this->requirePredecessor(
            $assembly['instance_id'],
            self::QUALIFIED,
            self::ASSEMBLED,
            $assembly['source_qualification'] ?? null,
            $assembly['assembly_id'],
            $assembly['record_digest'],
        );
        $this->faults?->at('BEFORE_ASSEMBLY_INDEXED');
        $this->codex->advance(
            $assembly['instance_id'],
            self::QUALIFIED,
            self::ASSEMBLED,
            [$this->folium(
                $assembly,
                $assembly['assembly_id'],
                'conscription',
                'operational-manifestation-assembly',
                'var/imperium/offices/conscription/delegate-mission-operational-manifestation-assemblies/'.$assembly['assembly_id'].'.json',
                2,
            )],
        );
        $this->faults?->at('ASSEMBLY_INDEXED');

        return $assembly;
    }

    public function recordBinding(array $binding): array
    {
        $this->validate(
            $binding,
            'imperium.delegate-mission-operational-manifestation-seat-binding/v1',
            'binding_id',
            self::BOUND,
        );
        $this->requirePredecessor(
            $binding['instance_id'],
            self::ASSEMBLED,
            self::BOUND,
            $binding['source_assembly'] ?? null,
            $binding['binding_id'],
            $binding['record_digest'],
        );
        $this->faults?->at('BEFORE_BINDING_INDEXED');
        $this->codex->advance(
            $binding['instance_id'],
            self::ASSEMBLED,
            self::BOUND,
            [$this->folium(
                $binding,
                $binding['binding_id'],
                'mission',
                'operational-manifestation-seat-binding',
                'var/imperium/mission/occupancy/'.$binding['binding_id'].'.json',
                3,
            )],
        );
        $this->faults?->at('BINDING_INDEXED');

        return $binding;
    }

    private function requirePredecessor(
        string $instanceId,
        string $expectedCheckpoint,
        string $nextCheckpoint,
        mixed $source,
        string $currentId,
        string $currentDigest,
    ): void {
        $codex = $this->codex->read();
        $last = $codex['folia'][array_key_last($codex['folia'])] ?? null;
        $predecessorMatches = $expectedCheckpoint === $codex['current_checkpoint']
            && is_array($source)
            && ($source['id'] ?? null) === ($last['folium_id'] ?? null)
            && ($source['digest'] ?? null) === ($last['digest'] ?? null);
        $exactReplayMatches = $nextCheckpoint === $codex['current_checkpoint']
            && $currentId === ($last['folium_id'] ?? null)
            && $currentDigest === ($last['digest'] ?? null);
        if ($instanceId !== $codex['instance_id'] || (!$predecessorMatches && !$exactReplayMatches)) {
            throw new \RuntimeException('CDI123_OPERATIONAL_PREDECESSOR_INVALID');
        }
    }

    private function validate(array $record, string $schema, string $idKey, string $status): void
    {
        $digest = $record['record_digest'] ?? null;
        $unsigned = $record;
        unset($unsigned['record_digest']);
        if ($schema !== ($record['schema'] ?? null)
            || !is_string($record[$idKey] ?? null)
            || '' === trim($record[$idKey])
            || !is_string($record['instance_id'] ?? null)
            || '' === trim($record['instance_id'])
            || $status !== ($record['status'] ?? null)
            || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($unsigned)))) {
            throw new \RuntimeException('CDI120_OPERATIONAL_FOLIUM_INVALID');
        }
    }

    private function requireId(string $id, array $record, string $idKey): void
    {
        if ($id !== ($record[$idKey] ?? null)) {
            throw new \RuntimeException('CDI121_OPERATIONAL_FOLIUM_ID_MISMATCH');
        }
    }

    private function folium(
        array $record,
        string $id,
        string $office,
        string $relation,
        string $storageReference,
        int $sequence,
    ): array {
        return [
            'digest' => $record['record_digest'],
            'folium_id' => $id,
            'folium_schema' => $record['schema'],
            'office' => $office,
            'relation' => $relation,
            'sequence' => $sequence,
            'storage_reference' => $storageReference,
        ];
    }
}
