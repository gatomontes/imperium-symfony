<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Citadel\DelegateMissionTurnRecoveryService;
use PHPUnit\Framework\TestCase;

final class DelegateMissionTurnRecoveryServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-turn-recovery-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testAuthorizedRecoveryPersistsTurnWithoutProviderAndExactlyReplays(): void
    {
        $authorizationId = $this->seed();
        $service = new DelegateMissionTurnRecoveryService($this->root);
        $at = new \DateTimeImmutable('2026-08-25T15:00:00+00:00');
        $turn = $service->recover($authorizationId, $at);
        $replay = $service->recover($authorizationId, $at->modify('+1 minute'));

        self::assertSame($turn, $replay);
        self::assertSame('COMPLETED', $turn['result']['disposition']);
        self::assertFalse($turn['recovery']['provider_reinvoked']);
        self::assertFalse($turn['provider_invocation_authority']);
        self::assertCount(1, glob($this->root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns/*.json') ?: []);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
    }

    public function testMalformedEnvelopePayloadFailsBeforeAuthorityConsumption(): void
    {
        $authorizationId = $this->seed('not-json');

        try {
            (new DelegateMissionTurnRecoveryService($this->root))->recover($authorizationId, new \DateTimeImmutable('2026-08-25T15:00:00+00:00'));
            self::fail('Expected invalid recovery payload.');
        } catch (\RuntimeException $exception) {
            self::assertSame('CT333_DELEGATE_TURN_RECOVERY_PAYLOAD_INVALID', $exception->getMessage());
        }
        self::assertSame([], glob($this->root.'/var/imperium/runtime/authority-consumptions/*.json') ?: []);
        self::assertSame([], glob($this->root.'/var/imperium/operational/delegate-mission-bounded-cognition-turns/*.json') ?: []);
    }

    public function testExpiredRecoveryAuthorizationFailsStopped(): void
    {
        $authorizationId = $this->seed();

        $this->expectExceptionMessage('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID');
        (new DelegateMissionTurnRecoveryService($this->root))->recover($authorizationId, new \DateTimeImmutable('2026-08-25T17:00:00+00:00'));
    }

    public function testDifferentAuthorizationCannotClaimReplayOfRecoveredTurn(): void
    {
        $authorizationId = $this->seed();
        $service = new DelegateMissionTurnRecoveryService($this->root);
        $service->recover($authorizationId, new \DateTimeImmutable('2026-08-25T15:00:00+00:00'));
        $authorization = json_decode((string) file_get_contents($this->root.'/var/imperium/runtime/provider-turn-recovery-authorizations/'.$authorizationId.'.json'), true, 512, JSON_THROW_ON_ERROR);
        unset($authorization['record_digest']);
        $otherId = 'provider-turn-recovery-'.str_repeat('e', 20);
        $authorization['authorization_id'] = $otherId;
        $authorization['recovery_authority']['authority_id'] = 'different-recovery-authority';
        $this->write('var/imperium/runtime/provider-turn-recovery-authorizations/'.$otherId.'.json', $authorization);

        $this->expectExceptionMessage('CT332_DELEGATE_TURN_RECOVERY_CONFLICT');
        $service->recover($otherId, new \DateTimeImmutable('2026-08-25T15:01:00+00:00'));
    }

    public function testTamperedRecoveryAuthorizationFailsBeforeTransition(): void
    {
        $authorizationId = $this->seed();
        $path = $this->root.'/var/imperium/runtime/provider-turn-recovery-authorizations/'.$authorizationId.'.json';
        $authorization = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $authorization['status'] = 'TAMPERED';
        file_put_contents($path, json_encode($authorization, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('CT330_DELEGATE_TURN_RECOVERY_AUTHORIZATION_INVALID');
        (new DelegateMissionTurnRecoveryService($this->root))->recover($authorizationId, new \DateTimeImmutable('2026-08-25T15:00:00+00:00'));
    }

    private function seed(?string $response = null): string
    {
        $response ??= json_encode(['disposition' => 'COMPLETED', 'output' => 'Recovered.', 'evidence_references' => [], 'uncertainties' => [], 'stop_condition_triggered' => false, 'stop_rationale' => null], JSON_THROW_ON_ERROR);
        $claimId = 'provider-invocation-'.str_repeat('a', 20);
        $activationId = 'delegate-mission-provider-invocation-activation-'.str_repeat('b', 20);
        $commissionId = 'delegate-mission-bounded-cognition-commission-'.str_repeat('c', 20);
        $authorizationId = 'provider-turn-recovery-'.str_repeat('d', 20);
        $commission = $this->write('var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions/'.$commissionId.'.json', ['commission_id' => $commissionId]);
        $activation = $this->write('var/imperium/offices/clavium/delegate-mission-provider-invocation-activations/'.$activationId.'.json', [
            'activation_id' => $activationId,
            'instance_id' => 'imperium-test',
            'source_commission' => ['id' => $commissionId, 'digest' => $commission['record_digest']],
            'source_model_binding' => ['id' => 'binding', 'digest' => str_repeat('1', 64)],
            'source_access_attestation' => ['id' => 'attestation', 'digest' => str_repeat('2', 64)],
            'target' => ['commission_id' => $commissionId, 'manifestation_id' => 'manifestation', 'occupancy_generation' => 1],
            'model' => ['runtime_binding' => ['provider' => 'deepseek']],
        ]);
        $claim = $this->write('var/imperium/runtime/provider-invocations/'.$claimId.'.json', [
            'claim_id' => $claimId,
            'source_activation' => ['id' => $activationId, 'digest' => $activation['record_digest']],
            'target' => $activation['target'],
            'lease_consumption' => ['lease_id' => 'lease', 'consumed' => true],
            'turn_authority_consumption' => ['authority_id' => 'turn-authority', 'consumed' => true],
            'provider_request' => ['external_io_started' => false],
            'recovery' => ['automatic_replay_permitted' => false],
            'status' => 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO',
        ]);
        $envelope = $this->write('var/imperium/runtime/provider-response-envelopes/'.$claimId.'.json', [
            'envelope_id' => $claimId,
            'claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'provider_response_identity' => 'sha256:'.hash('sha256', $response),
            'response' => $response,
            'credential_material_present' => false,
            'sealed_at' => '2026-08-25T14:00:00+00:00',
            'automatic_provider_replay_permitted' => false,
        ]);
        $this->write('var/imperium/runtime/provider-invocation-journal/'.$claimId.'.json', [
            'claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'provider_response_identity' => $envelope['provider_response_identity'],
            'status' => 'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING',
        ]);
        $this->write('var/imperium/runtime/provider-turn-recovery-authorizations/'.$authorizationId.'.json', [
            'authorization_id' => $authorizationId,
            'claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'response_envelope' => ['id' => $claimId, 'digest' => $envelope['record_digest']],
            'recovery_authority' => ['authority_id' => 'provider-turn-recovery-authority', 'authority_single_use' => true, 'authority_exercisable' => true, 'consumed' => false, 'expires_at' => '2026-08-25T16:00:00+00:00'],
            'status' => 'AUTHORIZED_PENDING_PROVIDER_TURN_FORWARD_RECOVERY',
            'provider_invocation_authority' => false,
        ]);

        return $authorizationId;
    }

    private function write(string $relativePath, array $record): array
    {
        $path = $this->root.'/'.$relativePath;
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_THROW_ON_ERROR));

        return $record;
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
