<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ConstructionAuthorizationRequestService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use PHPUnit\Framework\TestCase;

final class ConstructionAuthorizationRequestServiceTest extends TestCase
{
    public function testCuriaRequestsButDoesNotGrantExactFoundryConstructionAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-construction-request-'.bin2hex(random_bytes(6)); $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-test', 'instance_id' => 'imperium-test']);
        $dispositionId = 'personnel-disposition-1234567890abcdef1234'; $professions = ['Cybersecurity assessor', 'Independent reviewer'];
        $disposition = ['schema' => 'imperium.guildhall-personnel-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test',
            'unresolved_personnel_gaps' => $professions, 'disposition' => 'PERSONNEL_GAPS_REQUIRE_CONSTRUCTION', 'final_personnel_disposition' => true,
            'foundry_demand_authority' => true, 'construction_authority' => false, 'selection_authority' => false, 'spawning_authority' => false, 'execution_authority' => false];
        $disposition['record_digest'] = hash('sha256', CanonicalJson::encode($disposition));
        mkdir($root.'/var/imperium/offices/guildhall/personnel-dispositions', 0770, true); mkdir($root.'/var/imperium/offices/foundry/inbox', 0770, true);
        file_put_contents($root.'/var/imperium/offices/guildhall/personnel-dispositions/'.$dispositionId.'.json', json_encode($disposition, JSON_THROW_ON_ERROR));
        foreach ($professions as $index => $profession) {
            $id = 'foundry-persona-demand-'.str_pad((string) ($index + 1), 20, '0', STR_PAD_LEFT);
            $demand = ['schema' => 'imperium.foundry-persona-construction-demand/v1', 'demand_id' => $id, 'source_disposition_id' => $dispositionId,
                'source_disposition_digest' => $disposition['record_digest'], 'instance_id' => 'imperium-test', 'profession' => $profession,
                'status' => 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', 'persona_selection_authority' => false, 'construction_authority' => false,
                'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
            $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand)); file_put_contents($root.'/var/imperium/offices/foundry/inbox/'.$id.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
        }
        try {
            $service = new ConstructionAuthorizationRequestService($root, $store); $request = $service->request($dispositionId);
            self::assertSame($request, $service->request($dispositionId)); self::assertSame('PENDING_IMPERATOR_DECISION', $request['status']);
            self::assertSame('imperator-development-root', $request['recipient']['id']); self::assertCount(2, $request['demands']);
            self::assertSame('FOUNDRY_PERSONA_CONSTRUCTION_ONLY', $request['requested_authority']); self::assertFalse($request['approval_recorded']);
            self::assertFalse($request['construction_authority']); self::assertFalse($request['persona_selection_authority']);
            self::assertFalse($request['spawning_authority']); self::assertFalse($request['seat_binding_authority']); self::assertFalse($request['execution_authority']);
            self::assertFileExists($root.'/var/imperium/curia/authorization-requests/'.$request['request_id'].'.json');
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
