<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalAdoptionIntakeDispositionService;
use PHPUnit\Framework\TestCase;

final class OperationalAdoptionIntakeDispositionServiceTest extends TestCase
{
    public function testSeneschalAcceptsIntakeWithoutOpeningEvaluationAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-intake-accept-'.bin2hex(random_bytes(5));
        try {
            [$presentationId, $seneschalId, $result] = $this->fixtures($root);
            $service = new OperationalAdoptionIntakeDispositionService($root);
            $disposition = $service->decide($presentationId, $seneschalId, 'ACCEPTED', 'Curia will consider the exact accepted result.', new \DateTimeImmutable('2026-08-24T00:00:00+00:00'));

            self::assertSame('LEGATE_RESULT_ADOPTION_INTAKE_ACCEPTED_PENDING_EVALUATION_OPENING_NO_EVALUATION_AUTHORITY', $disposition['status']);
            self::assertSame($result, $disposition['result']);
            self::assertTrue($disposition['governing_intake_decided']);
            self::assertTrue($disposition['governing_intake_accepted']);
            self::assertFalse($disposition['governing_intake_refused']);
            self::assertFalse($disposition['adoption_lifecycle_closed']);
            foreach (['evaluation_opening_authority', 'result_evaluated_for_adoption', 'result_operationally_adopted', 'planning_amendment_authority', 'follow_up_commission_authority', 'commission_exercisable', 'governed_cognition_authority', 'provider_invocation_authority', 'credential_use_authority', 'operational_use_permitted', 'tool_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($disposition[$field]);
            }
            self::assertSame($disposition, $service->decide($presentationId, $seneschalId, 'ACCEPTED', 'Curia will consider the exact accepted result.', new \DateTimeImmutable('2026-08-24T01:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testSeneschalRefusalClosesAdoptionLifecycleWithoutAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-intake-refuse-'.bin2hex(random_bytes(5));
        try {
            [$presentationId, $seneschalId] = $this->fixtures($root);
            $disposition = (new OperationalAdoptionIntakeDispositionService($root))->decide($presentationId, $seneschalId, 'REFUSED', 'The result is outside Curia operational need.', new \DateTimeImmutable());

            self::assertSame('LEGATE_RESULT_ADOPTION_INTAKE_REFUSED_LIFECYCLE_CLOSED_NO_AUTHORITY', $disposition['status']);
            self::assertTrue($disposition['governing_intake_refused']);
            self::assertTrue($disposition['adoption_lifecycle_closed']);
            self::assertFalse($disposition['evaluation_opening_authority']);
            self::assertFalse($disposition['result_operationally_adopted']);
            self::assertFalse($disposition['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testNonSeneschalBindingCannotDecideIntake(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-intake-wrong-seat-'.bin2hex(random_bytes(5));
        try {
            [$presentationId] = $this->fixtures($root);
            $wrongId = 'operational-seat-binding-'.str_repeat('9', 20);
            $this->write($root.'/var/imperium/operational/occupancy/'.$wrongId.'.json', $this->record([
                'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $wrongId, 'instance_id' => 'imperium-test',
                'seat' => 'curia.chamberlain', 'manifestation_id' => 'manifestation-chamberlain', 'occupancy_generation' => 1,
                'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
            ]));
            $this->expectExceptionMessage('CUR455_ADOPTION_INTAKE_CHAIN_INVALID');
            (new OperationalAdoptionIntakeDispositionService($root))->decide($presentationId, $wrongId, 'ACCEPTED', 'Attempt.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testConflictingSecondIntakeDispositionIsRejected(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-intake-conflict-'.bin2hex(random_bytes(5));
        try {
            [$presentationId, $seneschalId] = $this->fixtures($root);
            $service = new OperationalAdoptionIntakeDispositionService($root);
            $service->decide($presentationId, $seneschalId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable());
            $this->expectExceptionMessage('CUR458_ADOPTION_INTAKE_DISPOSITION_CONFLICT');
            $service->decide($presentationId, $seneschalId, 'REFUSED', 'Refuse.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $instance = 'imperium-test';
        $seneschalId = 'operational-seat-binding-'.str_repeat('a', 20);
        $seneschal = $this->record([
            'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $seneschalId, 'instance_id' => $instance,
            'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
        ]);
        $presentationId = 'legate-result-adoption-presentation-'.str_repeat('b', 20);
        $result = ['disposition' => 'COMPLETED', 'output' => 'One bounded recommendation.', 'evidence_references' => ['sealed-input-digest'], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => 'Complete.'];
        $presentation = $this->record([
            'schema' => 'imperium.legate-result-adoption-presentation/v1', 'presentation_id' => $presentationId, 'instance_id' => $instance,
            'case_id' => 'case', 'case_digest' => str_repeat('1', 64),
            'source_review' => ['id' => 'citadel-legate-cognition-result-review-'.str_repeat('c', 20), 'digest' => str_repeat('2', 64)],
            'source_delivery' => ['id' => 'citadel-legate-cognition-result-delivery-'.str_repeat('d', 20), 'digest' => str_repeat('3', 64)],
            'source_cognition_turn' => ['id' => 'citadel-legate-bounded-cognition-turn-'.str_repeat('e', 20), 'digest' => str_repeat('4', 64)],
            'source_commission' => ['id' => 'citadel-legate-governed-commission-'.str_repeat('f', 20), 'digest' => str_repeat('5', 64)],
            'presenter' => ['seat' => 'curia.seneschal', 'binding_id' => $seneschalId, 'binding_digest' => $seneschal['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'recipient' => ['office' => 'curia', 'seat' => 'curia.seneschal', 'intake_pending' => true],
            'legate' => ['officer_class' => 'LEGATE', 'seat' => 'foundry.artificer'], 'contract' => ['task' => 'Recommend.'], 'result' => $result,
            'commissioner_review_rationale' => 'The exact contract is satisfied.', 'presentation_rationale' => 'Submit for Curia intake.',
            'status' => 'LEGATE_RESULT_ADOPTION_REQUEST_PRESENTED_PENDING_GOVERNING_INTAKE', 'commission_closed' => true,
            'governing_intake_decided' => false, 'result_evaluated_for_adoption' => false, 'result_operationally_adopted' => false,
            'planning_amendment_authority' => false, 'follow_up_commission_authority' => false, 'commission_exercisable' => false,
            'governed_cognition_authority' => false, 'provider_invocation_authority' => false, 'credential_use_authority' => false,
            'operational_use_permitted' => false, 'tool_use_authority' => false, 'external_action_authority' => false,
            'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$seneschalId.'.json', $seneschal);
        $this->write($root.'/var/imperium/operational/legate-result-adoption-presentations/'.$presentationId.'.json', $presentation);

        return [$presentationId, $seneschalId, $result];
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
