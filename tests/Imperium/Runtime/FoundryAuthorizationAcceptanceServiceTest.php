<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\FoundryAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;

final class FoundryAuthorizationAcceptanceServiceTest extends TestCase
{
    public function testBoundArtificerAcceptsExactAuthorizationWithoutExecutionAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-foundry-acceptance-'.bin2hex(random_bytes(6));
        $deliveryId = 'construction-authorization-delivery-1234567890abcdef1234'; $bindingId = 'foundry-artificer-binding-1234567890abcdef1234'; $demandId = 'foundry-activation-1234567890abcdef1234';
        $act = ['act_id' => 'construction-authorization-1234567890abcdef1234', 'authorized_demands' => ['build two exact mission Personas']]; $act['record_digest'] = hash('sha256', CanonicalJson::encode($act));
        $delivery = ['schema' => 'imperium.foundry-construction-authorization-delivery/v1', 'delivery_id' => $deliveryId, 'office' => 'foundry', 'target' => 'foundry',
            'authorization_act_id' => $act['act_id'], 'authorization_act_digest' => $act['record_digest'], 'authorization_act' => $act, 'authorized_demands' => $act['authorized_demands'],
            'status' => 'DELIVERED_PENDING_FOUNDRY_ACCEPTANCE', 'recipient_acceptance' => null, 'construction_authority' => true,
            'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $delivery['record_digest'] = hash('sha256', CanonicalJson::encode($delivery));
        $demand = ['demand_id' => $demandId, 'office' => 'foundry', 'authorization_delivery_id' => $deliveryId, 'authorization_delivery_digest' => $delivery['record_digest'],
            'authorization_act_id' => $act['act_id'], 'construction_authority' => true]; $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
        $binding = ['schema' => 'imperium.foundry-artificer-occupancy/v1', 'binding_id' => $bindingId, 'instance_id' => 'imperium-test', 'office' => 'foundry', 'seat' => 'foundry.artificer',
            'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1, 'source_activation_demand_id' => $demandId, 'status' => 'ACTIVE', 'binding_atomic' => true,
            'foundry_construction_authority' => true, 'recipient_acceptance' => false, 'execution_authority' => false]; $binding['record_digest'] = hash('sha256', CanonicalJson::encode($binding));
        mkdir($root.'/var/imperium/offices/foundry/inbox/construction-authorizations', 0770, true); mkdir($root.'/var/imperium/mastermason/spawning-requests', 0770, true); mkdir($root.'/var/imperium/offices/foundry/occupancy', 0770, true);
        file_put_contents($root.'/var/imperium/offices/foundry/inbox/construction-authorizations/'.$deliveryId.'.json', json_encode($delivery, JSON_THROW_ON_ERROR));
        file_put_contents($root.'/var/imperium/mastermason/spawning-requests/'.$demandId.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
        file_put_contents($root.'/var/imperium/offices/foundry/occupancy/'.$bindingId.'.json', json_encode($binding, JSON_THROW_ON_ERROR));
        try {
            $service = new FoundryAuthorizationAcceptanceService($root); $acceptance = $service->accept($deliveryId, $bindingId);
            self::assertSame($acceptance, $service->accept($deliveryId, $bindingId)); self::assertSame('imperium.foundry-authorization-acceptance/v1', $acceptance['schema']);
            self::assertSame('foundry.artificer', $acceptance['actor']['seat']); self::assertSame('ACCEPTED_FOR_EXACT_CONSTRUCTION', $acceptance['disposition']);
            self::assertSame($act['authorized_demands'], $acceptance['authorized_demands']); self::assertTrue($acceptance['recipient_acceptance']); self::assertTrue($acceptance['foundry_construction_authority']);
            self::assertFalse($acceptance['persona_selection_authority']); self::assertFalse($acceptance['spawning_authority']); self::assertFalse($acceptance['seat_binding_authority']); self::assertFalse($acceptance['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/foundry/acceptances/'.$acceptance['acceptance_id'].'.json');
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
