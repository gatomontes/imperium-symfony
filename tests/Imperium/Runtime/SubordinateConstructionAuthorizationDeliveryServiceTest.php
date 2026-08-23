<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\SubordinateConstructionAuthorizationDeliveryService;
use App\Imperium\Runtime\Guildhall\SubordinatePersonnelConstructionCommissionService;
use PHPUnit\Framework\TestCase;

final class SubordinateConstructionAuthorizationDeliveryServiceTest extends TestCase
{
    public function testDeliveryPreservesExactActAndRequiresFoundryAcceptance(): void
    {
        $root = sys_get_temp_dir().'/imperium-subordinate-delivery-'.bin2hex(random_bytes(6)); $office = 'hagiography'; $resolutionId = $office.'-subordinate-resolution-'.str_repeat('a', 20); $specializations = ['Chronicler exact research'];
        $resolution = ['resolution_id' => $resolutionId, 'instance_id' => 'imperium-test', 'office' => $office, 'decision' => ['required_specializations' => $specializations], 'status' => 'PENDING_CURIA_SUBORDINATE_CONSTRUCTION_AUTHORIZATION',
            'sealed' => true, 'construction_authority' => false, 'persona_selection_authority' => false, 'profile_approval_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $resolution['record_digest'] = hash('sha256', CanonicalJson::encode($resolution)); $dir = $root.'/var/imperium/offices/'.$office.'/subordinate-resolutions'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$resolutionId.'.json', json_encode($resolution, JSON_THROW_ON_ERROR));
        $reference = ['resolution_id' => $resolutionId, 'record_digest' => $resolution['record_digest'], 'office' => $office, 'acceptance_id' => 'acceptance', 'commission_id' => 'commission', 'subordinate_staff_class' => 'Chronicler', 'required_specializations' => $specializations];
        $requestId = 'subordinate-construction-request-'.str_repeat('b', 20); $request = ['request_id' => $requestId, 'instance_id' => 'imperium-test', 'resolutions' => [$reference]]; $request['record_digest'] = hash('sha256', CanonicalJson::encode($request));
        $dir = $root.'/var/imperium/curia/subordinate-construction-requests'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$requestId.'.json', json_encode($request, JSON_THROW_ON_ERROR));
        $actId = 'subordinate-construction-authorization-'.str_repeat('c', 20); $act = ['schema' => 'imperium.imperator-subordinate-construction-authorization/v1', 'kind' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_AUTHORIZATION', 'act_id' => $actId,
            'instance_id' => 'imperium-test', 'source_request_id' => $requestId, 'source_request_digest' => $request['record_digest'], 'resolutions' => [$reference], 'disposition' => 'AUTHORIZED_FOR_EXACT_RESOLUTIONS',
            'authorized_authority' => 'EXACT_SUBORDINATE_PERSONA_CONSTRUCTION_ONLY', 'construction_authority' => true, 'persona_selection_authority' => false, 'profile_approval_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $act['record_digest'] = hash('sha256', CanonicalJson::encode($act)); $dir = $root.'/var/imperium/curia/subordinate-construction-decisions'; mkdir($dir, 0770, true); file_put_contents($dir.'/'.$actId.'.json', json_encode($act, JSON_THROW_ON_ERROR));
        try { $service = new SubordinateConstructionAuthorizationDeliveryService($root); $delivery = $service->deliver($actId); self::assertSame($delivery, $service->deliver($actId));
            self::assertSame('DELIVERED_PENDING_GUILDHALL_COMMISSION', $delivery['status']); self::assertNull($delivery['recipient_acceptance']); self::assertTrue($delivery['personnel_commission_authority']); self::assertFalse($delivery['construction_authority']); self::assertFalse($delivery['construction_authority_exercisable']);
            self::assertSame([$reference], $delivery['authorized_resolutions']); self::assertFalse($delivery['persona_selection_authority']); self::assertFalse($delivery['profile_approval_authority']); self::assertFalse($delivery['spawning_authority']); self::assertFalse($delivery['seat_binding_authority']); self::assertFalse($delivery['execution_authority']);
            $commissionService = new SubordinatePersonnelConstructionCommissionService($root); $commission = $commissionService->commission($delivery['delivery_id']); self::assertSame($commission, $commissionService->commission($delivery['delivery_id'])); self::assertSame('COMMISSIONED_PENDING_FOUNDRY_ACCEPTANCE', $commission['status']); self::assertSame('foundry.artificer', $commission['recipient']['seat']); self::assertSame($delivery['record_digest'], $commission['source_authorization_delivery_digest']); self::assertTrue($commission['construction_authority']); self::assertFalse($commission['construction_authority_exercisable']);
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
