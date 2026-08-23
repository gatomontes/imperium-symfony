<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\FoundryActivationDemandService;
use PHPUnit\Framework\TestCase;

final class FoundryActivationDemandServiceTest extends TestCase
{
    public function testVacantFoundryDemandsCanonicalArtificerWithoutAcceptingOrSpawning(): void
    {
        $root = sys_get_temp_dir().'/imperium-foundry-demand-'.bin2hex(random_bytes(6));
        $directory = $root.'/var/imperium/offices/foundry/inbox/construction-authorizations';
        mkdir($directory, 0770, true);
        $act = ['act_id' => 'construction-authorization-1234567890abcdef1234'];
        $act['record_digest'] = hash('sha256', CanonicalJson::encode($act));
        $deliveryId = 'construction-authorization-delivery-1234567890abcdef1234';
        $delivery = ['schema' => 'imperium.foundry-construction-authorization-delivery/v1', 'delivery_id' => $deliveryId,
            'office' => 'foundry', 'target' => 'foundry', 'authorization_act_id' => $act['act_id'],
            'authorization_act_digest' => $act['record_digest'], 'authorized_demands' => [['demand_id' => 'foundry-persona-demand-1']],
            'status' => 'DELIVERED_PENDING_FOUNDRY_ACCEPTANCE', 'recipient_acceptance' => null, 'construction_authority' => true,
            'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false,
            'execution_authority' => false, 'authorization_act' => $act];
        $delivery['record_digest'] = hash('sha256', CanonicalJson::encode($delivery));
        file_put_contents($directory.'/'.$deliveryId.'.json', json_encode($delivery, JSON_THROW_ON_ERROR));

        try {
            $service = new FoundryActivationDemandService($root);
            $demand = $service->demand($deliveryId);
            self::assertSame($demand, $service->demand($deliveryId));
            self::assertSame('CANONICAL_STAFF_ARTIFACTS_REQUIRED', $demand['status']);
            self::assertSame('foundry.artificer', $demand['required_seats'][0]['seat']);
            self::assertSame('BLOCKED_PENDING_CANONICAL_STAFF_ARTIFACTS', $demand['required_seats'][0]['status']);
            self::assertTrue($demand['construction_authority']);
            self::assertFalse($demand['mission_persona_selection_required']);
            self::assertFalse($demand['spawning_authority']);
            self::assertFalse($demand['seat_binding_authority']);
            self::assertFalse($demand['recipient_acceptance']);
            self::assertFalse($demand['execution_authority']);
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
