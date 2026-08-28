<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\ProviderInvocationClaimService;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use PHPUnit\Framework\TestCase;

final class ProviderInvocationClaimServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-provider-claim-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testClaimsLeaseAndTurnAuthorityBeforeExternalIo(): void
    {
        [$activationId, $authorityId] = $this->seedActivation();
        $at = new \DateTimeImmutable('2026-08-25T12:00:00+00:00');

        $claim = (new ProviderInvocationClaimService($this->root))->claim($activationId, $authorityId, $at);

        self::assertSame('INVOCATION_CLAIMED_PENDING_EXTERNAL_IO', $claim['status']);
        self::assertTrue($claim['lease_consumption']['consumed']);
        self::assertTrue($claim['turn_authority_consumption']['consumed']);
        self::assertFalse($claim['provider_request']['external_io_started']);
        self::assertFalse($claim['recovery']['automatic_replay_permitted']);
        self::assertTrue($claim['recovery']['unknown_outcome_requires_governed_resolution']);
        $transaction = $claim['transactional_consumption'];
        $leaseId = 'delegate-mission-provider-credential-lease-'.str_repeat('d', 20);
        $lockScope = 'provider-invocation-claim:'.hash('sha256', $activationId);
        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame($claim['claim_id'], $transaction['transaction_id']);
        self::assertSame($claim['claim_fingerprint'], $transaction['replay_fingerprint']);
        self::assertSame([$authorityId, $leaseId], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame([1, 2], array_column($transaction['lock_plan'], 'order'));
        self::assertSame([$lockScope, $lockScope], array_column($transaction['lock_plan'], 'scope'));
        self::assertSame('COMMITTED', $transaction['consumption_result']['state']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['retry']['provider_reinvocation_permitted']);
        self::assertFalse($transaction['recovery']['rollback']['authority_unconsume_permitted']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertMatchesRegularExpression('/^imperium-provider-invocation-[a-f0-9]{20}$/', $claim['provider_request']['idempotency_key']);
        self::assertFalse($claim['credential_material_present']);
        self::assertStringNotContainsString('clavium://', CanonicalJson::encode($claim));
        self::assertStringNotContainsString('DEEPSEEK_API_KEY', CanonicalJson::encode($claim));
        self::assertFileDoesNotExist($this->root.'/var/imperium/runtime/provider-invocations.lock');
        self::assertNotEmpty(glob($this->root.'/var/imperium/runtime/transition-locks/*.lock') ?: []);
        self::assertCount(0, glob($this->root.'/var/imperium/runtime/provider-invocation-journal/*.json') ?: []);
    }

    public function testStructurallyDivergentTransactionalEnvelopeFailsReplayStopped(): void
    {
        [$activationId, $authorityId] = $this->seedActivation();
        $service = new ProviderInvocationClaimService($this->root);
        $claim = $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:00:00+00:00'));
        $path = $this->root.'/var/imperium/runtime/provider-invocations/'.$claim['claim_id'].'.json';
        unset($claim['record_digest']);
        $claim['transactional_consumption']['lock_plan'][0]['scope'] = 'invented-lock-scope';
        $this->writeRecord($path, $claim);

        $this->expectExceptionMessage('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:01:00+00:00'));
    }

    public function testHistoricalClaimWithoutTransactionalEnvelopeRemainsExactReplay(): void
    {
        [$activationId, $authorityId, $activationPath] = $this->seedActivation();
        $service = new ProviderInvocationClaimService($this->root);
        $claim = $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:00:00+00:00'));
        $activation = json_decode((string) file_get_contents($activationPath), true, 512, JSON_THROW_ON_ERROR);
        $path = $this->root.'/var/imperium/runtime/provider-invocations/'.$claim['claim_id'].'.json';
        unset($claim['record_digest'], $claim['transactional_consumption']);
        $claim['claim_fingerprint'] = ReplayFingerprint::of([
            'activation_id' => $activationId,
            'activation_digest' => $activation['record_digest'],
            'turn_authority_id' => $authorityId,
            'lease_id' => $activation['credential_lease']['lease_id'],
        ]);
        $this->writeRecord($path, $claim);

        $replayed = $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:01:00+00:00'));

        self::assertSame($claim['claim_fingerprint'], $replayed['claim_fingerprint']);
        self::assertArrayNotHasKey('transactional_consumption', $replayed);
    }

    public function testTwoClaimantsProduceOneStableClaim(): void
    {
        [$activationId, $authorityId] = $this->seedActivation();
        $first = (new ProviderInvocationClaimService($this->root))->claim(
            $activationId,
            $authorityId,
            new \DateTimeImmutable('2026-08-25T12:00:00+00:00'),
        );
        $second = (new ProviderInvocationClaimService($this->root))->claim(
            $activationId,
            $authorityId,
            new \DateTimeImmutable('2026-08-25T12:01:00+00:00'),
        );

        self::assertSame($first, $second);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/provider-invocations/*.json') ?: []);
    }

    public function testTwoProcessesConvergeOnOneTransactionalClaim(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        [$activationId, $authorityId] = $this->seedActivation();
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/provider-invocation-claim-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        for ($i = 0; $i < 2; ++$i) {
            $processes[$i] = proc_open([PHP_BINARY, $worker, $this->root, $activationId, $authorityId, $gate], $descriptors, $pipes[$i]);
            self::assertIsResource($processes[$i]);
        }
        touch($gate);
        $results = [];
        for ($i = 0; $i < 2; ++$i) {
            $results[] = stream_get_contents($pipes[$i][1]);
            $errors = stream_get_contents($pipes[$i][2]);
            fclose($pipes[$i][1]);
            fclose($pipes[$i][2]);
            self::assertSame(0, proc_close($processes[$i]));
            self::assertSame('', $errors);
        }
        self::assertSame($results[0], $results[1]);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/provider-invocations/*.json') ?: []);
        $claim = json_decode((string) file_get_contents((glob($this->root.'/var/imperium/runtime/provider-invocations/*.json') ?: [])[0]), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame([$authorityId, 'delegate-mission-provider-credential-lease-'.str_repeat('d', 20)], array_column($claim['transactional_consumption']['authority_set'], 'authority_id'));
    }

    public function testMismatchedAuthorityFailsStopped(): void
    {
        [$activationId] = $this->seedActivation();

        $this->expectExceptionMessage('CLV404_PROVIDER_INVOCATION_CLAIM_CHAIN_INVALID');
        (new ProviderInvocationClaimService($this->root))->claim(
            $activationId,
            'wrong-authority',
            new \DateTimeImmutable('2026-08-25T12:00:00+00:00'),
        );
    }

    public function testExpiredLeaseFailsStopped(): void
    {
        [$activationId, $authorityId] = $this->seedActivation('2026-08-25T12:00:00+00:00');

        $this->expectExceptionMessage('CLV404_PROVIDER_INVOCATION_CLAIM_CHAIN_INVALID');
        (new ProviderInvocationClaimService($this->root))->claim(
            $activationId,
            $authorityId,
            new \DateTimeImmutable('2026-08-25T12:00:00+00:00'),
        );
    }

    public function testChangedActivationCannotProduceSecondClaim(): void
    {
        [$activationId, $authorityId, $path] = $this->seedActivation();
        $service = new ProviderInvocationClaimService($this->root);
        $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:00:00+00:00'));

        $activation = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        unset($activation['record_digest']);
        $activation['model']['configuration']['temperature'] = 0.9;
        $this->writeRecord($path, $activation);

        $this->expectExceptionMessage('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:01:00+00:00'));
    }

    public function testTamperedStoredClaimFailsStoppedInsteadOfReplaying(): void
    {
        [$activationId, $authorityId] = $this->seedActivation();
        $service = new ProviderInvocationClaimService($this->root);
        $claim = $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:00:00+00:00'));
        $path = $this->root.'/var/imperium/runtime/provider-invocations/'.$claim['claim_id'].'.json';
        $claim['status'] = 'TAMPERED';
        file_put_contents($path, json_encode($claim, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('CLV403_PROVIDER_INVOCATION_CLAIM_CONFLICT');
        $service->claim($activationId, $authorityId, new \DateTimeImmutable('2026-08-25T12:01:00+00:00'));
    }

    private function seedActivation(string $expiresAt = '2026-08-25T13:00:00+00:00'): array
    {
        $activationId = 'delegate-mission-provider-invocation-activation-'.str_repeat('a', 20);
        $authorityId = 'delegate-mission-bounded-cognition-turn-authority-'.str_repeat('b', 20);
        $path = $this->root.'/var/imperium/offices/clavium/delegate-mission-provider-invocation-activations/'.$activationId.'.json';
        $record = [
            'schema' => 'imperium.clavium-delegate-mission-provider-invocation-activation/v1',
            'activation_id' => $activationId,
            'instance_id' => 'imperium-test',
            'target' => [
                'commission_id' => 'delegate-commission-'.str_repeat('c', 20),
                'manifestation_id' => 'manifestation-delegate',
                'occupancy_generation' => 1,
            ],
            'model' => [
                'provider_model_version' => 'deepseek/delegate@2026-08-01',
                'runtime_binding' => [
                    'provider' => 'deepseek',
                    'platform_service' => 'ai.platform.generic.deepseek',
                    'runtime_model' => 'deepseek-v4-flash',
                ],
                'configuration' => ['temperature' => 0.2],
            ],
            'credential_lease' => [
                'lease_id' => 'delegate-mission-provider-credential-lease-'.str_repeat('d', 20),
                'authority_single_use' => true,
                'credential_reference_digest' => hash('sha256', 'clavium://providers/deepseek/default'),
                'credential_reference_disclosed' => false,
                'credential_possession_transferred' => false,
                'scope' => ['model.invoke'],
                'provider' => 'deepseek',
                'expires_at' => $expiresAt,
                'consumed' => false,
            ],
            'bounded_cognition_turn_authority' => [
                'authority_id' => $authorityId,
                'authority_single_use' => true,
                'authority_exercisable' => true,
                'maximum_turns' => 1,
                'consumed' => false,
            ],
            'status' => 'DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN',
            'provider_invocation_authority' => true,
            'credential_use_authority' => true,
            'sealed' => true,
        ];
        $this->writeRecord($path, $record);

        return [$activationId, $authorityId, $path];
    }

    private function writeRecord(string $path, array $record): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0770, true);
        }
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        file_put_contents($path, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
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
