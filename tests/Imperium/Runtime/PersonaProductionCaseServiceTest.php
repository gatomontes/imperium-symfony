<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\PersonaProductionCaseService;
use PHPUnit\Framework\TestCase;

final class PersonaProductionCaseServiceTest extends TestCase
{
    public function testOpensOneBoundedCasePerExactAuthorizedDemand(): void
    {
        $root = sys_get_temp_dir().'/imperium-production-cases-'.bin2hex(random_bytes(6)); $acceptanceId = 'foundry-acceptance-1234567890abcdef1234';
        $professions = ['Cybersecurity / web application security assessment', 'Application-owner coordination / scope confirmation']; $references = [];
        mkdir($root.'/var/imperium/offices/foundry/inbox', 0770, true);
        foreach ($professions as $index => $profession) {
            $demandId = 'foundry-persona-demand-'.str_pad((string) ($index + 1), 20, (string) ($index + 1));
            $demand = ['schema' => 'imperium.foundry-persona-construction-demand/v1', 'demand_id' => $demandId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test',
                'source_disposition_id' => 'personnel-disposition-test', 'source_disposition_digest' => str_repeat('a', 64), 'profession' => $profession,
                'exemplar_criteria' => ['evidence-led'], 'team_composition' => $professions, 'boundary_controls' => ['passive-only'],
                'status' => 'PENDING_CURIA_CONSTRUCTION_AUTHORIZATION', 'construction_authority' => false, 'persona_selection_authority' => false,
                'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
            $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand)); file_put_contents($root.'/var/imperium/offices/foundry/inbox/'.$demandId.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
            $references[] = ['demand_id' => $demandId, 'profession' => $profession, 'record_digest' => $demand['record_digest']];
        }
        $acceptance = ['schema' => 'imperium.foundry-authorization-acceptance/v1', 'acceptance_id' => $acceptanceId, 'instance_id' => 'imperium-test',
            'actor' => ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1],
            'disposition' => 'ACCEPTED_FOR_EXACT_CONSTRUCTION', 'authorized_demands' => $references, 'recipient_acceptance' => true, 'foundry_construction_authority' => true,
            'persona_selection_authority' => false, 'spawning_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false];
        $acceptance['record_digest'] = hash('sha256', CanonicalJson::encode($acceptance)); mkdir($root.'/var/imperium/offices/foundry/acceptances', 0770, true);
        file_put_contents($root.'/var/imperium/offices/foundry/acceptances/'.$acceptanceId.'.json', json_encode($acceptance, JSON_THROW_ON_ERROR));
        try {
            $service = new PersonaProductionCaseService($root); $result = $service->open($acceptanceId); self::assertSame($result, $service->open($acceptanceId)); self::assertCount(2, $result['cases']);
            foreach ($result['cases'] as $index => $case) { self::assertSame($index + 1, $case['queue_position']); self::assertSame($professions[$index], $case['profession']); self::assertSame('OPEN_PENDING_SPECIALIZED_INPUTS', $case['status']);
                self::assertTrue($case['construction_authority']); self::assertFalse($case['persona_selection_authority']); self::assertFalse($case['spawning_authority']); self::assertFalse($case['admission_authority']); self::assertFalse($case['execution_authority']);
                self::assertFileExists($root.'/var/imperium/offices/foundry/production-cases/'.$case['case_id'].'.json'); }
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
