<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Foundry\ArtificerConstructionCommissionService;
use App\Imperium\Runtime\Foundry\CanonicalFoundryStaffRegistry;
use PHPUnit\Framework\TestCase;

final class ArtificerConstructionCommissionServiceTest extends TestCase
{
    public function testIssuesOnlyExactArtificerAssemblyAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-artificer-commission-'.bin2hex(random_bytes(6)); $project = dirname(__DIR__, 3); $staff = new CanonicalFoundryStaffRegistry($project);
        $caseId = 'foundry-provisioning-1234567890abcdef1234'; $case = ['schema' => 'imperium.office-provisioning-case/v1', 'case_id' => $caseId,
            'activation_demand_id' => 'foundry-activation-1234567890abcdef1234', 'office' => 'foundry', 'seat' => 'foundry.artificer',
            'canonical_staff_package' => $staff->current(), 'status' => 'CANONICAL_STAFF_READY', 'construction_authority' => true,
            'commission_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false];
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case)); mkdir($root.'/var/imperium/mastermason/activation-cases', 0770, true); mkdir($root.'/runtime/artifacts', 0770, true);
        file_put_contents($root.'/var/imperium/mastermason/activation-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR)); copy($project.'/runtime/artifacts/generic-officer-substrate.json', $root.'/runtime/artifacts/generic-officer-substrate.json');
        try {
            $service = new ArtificerConstructionCommissionService($root, $staff, new GenericOfficerSubstrateRegistry($root)); $commission = $service->issue($caseId);
            self::assertSame($commission, $service->issue($caseId)); self::assertSame('ISSUED_PENDING_CONSCRIPTION', $commission['status']); self::assertSame('foundry.artificer', $commission['target_seat']);
            self::assertTrue($commission['spawning_authority']); self::assertFalse($commission['foundry_construction_authority']); self::assertFalse($commission['seat_binding_authority']); self::assertFalse($commission['recipient_acceptance']); self::assertFalse($commission['execution_authority']);
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
