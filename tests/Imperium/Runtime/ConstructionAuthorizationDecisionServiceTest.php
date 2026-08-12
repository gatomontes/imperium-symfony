<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ConstructionAuthorizationDecisionService;
use PHPUnit\Framework\TestCase;

final class ConstructionAuthorizationDecisionServiceTest extends TestCase
{
    public function testImperatorAuthorizesOnlyExactPersonaConstructionDemands(): void
    {
        $root = sys_get_temp_dir().'/imperium-construction-decision-'.bin2hex(random_bytes(6));
        $requestId = 'construction-authorization-request-1234567890abcdef1234';
        $demandRefs = [];
        mkdir($root.'/var/imperium/curia/authorization-requests', 0770, true);
        mkdir($root.'/var/imperium/offices/foundry/inbox', 0770, true);
        $dispositionDigest = str_repeat('a', 64);
        foreach (['Cybersecurity assessor', 'Independent reviewer'] as $index => $profession) {
            $demandId = 'foundry-persona-demand-'.str_pad((string) ($index + 1), 20, '0', STR_PAD_LEFT);
            $demand = ['schema' => 'imperium.foundry-persona-construction-demand/v1', 'demand_id' => $demandId,
                'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test',
                'source_disposition_id' => 'personnel-disposition-1234567890abcdef1234', 'source_disposition_digest' => $dispositionDigest,
                'profession' => $profession,
                'status' => 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', 'construction_authority' => false,
                'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
            $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
            file_put_contents($root.'/var/imperium/offices/foundry/inbox/'.$demandId.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
            $demandRefs[] = ['demand_id' => $demandId, 'record_digest' => $demand['record_digest'], 'profession' => $profession];
        }
        $request = ['schema' => 'imperium.curia-construction-authorization-request/v1', 'request_id' => $requestId,
            'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test', 'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'source_disposition_id' => 'personnel-disposition-1234567890abcdef1234', 'source_disposition_digest' => $dispositionDigest,
            'demands' => $demandRefs,
            'requested_authority' => 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY', 'status' => 'PENDING_IMPERATOR_DECISION',
            'construction_authority' => false, 'persona_selection_authority' => false, 'spawning_authority' => false,
            'seat_binding_authority' => false, 'execution_authority' => false];
        $request['record_digest'] = hash('sha256', CanonicalJson::encode($request));
        file_put_contents($root.'/var/imperium/curia/authorization-requests/'.$requestId.'.json', json_encode($request, JSON_THROW_ON_ERROR));

        try {
            $service = new ConstructionAuthorizationDecisionService($root);
            $act = $service->authorize($requestId);
            self::assertSame($act, $service->authorize($requestId));
            self::assertSame('AUTHORIZED_FOR_EXACT_DEMANDS', $act['disposition']);
            self::assertCount(2, $act['demands']);
            self::assertTrue($act['construction_authority']);
            self::assertFalse($act['persona_selection_authority']);
            self::assertFalse($act['spawning_authority']);
            self::assertFalse($act['seat_binding_authority']);
            self::assertFalse($act['execution_authority']);
            self::assertFileExists($root.'/var/imperium/curia/authorization-decisions/'.$act['act_id'].'.json');
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
