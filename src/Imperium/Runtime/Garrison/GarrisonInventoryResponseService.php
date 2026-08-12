<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Garrison;

use App\Bootstrap\CanonicalJson;

final readonly class GarrisonInventoryResponseService
{
    private string $inquiryDirectory;
    private string $occupancyDirectory;
    private string $custodyDirectory;
    private string $guildhallResponses;

    public function __construct(string $projectDir)
    {
        $this->inquiryDirectory = $projectDir.'/var/imperium/offices/garrison/inbox';
        $this->occupancyDirectory = $projectDir.'/var/imperium/offices/garrison/occupancy';
        $this->custodyDirectory = $projectDir.'/var/imperium/offices/garrison/custody';
        $this->guildhallResponses = $projectDir.'/var/imperium/offices/guildhall/inventory-responses';
    }

    public function respond(string $inquiryId): array
    {
        if (!preg_match('/^garrison-inquiry-[a-f0-9]{20}$/', $inquiryId)) throw new \InvalidArgumentException('GA60_INQUIRY_INVALID: exact Garrison inquiry identity is required.');
        $inquiry = $this->read($this->inquiryDirectory.'/'.$inquiryId.'.json', 'GA61_INQUIRY_ABSENT');
        if (!$this->digestMatches($inquiry) || $inquiryId !== ($inquiry['inquiry_id'] ?? null)
            || !in_array($inquiry['status'] ?? null, ['CONSTABLE_ACTIVATION_REQUIRED', 'DELIVERED_PENDING_CONSTABLE_RESPONSE'], true)
            || true === ($inquiry['authoritative_inventory_response'] ?? null) || true === ($inquiry['ranking_authority'] ?? null)
            || true === ($inquiry['selection_authority'] ?? null) || true === ($inquiry['reservation_authority'] ?? null)
            || true === ($inquiry['retrieval_authority'] ?? null) || true === ($inquiry['spawning_authority'] ?? null) || true === ($inquiry['execution_authority'] ?? null)) {
            throw new \RuntimeException('GA62_INQUIRY_INVALID: exact pending non-authorizing Garrison inquiry is required.');
        }
        $occupancy = $this->currentConstable($inquiry['instance_id'] ?? null);
        $records = $this->custodyRecords();
        $response = [
            'schema' => 'imperium.garrison-inventory-response/v1',
            'response_id' => 'garrison-response-'.substr(hash('sha256', CanonicalJson::encode([$inquiryId, $inquiry['record_digest'], $occupancy['record_digest'], $records])), 0, 20),
            'instance_id' => $inquiry['instance_id'], 'proceeding_id' => $inquiry['proceeding_id'],
            'source_inquiry_id' => $inquiryId, 'source_inquiry_digest' => $inquiry['record_digest'],
            'responder' => ['office' => 'garrison', 'seat' => 'garrison.constable', 'manifestation_id' => $occupancy['manifestation_id'], 'occupancy_generation' => $occupancy['occupancy_generation'], 'occupancy_digest' => $occupancy['record_digest']],
            'recipient' => $inquiry['requester'], 'inventory_questions' => $inquiry['inventory_questions'], 'requested_facts' => $inquiry['requested_facts'],
            'inventory_records' => $records,
            'ledger_finding' => [] === $records ? 'NO_ADMITTED_PERSONA_CUSTODY_RECORDS_HELD' : 'EXACT_ADMITTED_PERSONA_CUSTODY_RECORDS_ATTACHED',
            'status' => 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED', 'authoritative_inventory_response' => true,
            'ranking_authority' => false, 'selection_authority' => false, 'reservation_authority' => false, 'retrieval_authority' => false, 'spawning_authority' => false, 'execution_authority' => false,
            'interpretation_boundary' => 'Garrison reports exact custody and availability facts only; Guildhall determines professional fit and construction need.',
        ];
        return $this->persist($response);
    }

    private function currentConstable(mixed $instanceId): array
    {
        $paths = glob($this->occupancyDirectory.'/garrison-constable-binding-*.json') ?: [];
        if (1 !== count($paths)) throw new \RuntimeException('GA63_CONSTABLE_OCCUPANCY_REQUIRED: one exact active Constable occupancy is required.');
        $record = $this->read($paths[0], 'GA63_CONSTABLE_OCCUPANCY_REQUIRED');
        if (!$this->digestMatches($record) || 'imperium.garrison-constable-occupancy/v1' !== ($record['schema'] ?? null)
            || $instanceId !== ($record['instance_id'] ?? null) || 'garrison.constable' !== ($record['seat'] ?? null) || 'ACTIVE' !== ($record['status'] ?? null)
            || true !== ($record['inventory_response_authority'] ?? null) || true === ($record['selection_authority'] ?? null) || true === ($record['execution_authority'] ?? null)) {
            throw new \RuntimeException('GA64_CONSTABLE_OCCUPANCY_INVALID: active occupancy lacks exact inventory-response authority.');
        }
        return $record;
    }

    private function custodyRecords(): array
    {
        $records = [];
        foreach (glob($this->custodyDirectory.'/*.json') ?: [] as $path) {
            $record = $this->read($path, 'GA65_CUSTODY_RECORD_INVALID');
            if (!$this->digestMatches($record) || 'imperium.garrison-persona-custody/v1' !== ($record['schema'] ?? null)
                || 'ADMITTED_HELD' !== ($record['custody_state'] ?? null) || !is_string($record['persona_id'] ?? null) || !is_string($record['persona_version'] ?? null)) {
                throw new \RuntimeException('GA65_CUSTODY_RECORD_INVALID: inventory contains an invalid custody record.');
            }
            $records[] = $record;
        }
        usort($records, static fn (array $a, array $b): int => [$a['persona_id'], $a['persona_version']] <=> [$b['persona_id'], $b['persona_version']]);
        return $records;
    }

    private function read(string $path, string $error): array { if (!is_file($path)) throw new \RuntimeException($error); return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR); }
    private function digestMatches(array $record): bool { $digest = $record['record_digest'] ?? null; unset($record['record_digest']); return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record))); }
    private function persist(array $response): array
    {
        if (!is_dir($this->guildhallResponses) && !mkdir($this->guildhallResponses, 0770, true) && !is_dir($this->guildhallResponses)) throw new \RuntimeException('Guildhall inventory-response directory cannot be created.');
        $response['record_digest'] = hash('sha256', CanonicalJson::encode($response)); $path = $this->guildhallResponses.'/'.$response['response_id'].'.json';
        if (is_file($path)) { $existing = $this->read($path, 'GA66_RESPONSE_ABSENT'); if (CanonicalJson::encode($existing) !== CanonicalJson::encode($response)) throw new \RuntimeException('GA67_RESPONSE_REPLAY_CONFLICT'); return $existing; }
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        if (false === file_put_contents($temporary, json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n", LOCK_EX) || !rename($temporary, $path)) { @unlink($temporary); throw new \RuntimeException('Garrison inventory response cannot be committed atomically.'); }
        return $response;
    }
}
