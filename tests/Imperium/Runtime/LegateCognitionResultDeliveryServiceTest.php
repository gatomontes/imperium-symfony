<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\LegateCognitionResultDeliveryService;
use PHPUnit\Framework\TestCase;

final class LegateCognitionResultDeliveryServiceTest extends TestCase
{
    public function testExactSealedResultIsDeliveredOnlyForCommissionerReview(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-delivery-'.bin2hex(random_bytes(5));
        try {
            [$turnId, $commissionerId, $output] = $this->fixtures($root);
            $service = new LegateCognitionResultDeliveryService($root);
            $delivery = $service->deliver($turnId, $commissionerId, new \DateTimeImmutable('2026-08-23T21:00:00+00:00'));

            self::assertSame('CITADEL_LEGATE_COGNITION_RESULT_DELIVERED_PENDING_COMMISSIONER_REVIEW', $delivery['status']);
            self::assertSame($output, $delivery['result']);
            self::assertSame('curia.seneschal', $delivery['recipient']['seat']);
            self::assertTrue($delivery['result_delivered']);
            self::assertFalse($delivery['result_reviewed']);
            self::assertTrue($delivery['review_disposition_authority']['authority_single_use']);
            self::assertFalse($delivery['review_disposition_authority']['consumed']);
            foreach (['result_accepted', 'result_rejected', 'follow_up_commission_authority', 'commission_exercisable', 'governed_cognition_authority', 'provider_invocation_authority', 'credential_use_authority', 'operational_use_permitted', 'tool_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($delivery[$field]);
            }
            self::assertSame($delivery, $service->deliver($turnId, $commissionerId, new \DateTimeImmutable('2026-08-23T22:00:00+00:00')));
        } finally {
            $this->remove($root);
        }
    }

    public function testDifferentOfficerCannotReceiveCommissionerResult(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-wrong-recipient-'.bin2hex(random_bytes(5));
        try {
            [$turnId] = $this->fixtures($root);
            $otherId = 'operational-seat-binding-'.str_repeat('9', 20);
            $this->write($root.'/var/imperium/operational/occupancy/'.$otherId.'.json', $this->record([
                'schema' => 'imperium.operational-seat-binding/v1',
                'binding_id' => $otherId,
                'instance_id' => 'imperium-test',
                'seat' => 'guildhall.guildmaster',
                'manifestation_id' => 'manifestation-guildmaster',
                'occupancy_generation' => 1,
                'status' => 'ACTIVE',
                'binding_atomic' => true,
                'sealed' => true,
            ]));
            $this->expectExceptionMessage('CIT425_COGNITION_RESULT_DELIVERY_CHAIN_INVALID');
            (new LegateCognitionResultDeliveryService($root))->deliver($turnId, $otherId, new \DateTimeImmutable());
        } finally {
            $this->remove($root);
        }
    }

    public function testTamperedTurnCannotBeDelivered(): void
    {
        $root = sys_get_temp_dir().'/imperium-legate-result-tampered-'.bin2hex(random_bytes(5));
        try {
            [$turnId, $commissionerId] = $this->fixtures($root);
            $path = $root.'/var/imperium/operational/citadel-legate-bounded-cognition-turns/'.$turnId.'.json';
            $turn = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            $turn['output']['output'] = 'Tampered.';
            $this->write($path, $turn);
            $this->expectExceptionMessage('CIT425_COGNITION_RESULT_DELIVERY_CHAIN_INVALID');
            (new LegateCognitionResultDeliveryService($root))->deliver($turnId, $commissionerId, new \DateTimeImmutable());
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
        $target = ['seat' => 'foundry.artificer', 'binding_id' => 'model-bound-operational-seat-binding-'.str_repeat('b', 20), 'binding_digest' => str_repeat('1', 64), 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1];
        $contract = ['task' => 'Recommend.', 'purpose' => 'Answer.', 'inputs' => ['sealed-input-digest'], 'evidence_requirements' => ['cite'], 'constraints' => ['no tools'], 'output_contract' => ['one answer'], 'stop_conditions' => ['evidence absent']];
        $commissionId = 'citadel-legate-governed-commission-'.str_repeat('c', 20);
        $commission = $this->record([
            'schema' => 'imperium.citadel-legate-governed-commission/v1', 'commission_id' => $commissionId, 'instance_id' => $instance,
            'issuer' => ['seat' => 'curia.seneschal', 'binding_id' => $commissionerId, 'binding_digest' => $commissioner['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1],
            'target' => $target, 'contract' => $contract, 'sealed' => true,
        ]);
        $dispositionId = 'citadel-legate-governed-commission-disposition-'.str_repeat('d', 20);
        $disposition = $this->record(['schema' => 'imperium.citadel-legate-governed-commission-disposition/v1', 'disposition_id' => $dispositionId, 'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']], 'disposition' => 'ACCEPTED', 'sealed' => true]);
        $decisionId = 'citadel-legate-cognition-turn-authorization-decision-'.str_repeat('e', 20);
        $decision = $this->record(['schema' => 'imperium.citadel-legate-cognition-turn-authorization-decision/v1', 'decision_id' => $decisionId, 'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']], 'source_commission_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']], 'decision' => 'AUTHORIZED', 'sealed' => true]);
        $activationId = 'citadel-legate-provider-invocation-activation-'.str_repeat('f', 20);
        $activation = $this->record(['schema' => 'imperium.clavium-citadel-legate-provider-invocation-activation/v1', 'activation_id' => $activationId, 'source_cognition_turn_authorization' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'sealed' => true]);
        $output = ['disposition' => 'COMPLETED', 'output' => 'One bounded recommendation.', 'evidence_references' => ['sealed-input-digest'], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => 'Complete.'];
        $turnId = 'citadel-legate-bounded-cognition-turn-'.str_repeat('1', 20);
        $turn = $this->record([
            'schema' => 'imperium.citadel-legate-bounded-cognition-turn/v1', 'turn_id' => $turnId, 'instance_id' => $instance, 'case_id' => 'case', 'case_digest' => str_repeat('2', 64),
            'source_provider_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']],
            'source_cognition_turn_authorization' => ['id' => $decisionId, 'digest' => $decision['record_digest']],
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_commission_disposition' => ['id' => $dispositionId, 'digest' => $disposition['record_digest']],
            'target' => $target, 'contract' => $contract, 'output' => $output,
            'bounded_cognition_turn_authority' => ['consumed' => true], 'credential_lease' => ['consumed' => true],
            'status' => 'CITADEL_LEGATE_GOVERNED_COGNITION_TURN_COMPLETED_SEALED_NO_CONTINUING_AUTHORITY',
            'provider_invoked' => true, 'cognition_performed' => true, 'turns_consumed' => 1,
            'commission_exercisable' => false, 'governed_cognition_authority' => false, 'provider_invocation_authority' => false, 'continuing_turn_authority' => false, 'sealed' => true,
        ]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$commissionerId.'.json', $commissioner);
        $this->write($root.'/var/imperium/operational/citadel-legate-governed-commissions/'.$commissionId.'.json', $commission);
        $this->write($root.'/var/imperium/operational/citadel-legate-governed-commission-dispositions/'.$dispositionId.'.json', $disposition);
        $this->write($root.'/var/imperium/operational/citadel-legate-cognition-turn-authorization-decisions/'.$decisionId.'.json', $decision);
        $this->write($root.'/var/imperium/offices/clavium/citadel-legate-provider-invocation-activations/'.$activationId.'.json', $activation);
        $this->write($root.'/var/imperium/operational/citadel-legate-bounded-cognition-turns/'.$turnId.'.json', $turn);

        return [$turnId, $commissionerId, $output];
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
