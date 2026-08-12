<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Garrison\GarrisonInventoryInquiryService;
use PHPUnit\Framework\TestCase;

final class GarrisonInventoryInquiryServiceTest extends TestCase
{
    public function testRecordsExactInquiryButRequiresConstableBeforeAuthoritativeResponse(): void
    {
        $root = sys_get_temp_dir().'/imperium-garrison-inquiry-'.bin2hex(random_bytes(6));
        $determinationId = 'guildhall-determination-1234567890abcdef1234';
        $determination = [
            'schema' => 'imperium.guildhall-profession-determination/v1',
            'determination_id' => $determinationId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test',
            'occupancy' => ['guildhall.guildmaster' => ['manifestation_id' => 'guildmaster-test', 'occupancy_generation' => 1]],
            'guildmaster_synthesis' => [
                'disposition' => 'PROFESSION_DETERMINATION_COMPLETE',
                'required_professions' => ['Cybersecurity assessor', 'Independent reviewer'],
                'exemplar_criteria' => ['Passive assessment discipline'],
                'team_composition' => ['One assessor and one independent reviewer'],
                'boundary_controls' => ['No active scanning'],
                'garrison_inventory_queries' => ['Which admitted Personas are available?'],
            ],
            'status' => 'PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED',
            'final_personnel_disposition' => false,
            'garrison_inventory_authority' => true,
            'execution_authority' => false,
            'sealed' => true,
        ];
        $determination['record_digest'] = hash('sha256', CanonicalJson::encode($determination));
        $directory = $root.'/var/imperium/offices/guildhall/deliberations';
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$determinationId.'.json', json_encode($determination, JSON_THROW_ON_ERROR));

        try {
            $service = new GarrisonInventoryInquiryService($root);
            $inquiry = $service->route($determinationId);

            self::assertSame($inquiry, $service->route($determinationId));
            self::assertSame('imperium.garrison-inventory-inquiry/v1', $inquiry['schema']);
            self::assertSame('CONSTABLE_ACTIVATION_REQUIRED', $inquiry['status']);
            self::assertNull($inquiry['constable_occupancy']);
            self::assertFalse($inquiry['authoritative_inventory_response']);
            self::assertFalse($inquiry['ranking_authority']);
            self::assertFalse($inquiry['selection_authority']);
            self::assertFalse($inquiry['reservation_authority']);
            self::assertFalse($inquiry['retrieval_authority']);
            self::assertFalse($inquiry['spawning_authority']);
            self::assertFalse($inquiry['execution_authority']);
            self::assertSame(['Which admitted Personas are available?'], $inquiry['inventory_questions']);
            self::assertFileExists($root.'/var/imperium/offices/garrison/inbox/'.$inquiry['inquiry_id'].'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
