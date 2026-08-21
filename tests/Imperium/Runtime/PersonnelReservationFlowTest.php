<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationDecisionService;
use App\Imperium\Runtime\Curia\PersonnelUseAuthorizationRequestService;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Garrison\PersonaReservationDispositionService;
use App\Imperium\Runtime\Guildhall\PersonnelUseAuthorizationAcceptanceService;
use PHPUnit\Framework\TestCase;

final class PersonnelReservationFlowTest extends TestCase
{
    public function testAuthorizedExactPersonaIsReservedPendingProfileDerivationAuthorization(): void
    {
        [$root, $act, $bindingId] = $this->fixture('AUTHORIZED', true);
        try {
            $acceptanceService = new PersonnelUseAuthorizationAcceptanceService($root);
            $result = $acceptanceService->accept($act['act_id']);
            self::assertSame($result, $acceptanceService->accept($act['act_id']));
            self::assertSame('AUTHORIZED_PERSONNEL_ACCEPTED_PENDING_GARRISON_RESERVATION', $result['acceptance']['status']);
            self::assertTrue($result['acceptance']['garrison_reservation_request_authority']);
            self::assertFalse($result['acceptance']['reservation_authority']);
            self::assertCount(1, $result['reservation_requests']);

            $request = $result['reservation_requests'][0];
            self::assertSame('Web application security assessor', $request['personnel_commitment']['profession']);
            self::assertSame('persona-test', $request['personnel_commitment']['persona']['persona_id']);
            self::assertFalse($request['reservation_authority']);

            $service = new PersonaReservationDispositionService($root);
            $disposition = $service->decide($request['request_id'], $bindingId);
            self::assertSame($disposition, $service->decide($request['request_id'], $bindingId));
            self::assertSame('RESERVED', $disposition['disposition']);
            self::assertSame('RESERVED_PENDING_PROFILE_DERIVATION_AUTHORIZATION', $disposition['status']);
            self::assertTrue($disposition['persona_reserved']);
            self::assertFalse($disposition['retrieval_authority']);
            self::assertFalse($disposition['profile_derivation_authority']);
            self::assertFalse($disposition['spawning_authority']);
            self::assertFalse($disposition['seat_binding_authority']);
            self::assertFalse($disposition['deployment_authority']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testUnavailablePersonaReceivesFactualNonAuthorizingRefusal(): void
    {
        [$root, $act, $bindingId] = $this->fixture('AUTHORIZED', false);
        try {
            $request = (new PersonnelUseAuthorizationAcceptanceService($root))->accept($act['act_id'])['reservation_requests'][0];
            $disposition = (new PersonaReservationDispositionService($root))->decide($request['request_id'], $bindingId);
            self::assertSame('PERSONA_UNAVAILABLE', $disposition['disposition']);
            self::assertSame('REFUSED_PERSONA_UNAVAILABLE', $disposition['status']);
            self::assertFalse($disposition['persona_reserved']);
            self::assertFalse($disposition['profile_derivation_authority']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testMissingCustodyReceivesNotAdmittedRefusal(): void
    {
        [$root, $act, $bindingId] = $this->fixture('AUTHORIZED', null);
        try {
            $request = (new PersonnelUseAuthorizationAcceptanceService($root))->accept($act['act_id'])['reservation_requests'][0];
            $disposition = (new PersonaReservationDispositionService($root))->decide($request['request_id'], $bindingId);
            self::assertSame('PERSONA_NOT_ADMITTED', $disposition['disposition']);
            self::assertSame('REFUSED_PERSONA_NOT_ADMITTED', $disposition['status']);
            self::assertFalse($disposition['persona_reserved']);
            self::assertNull($disposition['custody_digest']);
        } finally {
            $this->removeTree($root);
        }
    }

    public function testGuildhallCannotAcceptNonAuthorizingImperatorDisposition(): void
    {
        [$root, $act] = $this->fixture('ALTERNATIVE_PROPOSED', true);
        try {
            $this->expectExceptionMessage('G83_PERSONNEL_USE_CHAIN_INVALID');
            (new PersonnelUseAuthorizationAcceptanceService($root))->accept($act['act_id']);
        } finally {
            $this->removeTree($root);
        }
    }

    private function fixture(string $imperatorDisposition, ?bool $custodyAvailable): array
    {
        $root = sys_get_temp_dir().'/imperium-personnel-reservation-'.bin2hex(random_bytes(6));
        $store = new ProceedingStore($root);
        $store->persist(['proceeding_id' => 'proceeding-test', 'instance_id' => 'imperium-test']);
        $dispositionId = 'personnel-use-disposition-'.str_repeat('a', 20);
        $commitment = [
            'capability_slot_id' => 'slot-passive-assessment',
            'capability_requirements' => ['Analyze public application behavior', 'Produce evidence-bound findings'],
            'profession' => 'Web application security assessor',
            'persona' => ['custody_id' => 'persona-custody-test', 'persona_id' => 'persona-test'],
            'suitability_determination' => 'Exact admitted Persona satisfies the profession and capability constraints.',
            'guildhall_resolution_digest' => str_repeat('b', 64),
        ];
        $guildhallDisposition = [
            'schema' => 'imperium.guildhall-personnel-use-disposition/v1',
            'disposition_id' => $dispositionId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => 'proceeding-test',
            'translation_boundary' => ['name' => 'CAPABILITY_TO_PROFESSION', 'authority' => 'guildhall.guildmaster'],
            'resolved_capability_slots' => [$commitment],
            'disposition' => 'CAPABILITY_SLOTS_RESOLVED_PENDING_USE_AUTHORIZATION',
            'final_personnel_disposition' => true,
            'personnel_use_request_authority' => true,
            'reservation_authority' => false,
            'profile_derivation_authority' => false,
            'spawning_authority' => false,
            'seat_binding_authority' => false,
            'execution_authority' => false,
            'sealed' => true,
        ];
        $this->write($root.'/var/imperium/offices/guildhall/personnel-use-dispositions/'.$dispositionId.'.json', $guildhallDisposition);
        $request = (new PersonnelUseAuthorizationRequestService($root, $store))->request($dispositionId);
        $act = (new PersonnelUseAuthorizationDecisionService($root))->decide($request['request_id'], $imperatorDisposition, 'Exact Imperator personnel-use disposition.');

        $bindingId = 'garrison-constable-binding-test';
        $this->write($root.'/var/imperium/offices/garrison/occupancy/'.$bindingId.'.json', [
            'schema' => 'imperium.garrison-constable-occupancy/v1',
            'binding_id' => $bindingId,
            'instance_id' => 'imperium-test',
            'seat' => 'garrison.constable',
            'manifestation_id' => 'constable-test',
            'occupancy_generation' => 1,
            'status' => 'ACTIVE',
            'persona_reservation_disposition_authority' => true,
            'selection_authority' => false,
            'execution_authority' => false,
        ]);
        if (null !== $custodyAvailable) {
            $this->write($root.'/var/imperium/offices/garrison/custody/persona-custody-test.json', [
                'schema' => 'imperium.garrison-persona-custody/v1',
                'custody_id' => 'persona-custody-test',
                'instance_id' => 'imperium-test',
                'persona_id' => 'persona-test',
                'persona_version' => '1',
                'persona_digest' => str_repeat('c', 64),
                'custody_state' => 'ADMITTED_HELD',
                'available' => $custodyAvailable,
                'execution_authority' => false,
                'sealed' => true,
            ]);
        }
        return [$root, $act, $bindingId];
    }

    private function write(string $path, array $record): void
    {
        mkdir(dirname($path), 0770, true);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) return;
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
