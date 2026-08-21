<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\ProceedingStore;
use App\Imperium\Runtime\Guildhall\GuildhallCognitionGateway;
use App\Imperium\Runtime\Guildhall\GuildhallDeliberationService;
use PHPUnit\Framework\TestCase;

final class GuildhallDeliberationServiceTest extends TestCase
{
    public function testSealsProfessionDeterminationAndExactGarrisonInquiryWithoutFinalDisposition(): void
    {
        [$root, $store, $acceptanceId] = $this->fixture();
        $gateway = new class implements GuildhallCognitionGateway {
            public int $calls = 0;

            public function deliberate(
                array $missionPlan,
                array $commissionScope,
                array $occupancy,
                array $completed = [],
                ?callable $progress = null,
                ?callable $checkpoint = null,
            ): array {
                ++$this->calls;
                TestCase::assertSame('Assess the public application.', $missionPlan['objective']);
                TestCase::assertCount(4, $occupancy);

                $decision = [
                    'committee' => [
                        'disciplinary_fit' => ['disposition' => 'PASS', 'findings' => ['Security assessment discipline required.'], 'requirements' => ['Evidence-led assessor.'], 'questions' => []],
                        'composition' => ['disposition' => 'PASS', 'findings' => ['Assessor and independent reviewer required.'], 'requirements' => ['Reviewer independent from assessor.'], 'questions' => []],
                        'boundary_challenge' => ['disposition' => 'PASS', 'findings' => ['Passive scope must remain enforced.'], 'requirements' => ['No active scanning capability.'], 'questions' => []],
                    ],
                    'guildmaster' => [
                        'disposition' => 'PROFESSION_DETERMINATION_COMPLETE',
                        'rationale' => 'The passive assessment requires evidence-led security assessment and independent review.',
                        'required_professions' => ['Web application security assessor', 'Independent cybersecurity reviewer'],
                        'exemplar_criteria' => ['Demonstrated passive web assessment discipline', 'Documented independence in findings review'],
                        'team_composition' => ['One assessor', 'One independent reviewer'],
                        'boundary_controls' => ['No active scanning, authentication, or exploitation'],
                        'garrison_inventory_queries' => ['Which admitted Personas are available for passive web application security assessment?', 'Which admitted Persona can independently review the assessor findings?'],
                        'unresolved_questions' => [],
                    ],
                ];
                if (null !== $progress) {
                    $progress('disciplinary_fit', 'CALLING');
                    $progress('disciplinary_fit', 'SEALED');
                }
                if (null !== $checkpoint) {
                    $checkpoint(['committee' => ['disciplinary_fit' => $decision['committee']['disciplinary_fit']]]);
                    $checkpoint($decision);
                }

                return $decision;
            }
        };

        try {
            $service = new GuildhallDeliberationService($root, $store, $gateway);
            $record = $service->deliberate($acceptanceId);

            self::assertSame($record, $service->deliberate($acceptanceId));
            self::assertSame(1, $gateway->calls);
            self::assertSame('imperium.guildhall-profession-determination/v1', $record['schema']);
            self::assertSame('PROFESSION_DETERMINED_GARRISON_INVENTORY_REQUIRED', $record['status']);
            self::assertSame('FUNCTIONAL_CAPABILITIES', $record['source_language']);
            self::assertSame(['Analyze public application behavior.', 'Produce evidence-bound findings.', 'Independently challenge conclusions.'], $record['source_capability_requirements']);
            self::assertSame('CAPABILITY_TO_PROFESSION', $record['translation_boundary']['name']);
            self::assertSame('guildhall.guildmaster', $record['translation_boundary']['authority']);
            self::assertFalse($record['translation_boundary']['curia_profession_selection_authority']);
            self::assertFalse($record['translation_boundary']['curia_persona_selection_authority']);
            self::assertFalse($record['final_personnel_disposition']);
            self::assertTrue($record['garrison_inventory_authority']);
            self::assertFalse($record['spawning_authority']);
            self::assertFalse($record['seat_binding_authority']);
            self::assertFalse($record['execution_authority']);
            self::assertTrue($record['sealed']);
            self::assertCount(2, $record['guildmaster_synthesis']['required_professions']);
            self::assertCount(2, $record['guildmaster_synthesis']['garrison_inventory_queries']);
            self::assertFileExists($root.'/var/imperium/offices/guildhall/deliberations/'.$record['determination_id'].'.json');
            self::assertFileExists($root.'/var/imperium/offices/guildhall/deliberation-checkpoints/'.$acceptanceId.'.json');
        } finally {
            $this->removeTree($root);
        }
    }

