<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\AuthorshipResidentConstructionCommissionService;
use App\Imperium\Runtime\Authorship\CanonicalAuthorshipStaffRegistry;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorshipResidentConstructionCommissionServiceTest extends TestCase
{
    #[DataProvider('offices')]
    public function testIssuesOnlyExactResidentConstructionAuthority(string $office, string $role, string $seat): void
    {
        $root = sys_get_temp_dir().'/imperium-authorship-resident-'.bin2hex(random_bytes(6)); $project = dirname(__DIR__, 3); $staff = new CanonicalAuthorshipStaffRegistry($project);
        $caseId = $office.'-provisioning-1234567890abcdef1234'; $case = ['schema' => 'imperium.office-provisioning-case/v1', 'case_id' => $caseId, 'instance_id' => 'imperium-local', 'office' => $office, 'seat' => $seat,
            'canonical_staff_package' => $staff->current($office), 'status' => 'CANONICAL_STAFF_READY', 'authorship_authority' => true, 'authorship_authority_exercisable' => false,
            'mission_persona_selection_required' => false, 'per_mission_profile_derivation_required' => false, 'subordinate_staff_resolution_pending' => true,
            'commission_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false];
        $case['record_digest'] = hash('sha256', CanonicalJson::encode($case)); mkdir($root.'/var/imperium/mastermason/activation-cases', 0770, true); mkdir($root.'/runtime/artifacts', 0770, true);
        file_put_contents($root.'/var/imperium/mastermason/activation-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR)); copy($project.'/runtime/artifacts/generic-officer-substrate.json', $root.'/runtime/artifacts/generic-officer-substrate.json');
        try { $service = new AuthorshipResidentConstructionCommissionService($root, $staff, new GenericOfficerSubstrateRegistry($root)); $commission = $service->issue($office, $caseId);
            self::assertSame($commission, $service->issue($office, $caseId)); self::assertStringStartsWith($role.'-construction-', $commission['commission_id']); self::assertSame($seat, $commission['target_seat']); self::assertSame('ISSUED_PENDING_CONSCRIPTION', $commission['status']);
            self::assertTrue($commission['spawning_authority']); self::assertFalse($commission['authorship_authority']); self::assertFalse($commission['subordinate_staff_resolution_authority']); self::assertFalse($commission['seat_binding_authority']); self::assertFalse($commission['recipient_acceptance']); self::assertFalse($commission['execution_authority']);
        } finally { $this->removeTree($root); }
    }
    public static function offices(): array { return [['hagiography', 'sanctographer', 'hagiography.sanctographer'], ['studium', 'chancellor', 'studium.chancellor']]; }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
