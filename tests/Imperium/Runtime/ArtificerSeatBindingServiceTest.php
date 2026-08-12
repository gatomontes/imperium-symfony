<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Foundry\ArtificerSeatBindingService;
use App\Imperium\Runtime\Foundry\CanonicalFoundryStaffRegistry;
use PHPUnit\Framework\TestCase;

final class ArtificerSeatBindingServiceTest extends TestCase
{
    public function testAtomicallyBindsQualifiedArtificerAndActivatesOnlyExactFoundryAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-artificer-binding-'.bin2hex(random_bytes(6)); $project = dirname(__DIR__, 3);
        $bootstrap = new StateStore($root); $bootstrap->locked(static function () use ($bootstrap): void { $bootstrap->write(['state' => 'CURIA_READY', 'binding' => ['instance_id' => 'imperium-test'], 'events' => []]); });
        mkdir($root.'/runtime/artifacts', 0770, true); copy($project.'/runtime/artifacts/generic-officer-substrate.json', $root.'/runtime/artifacts/generic-officer-substrate.json');
        $registry = new CanonicalFoundryStaffRegistry($project); $substrate = new GenericOfficerSubstrateRegistry($root); $member = $registry->member();
        $caseId = 'foundry-provisioning-1234567890abcdef1234'; $case = ['case_id' => $caseId, 'activation_demand_id' => 'foundry-activation-1234567890abcdef1234',
            'seat' => 'foundry.artificer', 'status' => 'CANONICAL_STAFF_READY', 'construction_authority' => true, 'canonical_staff_package' => $registry->current()];
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case)); mkdir($root.'/var/imperium/mastermason/activation-cases', 0770, true);
        file_put_contents($root.'/var/imperium/mastermason/activation-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR));
        $deliveryId = 'qualified-delivery-1234567890abcdef1234'; $manifestationId = 'imperium-test.officer.foundry.artificer.123456789abc';
        $qualification = ['candidate_id' => $manifestationId, 'qualification_contract' => $member['qualification_contract'], 'disposition' => 'QUALIFIED'];
        $packet = ['schema' => 'imperium.qualified-manifestation-packet/v1', 'delivery_id' => $deliveryId, 'source_provisioning_case_id' => $caseId, 'commission' => ['consumed' => true],
            'candidate' => ['manifestation_id' => $manifestationId, 'instance_id' => 'imperium-test', 'persona' => $member['persona'], 'profile' => $member['profile'],
                'substrate_instance' => ['substrate' => $substrate->current(), 'status' => 'PROFILE_INSTALLED'], 'target_seat' => 'foundry.artificer', 'target_occupancy_generation' => 1, 'status' => 'QUALIFIED_UNBOUND'],
            'qualification' => $qualification, 'qualification_digest' => hash('sha256', CanonicalJson::encode($qualification)), 'sealed' => true,
            'foundry_construction_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false];
        $packet['record_digest'] = hash('sha256', CanonicalJson::encode($packet)); mkdir($root.'/var/imperium/mastermason/qualified-manifestations', 0770, true);
        file_put_contents($root.'/var/imperium/mastermason/qualified-manifestations/'.$deliveryId.'.json', json_encode($packet, JSON_THROW_ON_ERROR));
        try {
            $service = new ArtificerSeatBindingService($root, $bootstrap, $registry, $substrate); $occupancy = $service->bind($deliveryId);
            self::assertSame($occupancy, $service->bind($deliveryId)); self::assertSame('imperium.foundry-artificer-occupancy/v1', $occupancy['schema']);
            self::assertSame('foundry.artificer', $occupancy['seat']); self::assertSame($manifestationId, $occupancy['manifestation_id']); self::assertSame('ACTIVE', $occupancy['status']);
            self::assertSame(0, $occupancy['prior_occupancy_generation']); self::assertSame(1, $occupancy['occupancy_generation']); self::assertTrue($occupancy['binding_atomic']);
            self::assertTrue($occupancy['seat_binding_authority']); self::assertSame('CONSUMED_BY_ATOMIC_BINDING', $occupancy['seat_binding_disposition']);
            self::assertTrue($occupancy['foundry_construction_authority']); self::assertFalse($occupancy['recipient_acceptance']); self::assertFalse($occupancy['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/foundry/occupancy/'.$occupancy['binding_id'].'.json');
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
