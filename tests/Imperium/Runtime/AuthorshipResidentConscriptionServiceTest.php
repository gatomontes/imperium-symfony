<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;
use App\Imperium\Runtime\Authorship\CanonicalAuthorshipStaffRegistry;
use App\Imperium\Runtime\Conscription\AuthorshipResidentConscriptionService;
use App\Imperium\Runtime\Conscription\GenericOfficerSubstrateRegistry;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AuthorshipResidentConscriptionServiceTest extends TestCase
{
    #[DataProvider('offices')]
    public function testConsumesExactCommissionAndReturnsQualifiedUnboundResident(string $office, string $role, string $seat, string $subordinate): void
    {
        $root = sys_get_temp_dir().'/imperium-'.$role.'-conscription-'.bin2hex(random_bytes(6)); $project = dirname(__DIR__, 3); $bootstrap = new StateStore($root); $staff = new CanonicalAuthorshipStaffRegistry($project);
        $bootstrap->locked(static function () use ($bootstrap): void { $bootstrap->write(['state' => 'CURIA_READY', 'binding' => ['instance_id' => 'imperium-test'], 'events' => [['transition' => 'T04', 'result' => 'SUCCESS', 'output' => ['successor' => ['manifestation_id' => 'imperium-test.officer.ordinary-recruiter.1', 'seat' => 'conscription.recruiter', 'occupancy_generation' => 2, 'authority' => 'ordinary-recruiter']]]]]); });
        mkdir($root.'/runtime/artifacts', 0770, true); copy($project.'/runtime/artifacts/generic-officer-substrate.json', $root.'/runtime/artifacts/generic-officer-substrate.json'); $substrate = new GenericOfficerSubstrateRegistry($root);
        $caseId = $office.'-provisioning-1234567890abcdef1234'; $case = ['case_id' => $caseId, 'office' => $office, 'seat' => $seat, 'status' => 'CANONICAL_STAFF_READY']; $case['record_digest'] = hash('sha256', CanonicalJson::encode($case)); mkdir($root.'/var/imperium/mastermason/activation-cases', 0770, true); file_put_contents($root.'/var/imperium/mastermason/activation-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR));
        $member = $staff->member($office); $commissionId = $role.'-construction-1234567890abcdef1234'; $commission = ['schema' => 'imperium.construction-commission/v1', 'commission_id' => $commissionId, 'issuer' => 'mastermason', 'source_provisioning_case_id' => $caseId, 'source_provisioning_case_digest' => $case['record_digest'], 'office' => $office, 'target_seat' => $seat, 'persona' => $member['persona'], 'profile' => $member['profile'], 'qualification_contract' => $member['qualification_contract'], 'substrate' => $substrate->current(), 'status' => 'ISSUED_PENDING_CONSCRIPTION', 'spawning_authority' => true, 'authorship_authority' => false, 'subordinate_staff_resolution_authority' => false, 'subordinate_staff_class' => $subordinate, 'seat_binding_authority' => false, 'recipient_acceptance' => false, 'execution_authority' => false]; $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission)); mkdir($root.'/var/imperium/offices/conscription/inbox', 0770, true); file_put_contents($root.'/var/imperium/offices/conscription/inbox/'.$commissionId.'.json', json_encode($commission, JSON_THROW_ON_ERROR));
        try { $service = new AuthorshipResidentConscriptionService($root, $bootstrap, $staff, $substrate); $result = $service->fulfill($office, $commissionId); $delivery = $result['delivery']; self::assertSame($result, $service->fulfill($office, $commissionId)); self::assertSame($seat, $delivery['candidate']['target_seat']); self::assertSame('QUALIFIED_UNBOUND', $delivery['candidate']['status']); self::assertSame('PROFILE_INSTALLED', $delivery['candidate']['substrate_instance']['status']); self::assertSame('QUALIFIED', $delivery['qualification']['disposition']); self::assertTrue($delivery['sealed']); self::assertFalse($delivery['authorship_authority']); self::assertFalse($delivery['subordinate_staff_resolution_authority']); self::assertFalse($delivery['seat_binding_authority']); self::assertFalse($delivery['recipient_acceptance']); self::assertFalse($delivery['execution_authority']); self::assertFileExists($root.'/var/imperium/mastermason/qualified-manifestations/'.$delivery['delivery_id'].'.json'); } finally { $this->removeTree($root); }
    }
    public static function offices(): array { return [['hagiography', 'sanctographer', 'hagiography.sanctographer', 'Chronicler'], ['studium', 'chancellor', 'studium.chancellor', 'Notary']]; }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