    private function fixture(): array
    {
        $root = sys_get_temp_dir().'/imperium-guildhall-deliberation-'.bin2hex(random_bytes(6));
        $store = new ProceedingStore($root);
        $proceedingId = 'proceeding-deliberation-test';
        $store->persist(['proceeding_id' => $proceedingId, 'instance_id' => 'imperium-test']);
        $plan = [
            'objective' => 'Assess the public application.',
            'scope' => ['Passive public review only.'],
            'deliverables' => ['Risk report.'],
            'constraints' => ['No active scanning.'],
            'required_inputs' => ['Public URL.'],
            'capability_requirements' => ['Analyze public application behavior.', 'Produce evidence-bound findings.', 'Independently challenge conclusions.'],
            'tool_requirements' => ['Passive methodology.'],
            'data_requirements' => ['Public observations.'],
            'office_participation' => ['Guildhall: personnel disposition.'],
            'stop_conditions' => ['Active access required.'],
        ];
        $turn = $store->appendTurn($proceedingId, 'response-deliberation-test', 1, [
            'response_id' => 'response-deliberation-test',
            'seneschal' => ['mission_plan' => $plan],
        ]);
        $commissionId = 'planning-guildhall-1234567890abcdef1234';
        $commission = $store->persistCommission($proceedingId, $commissionId, [
            'schema' => 'imperium.planning-commission/v1',
            'phase' => 'planning-only',
            'proceeding_id' => $proceedingId,
            'instance_id' => 'imperium-test',
            'issuer' => ['seat' => 'curia.seneschal'],
            'target' => 'guildhall.guildmaster',
            'source_language' => 'FUNCTIONAL_CAPABILITIES',
            'source_capability_requirements' => $plan['capability_requirements'],
            'translation_boundary' => [
                'name' => 'CAPABILITY_TO_PROFESSION',
                'authority' => 'guildhall.guildmaster',
                'curia_profession_selection_authority' => false,
                'curia_persona_selection_authority' => false,
                'guildhall_profession_determination_authority' => true,
                'guildhall_persona_suitability_authority' => true,
            ],
            'authority' => ['plan_turn' => 1, 'plan_digest' => $turn['record_digest']],
            'status' => 'ISSUED_PENDING_RECIPIENT',
            'execution_authority' => false,
        ]);

        $bindings = [];
        foreach (['guildhall.guildmaster', 'guildhall.committee.disciplinary-fit', 'guildhall.committee.composition', 'guildhall.committee.boundary-challenge'] as $seat) {
            $bindings[$seat] = [
                'seat' => $seat,
                'manifestation_id' => 'manifestation-'.substr(hash('sha256', $seat), 0, 12),
                'occupancy_generation' => 1,
                'status' => 'BOUND_PENDING_COMMISSION_ACCEPTANCE',
            ];
        }
        $bindingId = 'guildhall-binding-1234567890abcdef1234';
        $binding = [
            'schema' => 'imperium.guildhall-seat-binding-cohort/v1',
            'binding_id' => $bindingId,
            'bindings' => $bindings,
            'binding_atomic' => true,
        ];
        $binding['record_digest'] = hash('sha256', CanonicalJson::encode($binding));
        $occupancy = $root.'/var/imperium/offices/guildhall/occupancy';
        mkdir($occupancy, 0770, true);
        file_put_contents($occupancy.'/'.$bindingId.'.json', json_encode($binding, JSON_THROW_ON_ERROR));

        $acceptanceId = 'guildhall-acceptance-1234567890abcdef1234';
        $acceptance = [
            'schema' => 'imperium.guildhall-commission-acceptance/v1',
            'acceptance_id' => $acceptanceId,
            'instance_id' => 'imperium-test',
            'proceeding_id' => $proceedingId,
            'commission_id' => $commissionId,
            'commission_digest' => $commission['record_digest'],
            'binding_id' => $bindingId,
            'binding_digest' => $binding['record_digest'],
            'authorized_scope' => ['purpose' => 'Determine personnel requirements.'],
            'disposition' => 'ACCEPTED_FOR_INSTITUTIONAL_DELIBERATION',
            'recipient_acceptance' => true,
            'deliberation_authority' => true,
            'personnel_disposition_authority' => true,
            'execution_authority' => false,
        ];
        $acceptance['record_digest'] = hash('sha256', CanonicalJson::encode($acceptance));
        $acceptances = $root.'/var/imperium/offices/guildhall/acceptances';
        mkdir($acceptances, 0770, true);
        file_put_contents($acceptances.'/'.$acceptanceId.'.json', json_encode($acceptance, JSON_THROW_ON_ERROR));

        return [$root, $store, $acceptanceId];
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
