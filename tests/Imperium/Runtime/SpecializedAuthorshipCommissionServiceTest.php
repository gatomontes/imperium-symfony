<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Foundry\SpecializedAuthorshipCommissionService;
use PHPUnit\Framework\TestCase;

final class SpecializedAuthorshipCommissionServiceTest extends TestCase
{
    public function testDispatchesTwoExactNonExecutingAuthorshipCommissions(): void
    {
        $root = sys_get_temp_dir().'/imperium-specialized-authorship-'.bin2hex(random_bytes(6)); $caseId = 'persona-production-1234567890abcdef1234';
        $demandId = 'foundry-persona-demand-1234567890abcdef1234'; $acceptanceId = 'foundry-acceptance-1234567890abcdef1234'; $profession = 'Cybersecurity assessment';
        $demand = ['demand_id' => $demandId, 'profession' => $profession]; $demand['record_digest'] = hash('sha256', CanonicalJson::encode($demand));
        $reference = ['demand_id' => $demandId, 'record_digest' => $demand['record_digest'], 'profession' => $profession];
        $acceptance = ['acceptance_id' => $acceptanceId, 'disposition' => 'ACCEPTED_FOR_EXACT_CONSTRUCTION', 'authorized_demands' => [$reference]]; $acceptance['record_digest'] = hash('sha256', CanonicalJson::encode($acceptance));
        $artificer = ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1];
        $case = ['schema' => 'imperium.foundry-persona-production-case/v1', 'case_id' => $caseId, 'instance_id' => 'imperium-test', 'proceeding_id' => 'proceeding-test', 'queue_position' => 1,
            'profession' => $profession, 'source_demand_id' => $demandId, 'source_demand_digest' => $demand['record_digest'], 'authorization_acceptance_id' => $acceptanceId,
            'authorization_acceptance_digest' => $acceptance['record_digest'], 'artificer' => $artificer, 'exemplar_criteria' => ['evidence-led'], 'team_composition' => [$profession],
            'boundary_controls' => ['passive-only'], 'status' => 'OPEN_PENDING_SPECIALIZED_INPUTS', 'construction_authority' => true, 'persona_selection_authority' => false,
            'spawning_authority' => false, 'admission_authority' => false, 'seat_binding_authority' => false, 'execution_authority' => false]; $case['record_digest'] = hash('sha256', CanonicalJson::encode($case));
        mkdir($root.'/var/imperium/offices/foundry/production-cases', 0770, true); mkdir($root.'/var/imperium/offices/foundry/acceptances', 0770, true); mkdir($root.'/var/imperium/offices/foundry/inbox', 0770, true);
        file_put_contents($root.'/var/imperium/offices/foundry/production-cases/'.$caseId.'.json', json_encode($case, JSON_THROW_ON_ERROR));
        file_put_contents($root.'/var/imperium/offices/foundry/acceptances/'.$acceptanceId.'.json', json_encode($acceptance, JSON_THROW_ON_ERROR));
        file_put_contents($root.'/var/imperium/offices/foundry/inbox/'.$demandId.'.json', json_encode($demand, JSON_THROW_ON_ERROR));
        try {
            $service = new SpecializedAuthorshipCommissionService($root); $result = $service->dispatch($caseId); self::assertSame($result, $service->dispatch($caseId)); self::assertCount(2, $result['commissions']);
            self::assertSame(['hagiography', 'studium'], array_column($result['commissions'], 'office'));
            foreach ($result['commissions'] as $commission) { self::assertSame('ISSUED_PENDING_RECIPIENT', $commission['status']); self::assertTrue($commission['authorship_authority']); self::assertNull($commission['recipient_acceptance']);
                self::assertFalse($commission['persona_selection_authority']); self::assertFalse($commission['persona_assembly_authority']); self::assertFalse($commission['spawning_authority']); self::assertFalse($commission['admission_authority']); self::assertFalse($commission['execution_authority']); }
            self::assertFileExists($root.'/var/imperium/offices/hagiography/inbox/'.$result['commissions'][0]['commission_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/studium/inbox/'.$result['commissions'][1]['commission_id'].'.json');
        } finally { $this->removeTree($root); }
    }
    private function removeTree(string $path): void { if (!is_dir($path)) return; foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) { $child = $path.'/'.$entry; is_dir($child) ? $this->removeTree($child) : unlink($child); } rmdir($path); }
}
