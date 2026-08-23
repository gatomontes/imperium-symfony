<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\LegateCognitionTurnAuthorizationService;
use PHPUnit\Framework\TestCase;

final class LegateCognitionTurnAuthorizationServiceTest extends TestCase
{
    public function testOriginalCommissionerAuthorizesOneShortLivedTurnWithoutInvokingProvider(): void
    {
        $root = sys_get_temp_dir().'/imperium-cognition-turn-auth-'.bin2hex(random_bytes(5));
        try {
            [$dispositionId, $issuerId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $service = new LegateCognitionTurnAuthorizationService($root);
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $expires = $at->modify('+10 minutes');
            $decision = $service->decide($dispositionId, $issuerId, 'AUTHORIZED', 'One exact bounded turn is warranted.', $expires, $at);

            self::assertSame('CITADEL_LEGATE_COGNITION_TURN_AUTHORIZED_PENDING_PROVIDER_INVOCATION_ACTIVATION', $decision['status']);
            self::assertSame('curia.seneschal', $decision['authorizer']['seat']);
            self::assertTrue($decision['governed_cognition_authority']);
            self::assertSame(1, $decision['bounded_cognition_turn_authority']['maximum_turns']);
            self::assertTrue($decision['bounded_cognition_turn_authority']['authority_single_use']);
            self::assertFalse($decision['bounded_cognition_turn_authority']['consumed']);
            self::assertTrue($decision['provider_invocation_activation_required']);
            self::assertTrue($decision['provider_invocation_activation_authority']['authority_single_use']);
            self::assertSame('clavium.locksmith', $decision['provider_invocation_activation_authority']['destination']);
            self::assertFalse($decision['provider_invocation_activation_authority']['consumed']);
            self::assertFalse($decision['commission_exercisable']);
            foreach (['provider_invocation_authority', 'provider_invoked', 'cognition_performed', 'operational_use_permitted', 'autonomous_cognition_authority', 'tool_use_authority', 'credential_use_authority', 'external_action_authority', 'execution_authority', 'continuing_turn_authority'] as $field) {
                self::assertFalse($decision[$field]);
            }
            self::assertSame($decision, $service->decide($dispositionId, $issuerId, 'AUTHORIZED', 'One exact bounded turn is warranted.', $expires, $at->modify('+1 minute')));
        } finally {
            $this->remove($root);
        }
    }

    public function testRefusalCreatesNoTurnAuthority(): void
    {
        $root = sys_get_temp_dir().'/imperium-cognition-turn-refused-'.bin2hex(random_bytes(5));
        try {
            [$dispositionId, $issuerId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $decision = (new LegateCognitionTurnAuthorizationService($root))->decide($dispositionId, $issuerId, 'REFUSED', 'No cognition is presently required.', $at->modify('+10 minutes'), $at);

            self::assertSame('CITADEL_LEGATE_COGNITION_TURN_REFUSED_NO_AUTHORITY', $decision['status']);
            self::assertNull($decision['bounded_cognition_turn_authority']);
            self::assertFalse($decision['governed_cognition_authority']);
            self::assertFalse($decision['provider_invocation_activation_required']);
            self::assertFalse($decision['provider_invocation_authority']);
            self::assertFalse($decision['execution_authority']);
        } finally {
            $this->remove($root);
        }
    }

    public function testAuthorizationLongerThanFifteenMinutesFailsClosed(): void
    {
        $root = sys_get_temp_dir().'/imperium-cognition-turn-expiry-'.bin2hex(random_bytes(5));
        try {
            [$dispositionId, $issuerId] = $this->fixtures($root, '2026-08-24T20:00:00+00:00');
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $this->expectExceptionMessage('CIT344_COGNITION_TURN_EXPIRY_INVALID');
            (new LegateCognitionTurnAuthorizationService($root))->decide($dispositionId, $issuerId, 'AUTHORIZED', 'Too long.', $at->modify('+16 minutes'), $at);
        } finally {
            $this->remove($root);
        }
    }

    public function testExpiredModelAccessCannotAuthorizeTurn(): void
    {
        $root = sys_get_temp_dir().'/imperium-cognition-turn-access-expired-'.bin2hex(random_bytes(5));
        try {
            [$dispositionId, $issuerId] = $this->fixtures($root, '2026-08-23T20:00:00+00:00');
            $at = new \DateTimeImmutable('2026-08-23T20:00:00+00:00');
            $this->expectExceptionMessage('CIT349_COGNITION_TURN_AUTHORIZATION_CHAIN_INVALID');
            (new LegateCognitionTurnAuthorizationService($root))->decide($dispositionId, $issuerId, 'AUTHORIZED', 'Attempt.', $at->modify('+10 minutes'), $at);
        } finally {
            $this->remove($root);
        }
    }

    private function fixtures(string $root, string $accessExpiresAt): array
    {
        $targetId = 'model-bound-operational-seat-binding-'.str_repeat('a', 20);
        $target = $this->record(['schema' => 'imperium.model-bound-operational-manifestation-seat-binding/v1', 'binding_id' => $targetId, 'instance_id' => 'imperium-test', 'seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true, 'sealed' => true]);
        $issuerId = 'operational-seat-binding-'.str_repeat('b', 20);
        $issuer = $this->record(['schema' => 'imperium.operational-seat-binding/v1', 'binding_id' => $issuerId, 'instance_id' => 'imperium-test', 'seat' => 'curia.seneschal', 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1, 'status' => 'ACTIVE', 'binding_atomic' => true, 'governed_commission_issuance_authority' => true, 'commissionable_seats' => ['foundry.artificer'], 'sealed' => true]);
        $attestationId = 'profile-model-access-attestation-'.str_repeat('c', 20);
        $attestation = $this->record(['schema' => 'imperium.clavium-profile-model-access-attestation/v1', 'attestation_id' => $attestationId, 'status' => 'ACCESS_AVAILABLE', 'provider_access_evidence' => ['expires_at' => $accessExpiresAt], 'sealed' => true]);
        $activationId = 'citadel-legate-runtime-activation-'.str_repeat('d', 20);
        $activation = $this->record(['schema' => 'imperium.conscription-citadel-legate-runtime-activation/v1', 'activation_id' => $activationId, 'instance_id' => 'imperium-test', 'source_model_binding' => ['id' => 'profile-model-binding-test', 'digest' => str_repeat('9', 64)], 'seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1, 'status' => 'MODEL_BOUND_CITADEL_LEGATE_RUNTIME_ACTIVE_PENDING_GOVERNED_COMMISSION', 'runtime_active' => true, 'commission_intake_available' => true, 'sealed' => true]);
        $contract = ['task' => 'Recommend.', 'purpose' => 'Answer.', 'inputs' => ['input'], 'evidence_requirements' => ['cite'], 'constraints' => ['no tools'], 'output_contract' => ['one answer'], 'stop_conditions' => ['evidence absent']];
        $commissionId = 'citadel-legate-governed-commission-'.str_repeat('e', 20);
        $commission = $this->record(['schema' => 'imperium.citadel-legate-governed-commission/v1', 'commission_id' => $commissionId, 'instance_id' => 'imperium-test', 'issuer' => ['seat' => 'curia.seneschal', 'binding_id' => $issuerId, 'binding_digest' => $issuer['record_digest'], 'manifestation_id' => 'manifestation-seneschal', 'occupancy_generation' => 1], 'target' => ['seat' => 'foundry.artificer', 'binding_id' => $targetId, 'binding_digest' => $target['record_digest'], 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1, 'runtime_activation_id' => $activationId, 'runtime_activation_digest' => $activation['record_digest']], 'contract' => $contract, 'status' => 'CITADEL_LEGATE_GOVERNED_COMMISSION_ISSUED_PENDING_LEGATE_ACCEPTANCE', 'sealed' => true]);
        $dispositionId = 'citadel-legate-governed-commission-disposition-'.str_repeat('f', 20);
        $disposition = $this->record(['schema' => 'imperium.citadel-legate-governed-commission-disposition/v1', 'disposition_id' => $dispositionId, 'instance_id' => 'imperium-test', 'case_id' => 'case-artificer', 'case_digest' => str_repeat('1', 64), 'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']], 'source_runtime_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']], 'source_access_attestation' => ['id' => $attestationId, 'digest' => $attestation['record_digest']], 'actor' => ['seat' => 'foundry.artificer', 'binding_id' => $targetId, 'binding_digest' => $target['record_digest'], 'manifestation_id' => 'manifestation-artificer', 'occupancy_generation' => 1], 'contract' => $contract, 'disposition' => 'ACCEPTED', 'status' => 'CITADEL_LEGATE_GOVERNED_COMMISSION_ACCEPTED_PENDING_COGNITION_TURN_AUTHORIZATION', 'commission_accepted' => true, 'commission_bound' => true, 'commission_exercisable' => false, 'cognition_turn_authorization_required' => true, 'governed_cognition_authority' => false, 'provider_invocation_authority' => false, 'execution_authority' => false, 'sealed' => true]);
        $this->write($root.'/var/imperium/operational/occupancy/'.$targetId.'.json', $target);
        $this->write($root.'/var/imperium/operational/occupancy/'.$issuerId.'.json', $issuer);
        $this->write($root.'/var/imperium/offices/clavium/profile-model-access-attestations/'.$attestationId.'.json', $attestation);
        $this->write($root.'/var/imperium/operational/citadel-legate-runtime-activations/'.$activationId.'.json', $activation);
        $this->write($root.'/var/imperium/operational/citadel-legate-governed-commissions/'.$commissionId.'.json', $commission);
        $this->write($root.'/var/imperium/operational/citadel-legate-governed-commission-dispositions/'.$dispositionId.'.json', $disposition);

        return [$dispositionId, $issuerId];
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
