<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TransactionalAuthorityConsumptionEnvelopeTest extends TestCase
{
    public function testCompleteEnvelopeSealsOrderedMultiAuthorityConsumptionAndPreIoRecovery(): void
    {
        [$authorities, $inputs, $consumer, $locks, $result] = $this->fixtures();
        $fingerprint = ReplayFingerprint::of($inputs);
        $at = new \DateTimeImmutable('2026-08-28T10:00:00+00:00');
        $record = TransactionalAuthorityConsumptionEnvelope::complete('transaction-test', 'imperium-test', $authorities, $inputs, $fingerprint, $consumer, $locks, $result, $at);

        self::assertSame($fingerprint, $record['replay_fingerprint']);
        self::assertSame(['authority-a', 'authority-b'], array_column($record['authority_set'], 'authority_id'));
        self::assertSame([1, 2], array_column($record['lock_plan'], 'order'));
        self::assertSame(['authority-a', 'authority-b'], array_column($record['consumption_result']['authority_consumptions'], 'authority_id'));
        self::assertSame('COMPLETE', $record['recovery']['checkpoint']);
        self::assertFalse($record['recovery']['external_effect']['started']);
        self::assertFalse($record['recovery']['retry']['automatic_retry_permitted']);
        self::assertFalse($record['recovery']['retry']['provider_reinvocation_permitted']);
        self::assertFalse($record['recovery']['rollback']['authority_unconsume_permitted']);
        $digest = $record['record_digest'];
        unset($record['record_digest']);
        self::assertSame($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    #[DataProvider('invalidCases')]
    public function testIncompleteFingerprintReorderedLockAndDuplicatedAuthorityFailStopped(string $case): void
    {
        [$authorities, $inputs, $consumer, $locks, $result] = $this->fixtures();
        $fingerprint = ReplayFingerprint::of($inputs);
        if ('fingerprint' === $case) {
            $fingerprint = str_repeat('0', 64);
        } elseif ('lock-order' === $case) {
            $locks[0]['order'] = 2;
        } else {
            $authorities[1]['authority_id'] = 'authority-a';
            $locks[1]['authority_id'] = 'authority-a';
        }

        $this->expectExceptionMessage('PST140_TRANSACTIONAL_AUTHORITY_CONSUMPTION_INVALID');
        TransactionalAuthorityConsumptionEnvelope::complete('transaction-test', 'imperium-test', $authorities, $inputs, $fingerprint, $consumer, $locks, $result, new \DateTimeImmutable('2026-08-28T10:00:00+00:00'));
    }

    public static function invalidCases(): array
    {
        return [['fingerprint'], ['lock-order'], ['duplicate-authority']];
    }

    private function fixtures(): array
    {
        $sourceA = ['id' => 'source-a', 'digest' => str_repeat('a', 64)];
        $sourceB = ['id' => 'source-b', 'digest' => str_repeat('b', 64)];
        $authorities = [
            ['authority_id' => 'authority-a', 'authority_schema' => 'authority/a/v1', 'source' => $sourceA, 'issuer' => ['id' => 'issuer-a'], 'holder' => ['id' => 'holder-a'], 'scope' => ['act' => 'a'], 'expires_at' => '2026-08-28T10:05:00+00:00', 'single_use' => true, 'expected_unconsumed' => true],
            ['authority_id' => 'authority-b', 'authority_schema' => 'authority/b/v1', 'source' => $sourceB, 'issuer' => ['id' => 'issuer-b'], 'holder' => ['id' => 'holder-b'], 'scope' => ['act' => 'b'], 'expires_at' => '2026-08-28T10:05:00+00:00', 'single_use' => true, 'expected_unconsumed' => true],
        ];
        $inputs = ['authority_a' => $sourceA, 'authority_b' => $sourceB, 'target' => ['id' => 'target']];
        $consumer = ['actor' => ['kind' => 'runtime-service', 'id' => 'test'], 'competent_service' => 'test.service', 'bounded_act' => 'TEST_ONE_ACT'];
        $locks = [
            ['order' => 1, 'scope' => 'test:authority-a', 'authority_id' => 'authority-a'],
            ['order' => 2, 'scope' => 'test:authority-b', 'authority_id' => 'authority-b'],
        ];
        $result = ['schema' => 'test.result/v1', 'id' => 'result-test', 'embedded' => true];

        return [$authorities, $inputs, $consumer, $locks, $result];
    }
}
