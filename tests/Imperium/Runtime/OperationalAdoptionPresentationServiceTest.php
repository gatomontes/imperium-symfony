<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Curia\OperationalAdoptionPresentationService;
use PHPUnit\Framework\TestCase;

final class OperationalAdoptionPresentationServiceTest extends TestCase
{
    public function testOriginalCommissionerPresentsAcceptedResultWithoutDownstreamAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-presentation-'.bin2hex(random_bytes(5));
        try {
            [$reviewId, $commissionerId, $result] = $this->fixtures($root, true);
            $service = new OperationalAdoptionPresentationService($root);
            $presentation = $service->present($reviewId, $commissionerId, 'Submit the accepted result for separate Curia intake.', new \DateTimeImmutable('2026-08-23T23:30:00+00:00'));

            self::assertSame('LEGATE_RESULT_ADOPTION_REQUEST_PRESENTED_PENDING_GOVERNING_INTAKE', $presentation['status']);
            self::assertSame('curia.seneschal', $presentation['recipient']['seat']);
            self::assertTrue($presentation['recipient']['intake_pending']);
            self::assertSame($result, $presentation['result']);
            self::assertTrue($presentation['commission_closed']);
            self::assertFalse($presentation['governing_intake_decided']);
            foreach (['result_evaluated_for_adoption', 'result_operationally_adopted', 'planning_amendment_authority', 'follow_up_commission_authority', 'commission_exercisable', 'governed_cognition_authority', 'provider_invocation_authority', 'credential_use_authority', 'operational_use_permitted', 'tool_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($presentation[$field]);
            }
            self::assertSame($presentation, $service->present($reviewId, $commissionerId, 'Submit the accepted result for separate Curia intake.', new \DateTimeImmutable('2026-08-24T00:30:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRejectedResultCannotEnterAdoptionLifecycle(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-rejected-'.bin2hex(random_bytes(5));
        try {
            [$reviewId, $commissionerId] = $this->fixtures($root, false);
            $this->expectExceptionMessage('CUR445_ADOPTION_PRESENTATION_CHAIN_INVALID');
            (new OperationalAdoptionPresentationService($root))->present($reviewId, $commissionerId, 'Attempt presentation.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testAReplacementBindingCannotPresentTheOriginalCommissionersResult(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-wrong-presenter-'.bin2hex(random_bytes(5));
        try {
            [$reviewId] = $this->fixtures($root, true);
            $replacementId = 'operational-seat-binding-'.str_repeat('9', 20);
            $this->write($root.'/var/imperium/operational/occupancy/'.$replacementId.'.json', $this->record([
                'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $replacementId, 'instance_id' => 'imperium-test',
                'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-replacement', 'occupancy_generation' => 2,
                'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
            ]));
            $this->expectExceptionMessage('CUR445_ADOPTION_PRESENTATION_CHAIN_INVALID');
            (new OperationalAdoptionPresentationService($root))->present($reviewId, $replacementId, 'Attempt presentation.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testConflictingSecondPresentationIsRejected(): void
    {
        $root = sys_get_temp_dir().'/imperium-adoption-conflict-'.bin2hex(random_bytes(5));
        try {
            [$reviewId, $commissionerId] = $this->fixtures($root, true);
            $service = new OperationalAdoptionPresentationService($root);
            $service->present($reviewId, $commissionerId, 'First rationale.', new \DateTimeImmutable());
            $this->expectExceptionMessage('CUR448_ADOPTION_PRESENTATION_CONFLICT');
            $service->present($reviewId, $commissionerId, 'Changed rationale.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, bool $accepted): array
    {
        $instance = 'imperium-test';
        $commissionerId = 'operational-seat-binding-'.str_repeat('a', 20);
        $commissioner = $this->record([
            'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $commissionerId, 'instance_id' => $instance,
            'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
        ]);
        $reviewId = 'citadel-legate-cognition-result-review-'.str_repeat('b', 20);
        $result = ['disposition' => 'COMPLETED', 'output' => 'One bounded recommendation.', 'evidence_references' => ['sealed-input-digest'], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => 'Complete.'];
        $review = $this->record([
            'schema' => 'imperium.citadel-legate-cognition-result-review/v1', 'review_id' => $reviewId, 'instance_id' => $instance,
            'case_id' => 'case', 'case_digest' => str_repeat('1', 64),
            'source_delivery' => ['id' => 'citadel-legate-cognition-result-delivery-'.str_repeat('c', 20), 'digest' => str_repeat('2', 64)],
            'source_cognition_turn' => ['id' => 'citadel-legate-bounded-cognition-turn-'.str_repeat('d', 20), 'digest' => str_repeat('3', 64)],
            'source_commission' => ['id' => 'citadel-legate-governed-commission-'.str_repeat('e', 20), 'digest' => str_repeat('4', 64)],
            'reviewer' => ['seat' => 'curia.seneschal', 'binding_id' => $commissionerId, 'binding_digest' => $commissioner['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'legate' => ['officer_class' => 'LEGATE', 'seat' => 'foundry.artificer', 'binding_id' => 'model-bound-operational-seat-binding-'.str_repeat('f', 20)],
            'contract' => ['task' => 'Recommend.'], 'result' => $result,
            'disposition' => $accepted ? 'ACCEPTED' : 'REJECTED', 'rationale' => $accepted ? 'The exact contract is satisfied.' : 'The exact contract is not satisfied.',
            'status' => $accepted ? 'CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY' : 'CITADEL_LEGATE_COGNITION_RESULT_REJECTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY',
            'review_disposition_authority' => ['id' => 'citadel-legate-cognition-result-review-authority-'.str_repeat('5', 20), 'consumed' => true, 'continuing_authority' => false],
            'result_reviewed' => true, 'result_accepted' => $accepted, 'result_rejected' => !$accepted,
            'result_operationally_adopted' => false, 'commission_closed' => true, 'follow_up_commission_authority' => false,
            'commission_exercisable' => false, 'governed_cognition_authority' => false, 'provider_invocation_authority' => false,
            'credential_use_authority' => false, 'operational_use_permitted' => false, 'tool_use_authority' => false,
            'external_action_authority' => false, 'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$commissionerId.'.json', $commissioner);
        $this->write($root.'/var/imperium/operational/citadel-legate-cognition-result-reviews/'.$reviewId.'.json', $review);

        return [$reviewId, $commissionerId, $result];
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
