<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimFaultInjector;
use App\Imperium\Runtime\Clavium\OperationalCognitionInvocationClaimService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationalCognitionInvocationClaimServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-operational-claim-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testAtomicallyConsumesLeaseAndCognitionAuthorityIntoDurablePreIoClaim(): void
    {
        [$leaseId, $authorityId] = $this->fixtures();
        $service = new OperationalCognitionInvocationClaimService($this->root);
        $at = new \DateTimeImmutable('2026-08-26T16:03:00+00:00');
        $claim = $service->claim($leaseId, $authorityId, $at);

        self::assertSame('OPERATIONAL_INVOCATION_CLAIMED_DURABLE_PRE_IO', $claim['status']);
        self::assertTrue($claim['lease_consumption']['consumed']);
        self::assertTrue($claim['cognition_authority_consumption']['consumed']);
        self::assertMatchesRegularExpression('/^imperium-operational-cognition-invocation-claim-[a-f0-9]{20}$/', $claim['provider_request']['idempotency_identity']);
        self::assertFalse($claim['provider_request']['external_io_started']);
        self::assertFalse($claim['provider_invoked']);
        self::assertFalse($claim['credential_resolved']);
        self::assertFalse($claim['credential_material_present']);
        self::assertFalse($claim['network_access_performed']);
        self::assertFalse($claim['recovery']['automatic_replay_permitted']);
        self::assertTrue($claim['recovery']['unknown_outcome_requires_governed_resolution']);
        $transaction = $claim['transactional_consumption'];
        self::assertSame('imperium.runtime-transactional-authority-consumption/v1', $transaction['schema']);
        self::assertSame($claim['claim_id'], $transaction['transaction_id']);
        self::assertSame($claim['claim_fingerprint'], $transaction['replay_fingerprint']);
        self::assertSame([$authorityId, $leaseId], array_column($transaction['authority_set'], 'authority_id'));
        self::assertSame([1, 2], array_column($transaction['lock_plan'], 'order'));
        self::assertSame('oca-cognition-authority:'.hash('sha256', $authorityId), $transaction['lock_plan'][0]['scope']);
        self::assertSame('oca-lease:'.hash('sha256', $leaseId), $transaction['lock_plan'][1]['scope']);
        self::assertSame('COMMITTED', $transaction['consumption_result']['state']);
        self::assertFalse($transaction['consumption_result']['continuing_authority']);
        self::assertSame('imperium.runtime-authority-consumption-recovery/v1', $transaction['recovery']['schema']);
        self::assertSame('COMPLETE', $transaction['recovery']['checkpoint']);
        self::assertFalse($transaction['recovery']['retry']['automatic_retry_permitted']);
        self::assertFalse($transaction['recovery']['rollback']['authority_unconsume_permitted']);
        self::assertFalse($transaction['recovery']['retry']['provider_reinvocation_permitted']);
        self::assertFalse($transaction['recovery']['external_effect']['started']);
        self::assertSame($claim, $service->claim($leaseId, $authorityId, $at->modify('+1 minute')));
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: []);
        $serialized = CanonicalJson::encode($claim);
        foreach (['"credential_ref":', 'DEEPSEEK_API_KEY', 'Bearer ', 'https://api.'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, $serialized);
        }
    }

    public function testStructurallyDivergentTransactionalEnvelopeFailsReplayStopped(): void
    {
        [$leaseId, $authorityId] = $this->fixtures();
        $service = new OperationalCognitionInvocationClaimService($this->root);
        $claim = $service->claim($leaseId, $authorityId, new \DateTimeImmutable('2026-08-26T16:03:00+00:00'));
        $path = $this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/'.$claim['claim_id'].'.json';
        unset($claim['record_digest']);
        $claim['transactional_consumption']['lock_plan'][0]['scope'] = 'oca-lease:'.hash('sha256', $leaseId);
        $this->write($path, $claim);

        $this->expectExceptionMessage('OCA405_OPERATIONAL_INVOCATION_CLAIM_CONFLICT');
        $service->claim($leaseId, $authorityId, new \DateTimeImmutable('2026-08-26T16:04:00+00:00'));
    }

    #[DataProvider('transactionCheckpointCases')]
    public function testEveryTransactionCheckpointRecoversToOneExactImmutableClaim(string $checkpoint, int $durableAfterFailure): void
    {
        [$leaseId, $authorityId] = $this->fixtures();
        $at = new \DateTimeImmutable('2026-08-26T16:03:00+00:00');
        $fault = new class($checkpoint) implements OperationalCognitionInvocationClaimFaultInjector {
            public function __construct(private readonly string $selected)
            {
            }

            public function after(string $checkpoint): void
            {
                if ($this->selected === $checkpoint) {
                    throw new \RuntimeException('TEST_OPERATIONAL_CLAIM_FAULT_'.$checkpoint);
                }
            }
        };

        try {
            (new OperationalCognitionInvocationClaimService($this->root, faults: $fault))->claim($leaseId, $authorityId, $at);
            self::fail('The selected transaction checkpoint did not inject a failure.');
        } catch (\RuntimeException $exception) {
            self::assertSame('TEST_OPERATIONAL_CLAIM_FAULT_'.$checkpoint, $exception->getMessage());
        }

        $claimPaths = glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: [];
        self::assertCount($durableAfterFailure, $claimPaths);

        $service = new OperationalCognitionInvocationClaimService($this->root);
        $recovered = $service->claim($leaseId, $authorityId, $at);
        $replayed = $service->claim($leaseId, $authorityId, $at->modify('+1 minute'));

        self::assertSame($recovered, $replayed);
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: []);
        self::assertTrue($recovered['lease_consumption']['consumed']);
        self::assertTrue($recovered['cognition_authority_consumption']['consumed']);
        self::assertSame('COMPLETE', $recovered['transactional_consumption']['recovery']['checkpoint']);
        self::assertSame([$authorityId, $leaseId], array_column($recovered['transactional_consumption']['authority_set'], 'authority_id'));
        self::assertFalse($recovered['provider_request']['external_io_started']);
        self::assertFalse($recovered['provider_invoked']);
        self::assertFalse($recovered['credential_resolved']);
        self::assertFalse($recovered['network_access_performed']);
        self::assertCount(0, glob($this->root.'/var/imperium/runtime/provider-invocation-journal/*.json') ?: []);
    }

    public static function transactionCheckpointCases(): array
    {
        return [
            ['PREPARED', 0],
            ['CONSUMPTION_COMMITTED', 1],
            ['RESULT_COMMITTED', 1],
            ['COMPLETE', 1],
        ];
    }

    #[DataProvider('invalidSourceCases')]
    public function testExpiredConsumedAndSubstitutedSourcesFailStopped(string $case): void
    {
        [$leaseId, $authorityId, $leasePath, $requestPath] = $this->fixtures();
        if ('expired' === $case || 'consumed' === $case) {
            $lease = $this->readUnsealed($leasePath);
            if ('expired' === $case) {
                $lease['expires_at'] = '2026-08-26T16:02:59+00:00';
            } else {
                $lease['lease_consumed'] = true;
            }
            $this->write($leasePath, $lease);
        } else {
            $request = $this->readUnsealed($requestPath);
            $request['target']['manifestation_id'] = 'manifestation-substituted';
            $this->write($requestPath, $request);
        }

        $this->expectExceptionMessage('OCA403_OPERATIONAL_INVOCATION_CLAIM_CHAIN_INVALID');
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $authorityId, new \DateTimeImmutable('2026-08-26T16:03:00+00:00'));
    }

    public static function invalidSourceCases(): array
    {
        return [['expired'], ['consumed'], ['substituted']];
    }

    public function testDivergentAuthorityAndPartialConsumptionFailStopped(): void
    {
        [$leaseId, $authorityId] = $this->fixtures();
        $claimDirectory = $this->root.'/var/imperium/runtime/operational-cognition-invocation-claims';
        $this->write($claimDirectory.'/partial.json', [
            'schema' => 'imperium.clavium-operational-cognition-invocation-claim/v1',
            'claim_id' => 'partial',
            'claim_fingerprint' => str_repeat('a', 64),
            'lease_consumption' => ['lease_id' => $leaseId, 'consumed' => true],
            'cognition_authority_consumption' => ['authority_id' => 'operational-cognition-authority-'.str_repeat('9', 20), 'consumed' => false],
            'sealed' => true,
        ]);

        $this->expectExceptionMessage('OCA406_PARTIAL_AUTHORITY_CONSUMPTION_DETECTED');
        (new OperationalCognitionInvocationClaimService($this->root))->claim($leaseId, $authorityId, new \DateTimeImmutable('2026-08-26T16:03:00+00:00'));
    }

    public function testTwoProcessesConvergeOnOneClaim(): void
    {
        if (!function_exists('proc_open')) {
            self::markTestSkipped('proc_open is required for process-level contention proof.');
        }
        [$leaseId, $authorityId] = $this->fixtures();
        $gate = $this->root.'/go';
        $worker = dirname(__DIR__, 2).'/fixtures/operational-cognition-claim-contender.php';
        $descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $processes = $pipes = [];
        for ($i = 0; $i < 2; ++$i) {
            $processes[$i] = proc_open([PHP_BINARY, $worker, $this->root, $leaseId, $authorityId, $gate], $descriptors, $pipes[$i]);
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
        self::assertCount(1, glob($this->root.'/var/imperium/runtime/operational-cognition-invocation-claims/*.json') ?: []);
    }

    private function fixtures(): array
    {
        $requestId = 'operational-cognition-request-'.str_repeat('a', 20);
        $authorityId = 'operational-cognition-authority-'.str_repeat('b', 20);
        $target = ['seat' => 'foundry.artificer', 'manifestation_id' => 'manifestation-artificer', 'binding_id' => 'operational-seat-binding-'.str_repeat('c', 20), 'binding_digest' => str_repeat('d', 64), 'custody_id' => 'persona-custody-'.str_repeat('e', 20), 'custody_digest' => str_repeat('f', 64)];
        $request = $this->sealed([
            'schema' => 'imperium.curia-operational-cognition-request/v1', 'request_id' => $requestId, 'instance_id' => 'imperium-test', 'case_id' => 'operational-case', 'case_digest' => str_repeat('1', 64),
            'target' => $target, 'input_digest' => str_repeat('2', 64), 'profile_model_requirements_digest' => str_repeat('3', 64), 'model_requirements' => ['provider' => 'deepseek', 'model' => 'deepseek-v4-flash'], 'iteration' => 1, 'expires_at' => '2026-08-26T16:15:00+00:00',
            'status' => 'OPERATIONAL_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION', 'cognition_authority' => true, 'cognition_authority_id' => $authorityId, 'cognition_authority_single_use' => true, 'cognition_authority_consumed' => false, 'sealed' => true,
        ]);
        $decisionId = 'operational-provider-resource-decision-'.str_repeat('4', 20);
        $configuration = ['temperature' => 0.2];
        $ceiling = ['maximum_input_tokens' => 4096, 'maximum_output_tokens' => 1024, 'maximum_cost_microusd' => 250000];
        $decision = $this->sealed([
            'schema' => 'imperium.imperator-operational-provider-resource-decision/v1', 'decision_id' => $decisionId, 'instance_id' => 'imperium-test', 'case_id' => 'operational-case', 'case_digest' => str_repeat('1', 64),
            'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target, 'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'disposition' => 'AUTHORIZED', 'expires_at' => '2026-08-26T16:10:00+00:00', 'status' => 'OPERATIONAL_PROVIDER_RESOURCE_AUTHORIZED_PENDING_CLAVIUM_LEASE', 'sealed' => true,
        ]);
        $leaseId = 'operational-cognition-lease-'.str_repeat('5', 20);
        $lease = $this->sealed([
            'schema' => 'imperium.clavium-operational-cognition-lease/v1', 'lease_id' => $leaseId, 'instance_id' => 'imperium-test', 'case_id' => 'operational-case', 'case_digest' => str_repeat('1', 64),
            'source_provider_resource_decision' => ['id' => $decisionId, 'digest' => $decision['record_digest']], 'source_cognition_request' => ['id' => $requestId, 'digest' => $request['record_digest']], 'target' => $target,
            'provider' => 'deepseek', 'model' => 'deepseek-v4-flash', 'model_configuration' => $configuration, 'resource_ceiling' => $ceiling, 'input_digest' => $request['input_digest'], 'profile_model_requirements_digest' => $request['profile_model_requirements_digest'], 'iteration' => 1,
            'expires_at' => '2026-08-26T16:07:00+00:00', 'status' => 'OPERATIONAL_COGNITION_LEASE_ISSUED_PENDING_DURABLE_INVOCATION_CLAIM', 'opaque' => true, 'lease_single_use' => true, 'lease_consumed' => false,
            'credential_reference_disclosed' => false, 'credential_material_present' => false, 'credential_use_authority' => false, 'network_access_authority' => false, 'provider_invocation_authority' => false, 'sealed' => true,
        ]);
        $requestPath = $this->root.'/var/imperium/offices/curia/operational-cognition-requests/'.$requestId.'.json';
        $decisionPath = $this->root.'/var/imperium/imperator/operational-provider-resource-decisions/'.$decisionId.'.json';
        $leasePath = $this->root.'/var/imperium/offices/clavium/operational-cognition-leases/'.$leaseId.'.json';
        $this->writeSealed($requestPath, $request);
        $this->writeSealed($decisionPath, $decision);
        $this->writeSealed($leasePath, $lease);

        return [$leaseId, $authorityId, $leasePath, $requestPath];
    }

    private function readUnsealed(string $path): array
    {
        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        unset($record['record_digest']);

        return $record;
    }

    private function sealed(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    private function write(string $path, array $record): void
    {
        $this->writeSealed($path, $this->sealed($record));
    }

    private function writeSealed(string $path, array $record): void
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
