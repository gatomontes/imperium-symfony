<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Curia\PlanningCommissionRouter;
use App\Imperium\Runtime\Curia\ProceedingStore;
use PHPUnit\Framework\TestCase;

final class PlanningCommissionRouterTest extends TestCase
{
    public function testDeliversExactPacketsIdempotentlyWithoutClaimingAcceptanceOrExecution(): void
    {
        $root = sys_get_temp_dir().'/imperium-router-'.bin2hex(random_bytes(6));
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-router-test', 'instance_id' => 'instance-router-test']);
        foreach (['guildhall' => 'guildhall.guildmaster', 'armory' => 'armory'] as $name => $target) {
            $store->persistCommission('proceeding-router-test', 'planning-'.$name.'-12345678', [
                'schema' => 'imperium.planning-commission/v1',
                'phase' => 'planning-only',
                'proceeding_id' => 'proceeding-router-test',
                'instance_id' => 'instance-router-test',
                'target' => $target,
                'status' => 'ISSUED_PENDING_RECIPIENT',
                'execution_authority' => false,
            ]);
        }

        try {
            $router = new PlanningCommissionRouter($store, $root);
            $result = $router->deliver('proceeding-router-test');
            self::assertSame($result, $router->deliver('proceeding-router-test'));
            self::assertSame(['armory', 'guildhall'], array_keys($result['deliveries']));
            foreach ($result['deliveries'] as $delivery) {
                self::assertSame('DELIVERED_PENDING_RECIPIENT', $delivery['status']);
                self::assertNull($delivery['recipient_acceptance']);
                self::assertFalse($delivery['execution_authority']);
                self::assertSame('ISSUED_PENDING_RECIPIENT', $delivery['packet']['status']);
                self::assertFileExists($root.'/var/imperium/offices/'.$delivery['office'].'/inbox/'.$delivery['commission_id'].'.json');
            }
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
