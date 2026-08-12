<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Authorship\AuthorshipOfficeActivationDemandService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorshipOfficeActivationDemandServiceTest extends TestCase
{
    #[DataProvider('offices')]
    public function testDemandsOnlyCanonicalResidentWithoutResolvingSubordinateStaff(string $office, string $seat, string $class): void
    {
        $root = sys_get_temp_dir().'/imperium-'.$office.'-activation-'.bin2hex(random_bytes(6)); $commissionId = 'authorship-'.$office.'-1234567890abcdef1234';
        $commission = ['schema' => 'imperium.specialized-authorship-commission/v1', 'commission_id' => $commissionId, 'office' => $office, 'target_seat' => $seat,
            'instance_id' => 'imperium-test', 'production_case_id' => 'persona-production-1234567890abcdef1234', 'authorship_class' => $class,
            'status' => 'ISSUED_PENDING_RECIPIENT', 'authorship_authority' => true, 'recipient_acceptance' => null,
            'persona_selection_authority' => false, 'persona_assembly_authority' => false, 'spawning_authority' => false,
            'admission_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission)); mkdir($root.'/var/imperium/offices/'.$office.'/inbox', 0770, true);
        file_put_contents($root.'/var/imperium/offices/'.$office.'/inbox/'.$commissionId.'.json', json_encode($commission, JSON_THROW_ON_ERROR));
        try {
            $service = new AuthorshipOfficeActivationDemandService($root); $demand = $service->demand($office, $commissionId); self::assertSame($demand, $service->demand($office, $commissionId));
            self::assertSame('CANONICAL_STAFF_ARTIFACTS_REQUIRED', $demand['status']); self::assertSame($seat, $demand['required_seats'][0]['seat']); self::assertCount(1, $demand['required_seats']);
            self::assertTrue($demand['authorship_authority']); self::assertFalse($demand['authorship_authority_exercisable']); self::assertFalse($demand['mission_persona_selection_required']);
            self::assertTrue($demand['subordinate_staff_resolution_pending']); self::assertFalse($demand['spawning_authority']); self::assertFalse($demand['seat_binding_authority']); self::assertFalse($demand['recipient_acceptance']); self::assertFalse($demand['execution_authority']);
            self::assertFileExists($root.'/var/imperium/mastermason/spawning-requests/'.$demand['demand_id'].'.json');
        } finally { $this->removeTree($root); }
    }
    public static function offices(): iterable { yield 'hagiography' => ['hagiography', 'hagiography.sanctographer', 'EVIDENCE_DERIVED_PERSONA_SECTIONS']; yield 'studium' => ['studium', 'studium.chancellor', 'PERSONA_GOVERNANCE_DOCTRINE_SECTIONS']; }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
