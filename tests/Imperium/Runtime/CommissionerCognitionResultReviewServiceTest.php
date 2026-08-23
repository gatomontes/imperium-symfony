<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\CommissionerCognitionResultReviewService;
use PHPUnit\Framework\TestCase;

final class CommissionerCognitionResultReviewServiceTest extends TestCase
{
    public function testCommissionerAcceptsResultAndClosesCommissionWithoutAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-accept-'.bin2hex(random_bytes(5));
        try {
            [$deliveryId, $authorityId, $commissionerId, $result] = $this->fixtures($root);
            $service = new CommissionerCognitionResultReviewService($root);
            $review = $service->review($deliveryId, $authorityId, $commissionerId, 'ACCEPTED', 'The result satisfies the exact contract.', new \DateTimeImmutable('2026-08-23T22:00:00+00:00'));

            self::assertSame('CITADEL_LEGATE_COGNITION_RESULT_ACCEPTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY', $review['status']);
            self::assertSame($result, $review['result']);
            self::assertTrue($review['result_reviewed']);
            self::assertTrue($review['result_accepted']);
            self::assertFalse($review['result_rejected']);
            self::assertTrue($review['commission_closed']);
            self::assertTrue($review['review_disposition_authority']['consumed']);
            foreach (['result_operationally_adopted', 'follow_up_commission_authority', 'commission_exercisable', 'governed_cognition_authority', 'provider_invocation_authority', 'credential_use_authority', 'operational_use_permitted', 'tool_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($review[$field]);
            }
            self::assertSame($review, $service->review($deliveryId, $authorityId, $commissionerId, 'ACCEPTED', 'The result satisfies the exact contract.', new \DateTimeImmutable('2026-08-23T23:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRejectionAlsoClosesCommissionWithoutFollowUpAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-reject-'.bin2hex(random_bytes(5));
        try {
            [$deliveryId, $authorityId, $commissionerId] = $this->fixtures($root);
            $review = (new CommissionerCognitionResultReviewService($root))->review($deliveryId, $authorityId, $commissionerId, 'REJECTED', 'The supplied evidence does not satisfy the contract.', new \DateTimeImmutable());

            self::assertSame('CITADEL_LEGATE_COGNITION_RESULT_REJECTED_COMMISSION_CLOSED_NO_DOWNSTREAM_AUTHORITY', $review['status']);
            self::assertFalse($review['result_accepted']);
            self::assertTrue($review['result_rejected']);
            self::assertTrue($review['commission_closed']);
            self::assertFalse($review['follow_up_commission_authority']);
            self::assertFalse($review['continuing_turn_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testWrongReviewAuthorityFailsClosed(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-wrong-authority-'.bin2hex(random_bytes(5));
        try {
            [$deliveryId, $authorityId, $commissionerId] = $this->fixtures($root);
            $this->expectExceptionMessage('CIT436_COGNITION_RESULT_REVIEW_CHAIN_INVALID');
            (new CommissionerCognitionResultReviewService($root))->review($deliveryId, 'citadel-legate-cognition-result-review-authority-'.str_repeat('9', 20), $commissionerId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testConflictingSecondDispositionIsRejected(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-conflict-'.bin2hex(random_bytes(5));
        try {
            [$deliveryId, $authorityId, $commissionerId] = $this->fixtures($root);
            $service = new CommissionerCognitionResultReviewService($root);
            $service->review($deliveryId, $authorityId, $commissionerId, 'ACCEPTED', 'Accept.', new \DateTimeImmutable());
            $this->expectExceptionMessage('CIT439_COGNITION_RESULT_REVIEW_CONFLICT');
            $service->review($deliveryId, $authorityId, $commissionerId, 'REJECTED', 'Reject.', new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root): array
    {
        $instance = 'imperium-test';
        $commissionerId = 'operational-seat-binding-'.str_repeat('a', 20);
        $commissioner = $this->record([
            'schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $commissionerId, 'instance_id' => $instance,
            'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1,
            'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true,
        ]);
        $deliveryId = 'citadel-legate-cognition-result-delivery-'.str_repeat('b', 20);
        $authorityId = 'citadel-legate-cognition-result-review-authority-'.str_repeat('c', 20);
        $result = ['disposition' => 'COMPLETED', 'output' => 'One bounded recommendation.', 'evidence_references' => ['sealed-input-digest'], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => 'Complete.'];
        $delivery = $this->record([
            'schema' => 'imperium.citadel-legate-cognition-result-delivery/v1', 'delivery_id' => $deliveryId, 'instance_id' => $instance,
            'case_id' => 'case', 'case_digest' => str_repeat('1', 64),
            'source_cognition_turn' => ['id' => 'citadel-legate-bounded-cognition-turn-'.str_repeat('d', 20), 'digest' => str_repeat('2', 64)],
            'source_commission' => ['id' => 'citadel-legate-governed-commission-'.str_repeat('e', 20), 'digest' => str_repeat('3', 64)],
            'legate' => ['seat' => 'foundry.artificer', 'binding_id' => 'model-bound-operational-seat-binding-'.str_repeat('f', 20)],
            'recipient' => ['seat' => 'curia.seneschal', 'binding_id' => $commissionerId, 'binding_digest' => $commissioner['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'contract' => ['task' => 'Recommend.'], 'result' => $result,
            'status' => 'CITADEL_LEGATE_COGNITION_RESULT_DELIVERED_PENDING_COMMISSIONER_REVIEW',
            'result_delivered' => true, 'result_reviewed' => false, 'result_accepted' => false, 'result_rejected' => false,
            'review_disposition_authority' => ['authority_id' => $authorityId, 'authority_single_use' => true, 'destination' => 'curia.seneschal', 'purpose' => 'RECORD_ONE_EXACT_COGNITION_RESULT_REVIEW_DISPOSITION', 'consumed' => false],
            'follow_up_commission_authority' => false, 'commission_exercisable' => false, 'governed_cognition_authority' => false,
            'provider_invocation_authority' => false, 'execution_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$commissionerId.'.json', $commissioner);
        $this->write($root.'/var/imperium/operational/citadel-legate-cognition-result-deliveries/'.$deliveryId.'.json', $delivery);

        return [$deliveryId, $authorityId, $commissionerId, $result];
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
