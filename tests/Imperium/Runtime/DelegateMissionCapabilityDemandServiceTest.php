<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\DelegateMissionCapabilityDemandService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DelegateMissionCapabilityDemandServiceTest extends TestCase
{
    private const array FALSE_AUTHORITIES = [
        'mission_plan_amendment_authority',
        'guildhall_delivery_authority',
        'guildhall_intake_authority',
        'profession_translation_authority',
        'profession_determination_authority',
        'persona_selection_authority',
        'persona_suitability_authority',
        'personnel_use_authority',
        'reservation_authority',
        'retrieval_authority',
        'custody_transfer_authority',
        'profile_derivation_authority',
        'profile_examination_authority',
        'profile_approval_authority',
        'profile_installation_authority',
        'profile_qualification_authority',
        'manifestation_assembly_authority',
        'seat_binding_authority',
        'commission_authority',
        'follow_up_commission_authority',
        'deployment_authority',
        'operational_use_authority',
        'cognition_authority',
        'provider_invocation_authority',
        'data_access_authority',
        'tool_use_authority',
        'credential_use_authority',
        'perimeter_crossing_authority',
        'external_action_authority',
        'execution_authority',
        'return_execution_authority',
        'continuing_turn_authority',
    ];

    public function testCuriaSealsExactDelegateCapabilityDemandPendingGuildhallIntake(): void
    {
        $root = sys_get_temp_dir().'/imperium-delegate-demand-'.bin2hex(random_bytes(5));
        try {
            [$authorizationId, $plan] = $this->fixtures($root);
            $service = new DelegateMissionCapabilityDemandService($root);
            $sealedAt = new \DateTimeImmutable('2026-08-24T01:00:00+00:00');
            $demand = $service->seal($authorizationId, $sealedAt);

            self::assertSame('imperium.delegate-mission-capability-demand/v1', $demand['schema']);
            self::assertSame('DELEGATE', $demand['officer_class']);
            self::assertSame('DELEGATE_MISSION_CAPABILITY_DEMAND_SEALED_PENDING_GUILDHALL_INTAKE_NO_PERSONNEL_AUTHORITY', $demand['status']);
            self::assertSame('guildhall.guildmaster', $demand['consumer']['seat']);
            self::assertTrue($demand['consumer']['intake_pending']);
            self::assertFalse($demand['consumer']['delivered']);
            self::assertSame('CAPABILITY_TO_PROFESSION', $demand['translation_boundary']['name']);
            self::assertSame($plan['capability_requirements'], $demand['demand']['capability_requirements']);
            self::assertSame($plan['mission_seat'], $demand['demand']['mission_seat']);
            self::assertSame($plan['bounded_duration'], $demand['demand']['bounded_duration']);
            self::assertArrayNotHasKey('profession', $demand['demand']);
            self::assertArrayNotHasKey('persona', $demand['demand']);
            foreach (self::FALSE_AUTHORITIES as $authority) {
                self::assertArrayHasKey($authority, $demand);
                self::assertFalse($demand[$authority], $authority.' must remain false');
            }
            self::assertSame($demand, $service->seal($authorizationId, $sealedAt->modify('+1 hour')));
        } finally {
            $this->remove($root);
        }
    }

    #[DataProvider('forbiddenPersonnelSelectionFields')]
    public function testCuriaCannotSelectProfessionOrPersona(string $field, mixed $value): void
    {
        $root = sys_get_temp_dir().'/imperium-delegate-selection-'.bin2hex(random_bytes(5));
        try {
            $plan = $this->plan();
            $plan[$field] = $value;
            [$authorizationId] = $this->fixtures($root, $plan);
            $this->expectExceptionMessage('CUR495_DELEGATE_MISSION_CAPABILITY_DEMAND_PLAN_INVALID');
            (new DelegateMissionCapabilityDemandService($root))->seal($authorizationId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public static function forbiddenPersonnelSelectionFields(): iterable
    {
        yield 'profession' => ['profession', 'Security assessor'];
        yield 'profession requirements' => ['profession_requirements', ['Security assessor']];
        yield 'persona' => ['persona', ['id' => 'persona-test']];
        yield 'persona id' => ['persona_id', 'persona-test'];
    }

    public function testUnapprovedMissionPlanCannotProduceDemand(): void
    {
        $root = sys_get_temp_dir().'/imperium-delegate-unapproved-'.bin2hex(random_bytes(5));
        try {
            [$authorizationId, , $reviewPath] = $this->fixtures($root);
            $review = json_decode((string) file_get_contents($reviewPath), true, 512, JSON_THROW_ON_ERROR);
            unset($review['record_digest']);
            $review['disposition'] = 'OBJECT_RETURN_FOR_REVISION';
            $review['dossier_approval'] = false;
            $review['all_lines_acknowledged'] = false;
            $review['status'] = 'IMPERATOR_PLANNING_DOSSIER_OBJECTED_PENDING_CURIA_REVISION';
            $this->write($reviewPath, $this->record($review));

            $this->expectExceptionMessage('CUR494_DELEGATE_MISSION_CAPABILITY_DEMAND_CHAIN_INVALID');
            (new DelegateMissionCapabilityDemandService($root))->seal($authorizationId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testMissionPlanDriftFromApprovedDossierFailsClosed(): void
    {
        $root = sys_get_temp_dir().'/imperium-delegate-drift-'.bin2hex(random_bytes(5));
        try {
            [$authorizationId, , , $authorizationPath] = $this->fixtures($root);
            $authorization = json_decode((string) file_get_contents($authorizationPath), true, 512, JSON_THROW_ON_ERROR);
            unset($authorization['record_digest']);
            $authorization['mission_plan']['scope'] = ['Broadened scope'];
            $this->write($authorizationPath, $this->record($authorization));

            $this->expectExceptionMessage('CUR494_DELEGATE_MISSION_CAPABILITY_DEMAND_CHAIN_INVALID');
            (new DelegateMissionCapabilityDemandService($root))->seal($authorizationId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testIncompleteReturnAndRetirementDesignFailsClosed(): void
    {
        $root = sys_get_temp_dir().'/imperium-delegate-terminal-design-'.bin2hex(random_bytes(5));
        try {
            $plan = $this->plan();
            $plan['retirement_conditions'] = [];
            [$authorizationId] = $this->fixtures($root, $plan);
            $this->expectExceptionMessage('CUR495_DELEGATE_MISSION_CAPABILITY_DEMAND_PLAN_INVALID');
            (new DelegateMissionCapabilityDemandService($root))->seal($authorizationId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, ?array $plan = null): array
    {
        $plan ??= $this->plan();
        $dossierId = 'curia-planning-dossier-'.str_repeat('a', 20);
        $reviewId = 'imperator-planning-dossier-review-'.str_repeat('b', 20);
        $derivationAuthorityId = 'mission-authorization-derivation-authority-'.str_repeat('c', 20);
        $authorizationId = 'mission-authorization-'.str_repeat('d', 20);
        $lines = [];
        foreach ($plan as $field => $values) {
            foreach (is_array($values) && array_is_list($values) ? $values : [$values] as $value) {
                $line = ['line_number' => count($lines) + 1, 'section' => 'mission_plan.'.$field, 'text' => is_scalar($value) ? (string) $value : CanonicalJson::encode($value), 'source' => 'source_plan'];
                $line['line_digest'] = hash('sha256', CanonicalJson::encode($line));
                $lines[] = $line;
            }
        }
        $dossier = $this->record([
            'schema' => 'imperium.curia-planning-dossier/v1',
            'dossier_id' => $dossierId,
            'dossier_version' => 1,
            'instance_id' => 'imperium-test',
            'source_plan' => ['proceeding_id' => 'proceeding-delegate-mission', 'instance_id' => 'imperium-test', 'turn_sequence' => 7, 'turn_digest' => str_repeat('1', 64)],
            'mission_plan' => $plan,
            'resource_demands' => [],
            'model_selection_decisions' => [],
            'proposed_model_bindings' => [],
            'disclosures' => [],
            'lines' => $lines,
            'line_count' => count($lines),
            'status' => 'CURIA_PLANNING_DOSSIER_SEALED_PENDING_IMPERATOR_REVIEW',
            'sealed' => true,
        ]);
        $review = $this->record([
            'schema' => 'imperium.imperator-planning-dossier-review/v1',
            'review_id' => $reviewId,
            'dossier' => ['id' => $dossierId, 'version' => 1, 'digest' => $dossier['record_digest'], 'line_count' => count($lines)],
            'actor' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
            'disposition' => 'APPROVE_DOSSIER',
            'all_lines_acknowledged' => true,
            'cited_lines' => [],
            'response' => 'Approved exactly as disclosed.',
            'mission_authorization_derivation_authority' => ['authority_id' => $derivationAuthorityId, 'authority_single_use' => true, 'derivation_authority' => true],
            'dossier_approval' => true,
            'status' => 'IMPERATOR_PLANNING_DOSSIER_APPROVED_PENDING_MISSION_AUTHORIZATION',
            'sealed' => true,
        ]);
        $authorization = $this->record([
            'schema' => 'imperium.mission-authorization/v1',
            'authorization_id' => $authorizationId,
            'instance_id' => 'imperium-test',
            'authority_source' => [
                'dossier' => ['id' => $dossierId, 'version' => 1, 'digest' => $dossier['record_digest']],
                'imperator_review' => ['id' => $reviewId, 'digest' => $review['record_digest']],
                'derivation_authority_id' => $derivationAuthorityId,
            ],
            'holder' => ['kind' => 'imperium-runtime', 'instance_id' => 'imperium-test'],
            'authorized_dossier_lines' => $lines,
            'mission_plan' => $plan,
            'derivation_authority' => ['id' => $derivationAuthorityId, 'consumed' => true, 'continuing_authority' => false],
            'direct_execution_prohibited' => true,
            'silent_scope_expansion_prohibited' => true,
            'profile_mutation_performed' => false,
            'credential_release_performed' => false,
            'provider_invocation_performed' => false,
            'deployment_performed' => false,
            'external_effect_performed' => false,
            'execution_performed' => false,
            'execution_authority' => false,
            'status' => 'MISSION_AUTHORIZATION_SEALED_PENDING_AUTHORIZED_PREPARATION',
            'sealed' => true,
        ]);
        $dossierPath = $root.'/var/imperium/offices/curia/planning-dossiers/'.$dossierId.'.json';
        $reviewPath = $root.'/var/imperium/offices/curia/planning-dossier-reviews/'.$reviewId.'.json';
        $authorizationPath = $root.'/var/imperium/authorizations/missions/'.$authorizationId.'.json';
        $this->write($dossierPath, $dossier);
        $this->write($reviewPath, $review);
        $this->write($authorizationPath, $authorization);

        return [$authorizationId, $plan, $reviewPath, $authorizationPath];
    }

    private function plan(): array
    {
        return [
            'objective' => 'Assess the exact supplied public application surface.',
            'scope' => ['Supplied public URLs only'],
            'deliverables' => ['Evidence-bound risk report'],
            'constraints' => ['Passive observation only'],
            'required_inputs' => ['Approved target URL'],
            'capability_requirements' => ['Analyze public application behavior', 'Produce attributable evidence-bound findings'],
            'expected_outcomes' => ['Bounded assessment returned to Curia'],
            'mission_seat' => 'mission.delegate.passive-assessment',
            'bounded_duration' => ['maximum' => 4, 'unit' => 'hours', 'starts_when' => 'Authorized deployment begins', 'expires_when' => 'Four hours elapse or a stop condition occurs'],
            'data_requirements' => ['Public HTTP responses admitted through Lazaretto'],
            'tool_requirements' => ['Passive HTTP observation capability'],
            'credential_requirements' => ['No credential permitted'],
            'perimeter_requirements' => ['Outbound through Iron Gate and inbound through Lazaretto'],
            'stop_conditions' => ['Authentication is required', 'Target leaves approved scope'],
            'return_conditions' => ['Return the sealed report and evidence lineage to Curia'],
            'unbinding_conditions' => ['Unbind the mission Seat after governed return'],
            'custody_restoration_conditions' => ['Restore the Persona to Garrison ADMITTED_HELD custody'],
            'retirement_conditions' => ['Terminate the Delegate after return or interruption'],
        ];
    }

    private function record(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function write(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
