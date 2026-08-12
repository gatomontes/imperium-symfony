<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ConstructionAuthorizationDeliveryService;
use PHPUnit\Framework\TestCase;

final class ConstructionAuthorizationDeliveryServiceTest extends TestCase
{
    public function testDeliversExactAuthorizationWithoutClaimingFoundryAcceptance(): void
    {
        $root = sys_get_temp_dir().'/imperium-construction-delivery-'.bin2hex(random_bytes(6));
        mkdir($root.'/var/imperium/curia/authorization-decisions', 0770, true);
        mkdir($root.'/var/imperium/offices/foundry/inbox', 0770, true);
        $refs = [];
        foreach (['Cybersecurity assessor', 'Independent reviewer'] as $index => $profession) {
            $id = 'foundry-persona-demand-'.str_pad((string) ($index + 1), 20, '0', STR_PAD_LEFT);
            $demand = ['schema' => 'imperium.foundry-persona-construction-demand/v1', 'demand_id' => $id,
                'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test', 'profession' => $profession,
                'status' => 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', 'construction_authority' => false,
                'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
            $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
            file_put_contents($root.'/var/imperium/offices/foundry/inbox/'.$id.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
            $refs[] = ['demand_id' => $id, 'record_digest' => $demand['record_digest'], 'profession' => $profession];
        }
        $actId = 'construction-authorization-1234567890abcdef1234';
        $act = ['schema' => 'imperium.imperator-construction-authorization/v1', 'kind' => 'FOUNDRY_PERSONA_CONSTRUCTION_AUTHORIZATION',
            'act_id' => $actId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test', 'demands' => $refs,
            'disposition' => 'AUTHORIZED_FOR_EXACT_DEMANDS', 'authorized_authority' => 'FOUNDRY_PERSONA_CONSTRUCTION_ONLY',
            'construction_authority' => true, 'persona_selection_authority' => false, 'spawning_authority' => false,
            'seat_binding_authority' => false, 'execution_authority' => false];
        $act['record_digest'] = hash('sha256', CanonicalJson::encode($act));
        file_put_contents($root.'/var/imperium/curia/authorization-decisions/'.$actId.'.json', json_encode($act, JSON_THROW_ON_ERROR));

        try {
            $service = new ConstructionAuthorizationDeliveryService($root);
            $delivery = $service->deliver($actId);
            self::assertSame($delivery, $service->deliver($actId));
            self::assertSame('DELIVERED_PENDING_FOUNDRY_ACCEPTANCE', $delivery['status']);
            self::assertNull($delivery['recipient_acceptance']);
            self::assertCount(2, $delivery['authorized_demands']);
            self::assertTrue($delivery['construction_authority']);
            self::assertFalse($delivery['persona_selection_authority']);
            self::assertFalse($delivery['spawning_authority']);
            self::assertFalse($delivery['seat_binding_authority']);
            self::assertFalse($delivery['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/foundry/inbox/construction-authorizations/'.$delivery['delivery_id'].'.json');
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
