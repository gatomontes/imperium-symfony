<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;

final readonly class GarrisonInventoryInquiryService
{
    private string $determinationDirectory;
    private string $occupancyDirectory;
    private string $inquiryDirectory;

    public function __construct(string $projectDir)
    {
        $this->determinationDirectory = $projectDir.'/var/imperium/offices/guildhall/deliberations';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/garrison/occupancy';
        $this->inquiryDirectory = $projectDir.'/var/imperium/offices/garrison/inbox';
    }

    public function route(string $determinationId): array
    {
        if (!preg_match('/^guildhall-determination-[a-f0-9]{20}$/', $determinationId)) {
            throw new \InvalidArgumentException('GA10_DETERMINATION_INVALID: exact Guildhall determination identity is required.');
        }
        $determination = $this->read($this->determinationDirectory.'/'.$determinationId.'.json', 'GA11_DETERMINATION_ABSENT');
        $synthesis = $determination['guildmaster_synthesis'] ?? null;
        if (!is_array($synthesis)
            || !$this->digestMatches($determination)
            || 'imperium.guildhall-profession-determination/v1' !== ($determination['schema'] ?? null)
            || 'PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED' !== ($determination['status'] ?? null)
            || 'PROFESSION_DETERMINATION_COMPLETE' !== ($synthesis['disposition'] ?? null)
            || true !== ($determination['garrison_inventory_authority'] ?? null)
            || true !== ($determination['sealed'] ?? null)
            || true === ($determination['final_personnel_disposition'] ?? null)
            || true === ($determination['execution_authority'] ?? null)
            || [] === ($synthesis['garrison_inventory_queries'] ?? [])
        ) {
            throw new \RuntimeException('GA12_DETERMINATION_INVALID: exact sealed non-executing Profession Determination is required.');
        }

        $constable = $this->currentConstable();
        $inquiry = [
            'schema' => 'imperium.garrison-inventory-inquiry/v1',
            'inquiry_id' => 'garrison-inquiry-'.substr(hash('sha256', CanonicalJson::encode([$determinationId, $determination['record_digest'], $synthesis['garrison_inventory_queries']])), 0, 20),
            'instance_id' => $determination['instance_id'],
            'proceeding_id' => $determination['proceeding_id'],
            'requester' => [
                'office' => 'guildhall',
                'seat' => 'guildhall.guildmaster',
                'manifestation_id' => $determination['occupancy']['guildhall.guildmaster']['manifestation_id'] ?? null,
                'occupancy_generation' => $determination['occupancy']['guildhall.guildmaster']['occupancy_generation'] ?? null,
            ],
            'recipient' => ['office' => 'garrison', 'seat' => 'garrison.constable'],
            'source_determination_id' => $determinationId,
            'source_determination_digest' => $determination['record_digest'],
            'required_professions' => $synthesis['required_professions'],
            'exemplar_criteria' => $synthesis['exemplar_criteria'],
            'team_composition' => $synthesis['team_composition'],
            'boundary_controls' => $synthesis['boundary_controls'],
            'inventory_questions' => $synthesis['garrison_inventory_queries'],
            'requested_facts' => ['exact admitted Persona identity and version', 'custody state', 'availability facts', 'qualification evidence held in custody', 'conflicts and current commitments'],
            'ranking_authority' => false,
            'selection_authority' => false,
            'reservation_authority' => false,
            'retrieval_authority' => false,
            'spawning_authority' => false,
            'execution_authority' => false,
            'constable_occupancy' => $constable,
            'status' => null === $constable ? 'CONSTABLE_ACTIVATION_REQUIRED' : 'DELIVERED_PENDING_CONSTABLE_RESPONSE',
            'authoritative_inventory_response' => false,
        ];

        return $this->persist($inquiry);
    }

    private function currentConstable(): ?array
    {
        $paths = glob($this->occupancyDirectory.'/garrison-constable-binding-*.json') ?: [];
        if ([] === $paths) {
            return null;
        }
        if (1 !== count($paths)) {
            throw new \RuntimeException('GA13_CONSTABLE_OCCUPANCY_AMBIGUOUS: exact current Constable occupancy cannot be established.');
        }
        $record = $this->read($paths[0], 'GA14_CONSTABLE_OCCUPANCY_INVALID');
        if (!$this->digestMatches($record)
            || 'imperium.garrison-constable-occupancy/v1' !== ($record['schema'] ?? null)
            || 'garrison.constable' !== ($record['seat'] ?? null)
            || 'ACTIVE' !== ($record['status'] ?? null)
            || !is_string($record['manifestation_id'] ?? null)
            || !is_int($record['occupancy_generation'] ?? null)
        ) {
            throw new \RuntimeException('GA14_CONSTABLE_OCCUPANCY_INVALID: exact active Constable occupancy is required.');
        }

        return [
            'seat' => $record['seat'],
            'manifestation_id' => $record['manifestation_id'],
            'occupancy_generation' => $record['occupancy_generation'],
            'record_digest' => $record['record_digest'],
        ];
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

    private function persist(array $inquiry): array
    {
        if (!is_dir($this->inquiryDirectory) && !mkdir($this->inquiryDirectory, 0770, true) && !is_dir($this->inquiryDirectory)) {
            throw new \RuntimeException('Garrison inbox cannot be created.');
        }
        $inquiry['record_digest'] = hash('sha256', CanonicalJson::encode($inquiry));
        $path = $this->inquiryDirectory.'/'.$inquiry['inquiry_id'].'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'GA15_INQUIRY_REPLAY_CONFLICT');
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($inquiry)) {
                throw new \RuntimeException('GA15_INQUIRY_REPLAY_CONFLICT: inquiry identity is already bound differently.');
            }
            return $existing;
        }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($inquiry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Garrison inquiry cannot be committed atomically.');
        }
        return $inquiry;
    }
}
