<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Guildhall\GuildhallPersonnelDispositionService;
use PHPUnit\Framework\TestCase;

final class GuildhallPersonnelDispositionServiceTest extends TestCase
{
    public function testEmptyAuthoritativeLedgerProducesDispositionAndUnauthorizedFoundryDemands(): void
    {
        $root = sys_get_temp_dir().'/imperium-personnel-disposition-'.bin2hex(random_bytes(6));
        $determinationId = 'guildhall-determination-1234567890abcdef1234'; $inquiryId = 'garrison-inquiry-1234567890abcdef1234'; $responseId = 'garrison-response-1234567890abcdef1234';
        $guildmaster = ['manifestation_id' => 'guildmaster-test', 'occupancy_generation' => 1];
        $determination = ['schema' => 'imperium.guildhall-profession-determination/v1', 'determination_id' => $determinationId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test',
            'occupancy' => ['guildhall.guildmaster' => $guildmaster], 'guildmaster_synthesis' => ['required_professions' => ['Cybersecurity assessor', 'Independent reviewer'],
                'exemplar_criteria' => ['Passive assessment discipline'], 'team_composition' => ['One assessor and one reviewer'], 'boundary_controls' => ['No active scanning']],
            'status' => 'PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED', 'final_personnel_disposition' => false, 'sealed' => true, 'execution_authority' => false];
        $determination['record_digest'] = hash('sha256', CanonicalJson::encode($determination));
        $inquiry = ['inquiry_id' => $inquiryId, 'source_determination_id' => $determinationId, 'source_determination_digest' => $determination['record_digest']]; $inquiry['record_digest'] = hash('sha256', CanonicalJson::encode($inquiry));
        $response = ['response_id' => $responseId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test', 'source_inquiry_id' => $inquiryId, 'source_inquiry_digest' => $inquiry['record_digest'],
            'responder' => ['occupancy_generation' => 1], 'recipient' => ['seat' => 'guildhall.guildmaster'] + $guildmaster,
            'inventory_records' => [], 'ledger_finding' => 'NO_ADMITTED_PERSONA_CUSTODY_RECORDS_HELD', 'status' => 'AUTHORITATIVE_INVENTORY_FACTS_DELIVERED',
            'authoritative_inventory_response' => true, 'ranking_authority' => false, 'selection_authority' => false, 'execution_authority' => false];
        $response['record_digest'] = hash('sha256', CanonicalJson::encode($response));
        $files = [
            $root.'/var/imperium/offices/guildhall/deliberations/'.$determinationId.'.json' => $determination,
            $root.'/var/imperium/offices/garrison/inbox/'.$inquiryId.'.json' => $inquiry,
            $root.'/var/imperium/offices/guildhall/inventory-responses/'.$responseId.'.json' => $response,
        ];
        foreach ($files as $path => $record) { mkdir(dirname($path), 0770, true); file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR)); }
        try {
            $service = new GuildhallPersonnelDispositionService($root); $result = $service->resolve($responseId); $disposition = $result['disposition'];
            self::assertSame($result, $service->resolve($responseId)); self::assertSame('PERSONNEL_GAPS_REQUIRE_CONSTRUCTION', $disposition['disposition']);
            self::assertTrue($disposition['final_personnel_disposition']); self::assertSame([], $disposition['available_admitted_personas']);
            self::assertSame(['Cybersecurity assessor', 'Independent reviewer'], $disposition['unresolved_personnel_gaps']); self::assertTrue($disposition['foundry_demand_authority']);
            self::assertFalse($disposition['construction_authority']); self::assertFalse($disposition['selection_authority']); self::assertFalse($disposition['execution_authority']);
            self::assertCount(2, $result['demands']);
            foreach ($result['demands'] as $demand) { self::assertSame('PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', $demand['status']); self::assertFalse($demand['persona_selection_authority']); self::assertFalse($demand['construction_authority']); self::assertFalse($demand['spawning_authority']); self::assertFalse($demand['execution_authority']); self::assertFileExists($root.'/var/imperium/offices/foundry/inbox/'.$demand['demand_id'].'.json'); }
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
