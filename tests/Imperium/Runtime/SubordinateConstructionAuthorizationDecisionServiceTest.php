<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationDecisionService;
use PHPUnit\Framework\TestCase;

final class SubordinateConstructionAuthorizationDecisionServiceTest extends TestCase
{
    public function testImperatorAuthorizesOnlyTheExactUnchangedResolutionSet(): void
    {
        $root = sys_get_temp_dir().'/imperium-subordinate-decision-'.bin2hex(random_bytes(6)); $references = [];
        foreach ([['hagiography', 'Chronicler'], ['studium', 'Notary']] as [$office, $class]) {
            $id = $office.'-subordinate-resolution-'.str_repeat('a', 20); $specializations = [$class.' bounded research'];
            $resolution = ['schema' => 'imperium.authorship-subordinate-resolution/v1', 'resolution_id' => $id, 'instance_id' => 'imperium-test', 'office' => $office, 'acceptance_id' => $office.'-acceptance-'.str_repeat('b', 20),
                'commission_id' => 'authorship-'.$office.'-'.str_repeat('c', 20), 'subordinate_staff_class' => $class, 'decision' => ['disposition' => 'SUBORDINATE_REQUIREMENTS_DETERMINED', 'required_specializations' => $specializations],
                'status' => 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION', 'subordinate_staff_resolution_complete' => true, 'construction_request_authority' => true, 'construction_authority' => false,
                'persona_selection_authority' => false, 'profile_approval_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false, 'sealed' => true];
            $resolution['record_digest'] = hash('sha256', CanonicalJson::encode($resolution)); $dir = $root.'/var/imperium/offices/'.$office.'/subordinate-resolutions'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$id.'.json', json_encode($resolution, JSON_THROW_ON_ERROR));
            $references[] = ['resolution_id' => $id, 'record_digest' => $resolution['record_digest'], 'office' => $office, 'acceptance_id' => $resolution['acceptance_id'], 'commission_id' => $resolution['commission_id'], 'subordinate_staff_class' => $class, 'required_specializations' => $specializations];
        }
        $requestId = 'subordinate-construction-request-'.str_repeat('d', 20); $request = ['schema' => 'imperium.curia-subordinate-construction-authorization-request/v1', 'request_id' => $requestId,
            'instance_id' => 'imperium-test', 'recipient' => ['kind' => 'imperator', 'id' => 'imperator-development-root'], 'resolutions' => $references,
            'requested_authority' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY', 'status' => 'PENDING_IMPERATOR_DECISION', 'approval_recorded' => false,
            'construction_authority' => false, 'persona_selection_authority' => false, 'profile_approval_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $request['record_digest'] = hash('sha256', CanonicalJson::encode($request)); $dir = $root.'/var/imperium/curia/subordinate-construction-requests'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$requestId.'.json', json_encode($request, JSON_THROW_ON_ERROR));
        try { $service = new SubordinateConstructionAuthorizationDecisionService($root); $act = $service->authorize($requestId); self::assertSame($act, $service->authorize($requestId));
            self::assertSame('AUTHORIZED_FOR_EXACT_RESOLUTIONS', $act['disposition']); self::assertSame($references, $act['resolutions']); self::assertTrue($act['construction_authority']);
            self::assertFalse($act['persona_selection_authority']); self::assertFalse($act['profile_approval_authority']); self::assertFalse($act['spawning_authority']); self::assertFalse($act['seat_binding_authority']); self::assertFalse($act['execution_authority']);
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
