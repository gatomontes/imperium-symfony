<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use App\Imperium\Runtime\Garrison\CanonicalConstableRegistry;
use App\Imperium\Runtime\Garrison\ConstableConstructionCommissionService;
use PHPUnit\Framework\TestCase;

final class ConstableConstructionCommissionServiceTest extends TestCase
{
    public function testIssuesOneExactNonBindingConstableConstructionCommission(): void
    {
        $root = sys_get_temp_dir().'/imperium-constable-commission-'.bin2hex(random_bytes(6));
        $project = dirname(__DIR__, 3);
        $registry = new CanonicalConstableRegistry($project);
        $caseId = 'constable-provisioning-1234567890abcdef1234';
        $case = [
            'schema' => 'imperium.garrison-constable-provisioning-case/v1', 'case_id' => $caseId, 'instance_id' => 'imperium-test',
            'source_inquiry_id' => 'garrison-inquiry-1234567890abcdef1234', 'source_inquiry_digest' => str_repeat('a', 64), 'coordinator' => 'mastermason',
            'target_seat' => 'garrison.constable', 'canonical_constable_package' => $registry->current(), 'member' => $registry->member(),
            'status' => 'CANONICAL_CONSTABLE_READY', 'mission_persona_selection_required' => false, 'per_mission_profile_derivation_required' => false,
            'spawning_authority' => false, 'seat_binding_authority' => false, 'inventory_response_authority' => false, 'execution_authority' => false,
        ];
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case));
        mkdir($root.'/var/imperium/mastermason/activation-cases', 0770, true);
        mkdir($root.'/runtime/artifacts', 0770, true);
        file_put_contents($root.'/var/imperium/mastermason/activation-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR));
        copy($project.'/runtime/artifacts/generic-officer-substrate.json', $root.'/runtime/artifacts/generic-officer-substrate.json');
        try {
            $service = new ConstableConstructionCommissionService($root, $registry, new GenericOfficerSubstrateRegistry($root));
            $commission = $service->issue($caseId);
            self::assertSame($commission, $service->issue($caseId));
            self::assertSame('ISSUED_PENDING_CONSCRIPTION', $commission['status']);
            self::assertSame('garrison.constable', $commission['target_seat']);
            self::assertSame($caseId, $commission['source_provisioning_case_id']);
            self::assertSame($registry->member()['persona'], $commission['persona']);
            self::assertSame($registry->member()['profile'], $commission['profile']);
            self::assertTrue($commission['spawning_authority']);
            self::assertFalse($commission['seat_binding_authority']);
            self::assertFalse($commission['inventory_response_authority']);
            self::assertFalse($commission['execution_authority']);
            self::assertFileExists($root.'/var/imperium/offices/conscription/inbox/'.$commission['commission_id'].'.json');
        } finally { $this->removeTree($root); }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); }
        rmdir($path);
    }
}
