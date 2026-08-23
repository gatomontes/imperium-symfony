<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationRequestService;
use PHPUnit\Framework\TestCase;

final class SubordinateConstructionAuthorizationRequestServiceTest extends TestCase
{
    public function testCuriaPreservesIndependentExactResolutionsWithoutGrantingAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-subordinate-request-'.bin2hex(random_bytes(6)); $ids = [];
        foreach ([['hagiography', 'Chronicler'], ['studium', 'Notary']] as [$office, $class]) {
            $id = $office.'-subordinate-resolution-'.str_repeat('a', 20); $ids[] = $id;
            $record = ['schema' => 'imperium.authorship-subordinate-resolution/v1', 'resolution_id' => $id, 'instance_id' => 'imperium-test', 'office' => $office,
                'acceptance_id' => $office.'-acceptance-'.str_repeat('b', 20), 'commission_id' => 'authorship-'.$office.'-'.str_repeat('c', 20), 'subordinate_staff_class' => $class,
                'decision' => ['disposition' => 'SUBORDINATE_REQUIREMENTS_DETERMINED', 'required_specializations' => [$class.' exact bounded research']],
                'status' => 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION', 'subordinate_staff_resolution_complete' => true, 'construction_request_authority' => true,
                'construction_authority' => false, 'persona_selection_authority' => false, 'profile_approval_authority' => false, 'spawning_authority' => false,
                'seat_binding_authority' => false, 'execution_authority' => false, 'sealed' => true];
            $record['record_digest'] = hash('sha256', CanonicalJson::encode($record)); $dir = $root.'/var/imperium/offices/'.$office.'/subordinate-resolutions'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$id.'.json', json_encode($record, JSON_THROW_ON_ERROR));
        }
        try {
            $service = new SubordinateConstructionAuthorizationRequestService($root); $request = $service->request($ids);
            self::assertSame($request, $service->request($ids)); self::assertSame('PENDING_IMPERATOR_DECISION', $request['status']); self::assertCount(2, $request['resolutions']);
            self::assertSame($ids, array_column($request['resolutions'], 'resolution_id')); self::assertSame(['Chronicler exact bounded research'], $request['resolutions'][0]['required_specializations']);
            self::assertSame(['Notary exact bounded research'], $request['resolutions'][1]['required_specializations']); self::assertSame('EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY', $request['requested_authority']);
            self::assertFalse($request['approval_recorded']); self::assertFalse($request['construction_authority']); self::assertFalse($request['persona_selection_authority']);
            self::assertFalse($request['profile_approval_authority']); self::assertFalse($request['spawning_authority']); self::assertFalse($request['seat_binding_authority']); self::assertFalse($request['execution_authority']);
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
