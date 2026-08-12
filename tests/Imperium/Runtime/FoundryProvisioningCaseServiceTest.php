<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\CanonicalFoundryStaffRegistry;
use App\Imperium\Runtime\Foundry\FoundryProvisioningCaseService;
use PHPUnit\Framework\TestCase;

final class FoundryProvisioningCaseServiceTest extends TestCase
{
    public function testOpensCanonicalArtificerProvisioningWithoutDownstreamAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-foundry-provisioning-'.bin2hex(random_bytes(6));
        $id = 'foundry-activation-1234567890abcdef1234'; $dir = $root.'/var/imperium/mastermason/spawning-requests'; mkdir($dir, 0770, true);
        $demand = ['schema' => 'imperium.office-activation-demand/v1', 'demand_id' => $id, 'office' => 'foundry',
            'required_seats' => [['seat' => 'foundry.artificer']], 'status' => 'CANONICAL_STAFF_ARTIFACTS_REQUIRED',
            'construction_authority' => true, 'spawning_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false];
        $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand)); file_put_contents($dir.'/'.$id.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
        try {
            $source = dirname(__DIR__, 3); $service = new FoundryProvisioningCaseService($root, new CanonicalFoundryStaffRegistry($source));
            $case = $service->open($id); self::assertSame($case, $service->open($id)); self::assertSame('CANONICAL_STAFF_READY', $case['status']);
            self::assertSame('foundry.canonical-staff', $case['canonical_staff_package']['package_id']); self::assertTrue($case['construction_authority']);
            self::assertFalse($case['mission_persona_selection_required']); self::assertFalse($case['per_mission_profile_derivation_required']);
            self::assertFalse($case['commission_authority']); self::assertFalse($case['spawning_authority']); self::assertFalse($case['seat_binding_authority']); self::assertFalse($case['recipient_acceptance']); self::assertFalse($case['execution_authority']);
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
