<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalAdoptionEvaluationOpeningService;
use PHPUnit\Framework\TestCase;

final class OperationalAdoptionEvaluationOpeningServiceTest extends TestCase
{
    public function testSeneschalOpensBoundedEvaluationWithoutAssessmentAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-evaluation-open-'.bin2hex(random_bytes(5));
        try {
            [$intakeId, $seneschalId, $result] = $this->fixtures($root, true);
            $service = new OperationalAdoptionEvaluationOpeningService($root);
            $opening = $service->open($intakeId, $seneschalId, new \DateTimeImmutable('2026-08-24T01:00:00+00:00'));

            self::assertSame('LEGATE_RESULT_ADOPTION_EVALUATION_OPENED_PENDING_CURIAL_COMPOSITION_NO_ASSESSMENT_AUTHORITY', $opening['status']);
            self::assertSame($result, $opening['result']);
            self::assertTrue($opening['evaluation_opened']);
            self::assertFalse($opening['presiding_seneschal']['may_impersonate_curialis']);
            self::assertSame(['EVIDENCE_SUFFICIENCY', 'MISSION_OPERATIONAL_FIT', 'RISK_AUTHORITY_REVERSIBILITY'], array_column($opening['evaluation_contract']['required_judgments'], 'jurisdiction'));
            self::assertTrue($opening['evaluation_contract']['independent_assessments_required']);
            self::assertTrue($opening['evaluation_contract']['voting_prohibited']);
            foreach (['curial_composition_resolved', 'assessment_commissions_issued', 'assessment_commission_acceptance_authority', 'assessment_authority', 'result_evaluated_for_adoption', 'result_operationally_adopted', 'planning_amendment_authority', 'follow_up_commission_authority', 'commission_exercisable', 'governed_cognition_authority', 'provider_invocation_authority', 'credential_use_authority', 'operational_use_permitted', 'tool_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($opening[$field]);
            }
            self::assertSame($opening, $service->open($intakeId, $seneschalId, new \DateTimeImmutable('2026-08-24T02:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRefusedIntakeCannotOpenEvaluation(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-evaluation-refused-'.bin2hex(random_bytes(5));
        try {
            [$intakeId, $seneschalId] = $this->fixtures($root, false);
            $this->expectExceptionMessage('CUR464_ADOPTION_EVALUATION_OPENING_CHAIN_INVALID');
            (new OperationalAdoptionEvaluationOpeningService($root))->open($intakeId, $seneschalId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testReplacementSeneschalCannotOpenPriorDisposition(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-evaluation-replacement-'.bin2hex(random_bytes(5));
        try {
            [$intakeId] = $this->fixtures($root, true);
            $replacementId = 'operational-seat-binding-'.str_repeat('9', 20);
            $this->write($root.'/var/imperium/operational/occupancy/'.$replacementId.'.json', $this->record([
                'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $replacementId, 'instance_id' => 'imperium-test',
                'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-replacement', 'occupancy_generation' => 2,
                'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
            ]));
            $this->expectExceptionMessage('CUR464_ADOPTION_EVALUATION_OPENING_CHAIN_INVALID');
            (new OperationalAdoptionEvaluationOpeningService($root))->open($intakeId, $replacementId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, bool $accepted): array
    {
        $instance = 'imperium-test';
        $seneschalId = 'operational-seat-binding-'.str_repeat('a', 20);
        $seneschal = $this->record([
            'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $seneschalId, 'instance_id' => $instance,
            'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
        ]);
        $intakeId = 'legate-result-adoption-intake-disposition-'.str_repeat('b', 20);
        $result = ['disposition' => 'COMPLETED', 'output' => 'One bounded recommendation.', 'evidence_references' => ['sealed-input-digest'], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => 'Complete.'];
        $intake = $this->record([
            'schema' => 'imperium.legate-result-adoption-intake-disposition/v1', 'disposition_id' => $intakeId, 'instance_id' => $instance,
            'case_id' => 'case', 'case_digest' => str_repeat('1', 64),
            'source_presentation' => ['id' => 'legate-result-adoption-presentation-'.str_repeat('c', 20), 'digest' => str_repeat('2', 64)],
            'source_review' => ['id' => 'citadel-legate-cognition-result-review-'.str_repeat('d', 20), 'digest' => str_repeat('3', 64)],
            'source_delivery' => ['id' => 'citadel-legate-cognition-result-delivery-'.str_repeat('e', 20), 'digest' => str_repeat('4', 64)],
            'source_cognition_turn' => ['id' => 'citadel-legate-bounded-cognition-turn-'.str_repeat('f', 20), 'digest' => str_repeat('5', 64)],
            'source_commission' => ['id' => 'citadel-legate-governed-commission-'.str_repeat('6', 20), 'digest' => str_repeat('7', 64)],
            'presenter' => ['seat' => 'curia.seneschal'],
            'decision_maker' => ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'legate' => ['officer_class' => 'LEGATE', 'seat' => 'foundry.artificer'], 'contract' => ['task' => 'Recommend.'], 'result' => $result,
            'decision' => $accepted ? 'ACCEPTED' : 'REFUSED', 'rationale' => $accepted ? 'Consider.' : 'Decline.',
            'status' => $accepted ? 'LEGATE_RESULT_ADOPTION_INTAKE_ACCEPTED_PENDING_EVALUATION_OPENING_NO_EVALUATION_AUTHORITY' : 'LEGATE_RESULT_ADOPTION_INTAKE_REFUSED_LIFECYCLE_CLOSED_NO_AUTHORITY',
            'commission_closed' => true, 'governing_intake_decided' => true, 'governing_intake_accepted' => $accepted,
            'governing_intake_refused' => !$accepted, 'adoption_lifecycle_closed' => !$accepted, 'evaluation_opening_authority' => false,
            'result_evaluated_for_adoption' => false, 'result_operationally_adopted' => false, 'planning_amendment_authority' => false,
            'follow_up_commission_authority' => false, 'commission_exercisable' => false, 'governed_cognition_authority' => false,
            'provider_invocation_authority' => false, 'credential_use_authority' => false, 'operational_use_permitted' => false,
            'tool_use_authority' => false, 'external_action_authority' => false, 'execution_authority' => false,
            'continuing_turn_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$seneschalId.'.json', $seneschal);
        $this->write($root.'/var/imperium/operational/legate-result-adoption-intake-dispositions/'.$intakeId.'.json', $intake);

        return [$intakeId, $seneschalId, $result];
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
