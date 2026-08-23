<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\GarrisonInventoryResponseService;
use PHPUnit\Framework\TestCase;

final class GarrisonInventoryResponseServiceTest extends TestCase
{
    public function testActiveConstableAuthoritativelyReturnsExactEmptyCustodyLedgerWithoutSelection(): void
    {
        $root = sys_get_temp_dir().'/imperium-garrison-response-'.bin2hex(random_bytes(6)); $inquiryId = 'garrison-inquiry-1234567890abcdef1234';
        $inquiry = ['schema' => 'imperium.garrison-inventory-inquiry/v1', 'inquiry_id' => $inquiryId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test',
            'requester' => ['office' => 'guildhall', 'seat' => 'guildhall.guildmaster', 'manifestation_id' => 'guildmaster-test', 'occupancy_generation' => 1],
            'inventory_questions' => ['Which admitted Personas are available?'], 'requested_facts' => ['exact admitted Persona identity and version'],
            'status' => 'CONSTABLE_ACTIVATION_REQUIRED', 'authoritative_inventory_response' => false, 'ranking_authority' => false, 'selection_authority' => false,
            'reservation_authority' => false, 'retrieval_authority' => false, 'spawning_authority' => false, 'execution_authority' => false];
        $inquiry['record_digest'] = hash('sha256', CanonicalJson::encode($inquiry));
        $occupancy = ['schema' => 'imperium.garrison-constable-occupancy/v1', 'binding_id' => 'garrison-constable-binding-1234567890abcdef1234', 'instance_id' => 'imperium-test',
            'seat' => 'garrison.constable', 'manifestation_id' => 'constable-test', 'occupancy_generation' => 1, 'status' => 'ACTIVE',
            'inventory_response_authority' => true, 'selection_authority' => false, 'execution_authority' => false];
        $occupancy['record_digest'] = hash('sha256', CanonicalJson::encode($occupancy));
        mkdir($root.'/var/imperium/offices/garrison/inbox', 0770, true); mkdir($root.'/var/imperium/offices/garrison/occupancy', 0770, true);
        file_put_contents($root.'/var/imperium/offices/garrison/inbox/'.$inquiryId.'.json', json_encode($inquiry, JSON_THROW_ON_ERROR));
        file_put_contents($root.'/var/imperium/offices/garrison/occupancy/'.$occupancy['binding_id'].'.json', json_encode($occupancy, JSON_THROW_ON_ERROR));
        try {
            $service = new GarrisonInventoryResponseService($root); $response = $service->respond($inquiryId);
            self::assertSame($response, $service->respond($inquiryId)); self::assertSame('AUTHORITATIVE_INVENTORY_FACTS_DELIVERED', $response['status']);
            self::assertTrue($response['authoritative_inventory_response']); self::assertSame('constable-test', $response['responder']['manifestation_id']);
            self::assertSame('NO_ADMITTED_PERSONA_CUSTODY_RECORDS_HELD', $response['ledger_finding']); self::assertSame([], $response['inventory_records']);
            self::assertFalse($response['ranking_authority']); self::assertFalse($response['selection_authority']); self::assertFalse($response['reservation_authority']);
            self::assertFalse($response['retrieval_authority']); self::assertFalse($response['spawning_authority']); self::assertFalse($response['execution_authority']);
            self::assertSame('guildhall.guildmaster', $response['recipient']['seat']);
            self::assertFileExists($root.'/var/imperium/offices/guildhall/inventory-responses/'.$response['response_id'].'.json');
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
