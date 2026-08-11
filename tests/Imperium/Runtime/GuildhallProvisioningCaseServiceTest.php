<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Guildhall\GuildhallProvisioningCaseService;
use App\Imperium\Runtime\Guildhall\CanonicalGuildhallStaffRegistry;
use App\Imperium\Runtime\Guildhall\ProfileDefinitionRegistry;
use PHPUnit\Framework\TestCase;

final class GuildhallProvisioningCaseServiceTest extends TestCase
{
    public function testOpensFourCanonicalStaffLanesUnderTheSeneschalSummoningRule(): void
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-provisioning-'.bin2hex(random_bytes(6));
        $demandId = 'guildhall-activation-1234567890abcdef1234';
        $directory = $root.'/var/imperium/mastermason/spawning-requests';
        mkdir($directory, 0770, true);
        $definitions = new ProfileDefinitionRegistry(dirname(__DIR__, 3));
        $seats = [];
        foreach ([
            ['guildmaster', 'guildhall.guildmaster'],
            ['committee-disciplinary-fit', 'guildhall.committee.disciplinary-fit'],
            ['committee-composition', 'guildhall.committee.composition'],
            ['committee-boundary-challenge', 'guildhall.committee.boundary-challenge'],
        ] as [$name, $seat]) {
            $seats[] = ['seat' => $seat, 'profile_definition' => $definitions->current($name, $seat)];
        }
        $demand = [
            'schema' => 'imperium.office-activation-demand/v1',
            'demand_id' => $demandId,
            'recipient' => 'mastermason',
            'office' => 'guildhall',
            'required_seats' => $seats,
            'status' => 'PROFILE_DEFINITIONS_READY',
            'spawning_authority' => false,
            'recipient_acceptance' => false,
            'execution_authority' => false,
        ];
        $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
        file_put_contents($directory.'/'.$demandId.'.json', json_encode($demand, JSON_THROW_ON_ERROR));

        try {
            $service = new GuildhallProvisioningCaseService($root, $definitions, new CanonicalGuildhallStaffRegistry(dirname(__DIR__, 3), $definitions));
            $case = $service->open($demandId);
            self::assertSame($case, $service->open($demandId));
            self::assertSame('CANONICAL_STAFF_READY', $case['status']);
            self::assertCount(4, $case['lanes']);
            self::assertFalse($case['lanes'][0]['canonical_staff_requirement']['mission_persona_selection']);
            self::assertSame('admitted', $case['lanes'][0]['canonical_staff_requirement']['persona_admission_state']);
            self::assertSame('CANONICAL_STAFF_READY', $case['lanes'][0]['canonical_staff_requirement']['status']);
            self::assertSame('guildhall.canonical-staff', $case['canonical_staff_package']['package_id']);
            self::assertSame('curia.seneschal', $case['summoning_rule']['requester']);
            self::assertSame('curia.chamberlain', $case['summoning_rule']['router']);
            self::assertSame('mastermason', $case['summoning_rule']['runtime_executor']);
            self::assertSame('conscription', $case['summoning_rule']['manifestation_constructor']);
            self::assertFalse($case['mission_persona_selection_required']);
            self::assertFalse($case['per_mission_profile_derivation_required']);
            self::assertFalse($case['activation_request_recorded']);
            self::assertFalse($case['spawning_authority']);
            self::assertFalse($case['recipient_acceptance']);
            self::assertFalse($case['execution_authority']);
            self::assertFileExists($root.'/var/imperium/mastermason/activation-cases/'.$case['case_id'].'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->removeTree($child) : unlink($child);
        }
        rmdir($path);
    }
}
