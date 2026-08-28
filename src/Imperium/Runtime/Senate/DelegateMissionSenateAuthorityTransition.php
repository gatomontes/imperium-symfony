<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;

final class DelegateMissionSenateAuthorityTransition
{
    public static function run(string $resultDirectory, string $authorityIdentity, \Closure $transition): array
    {
        $root = dirname($resultDirectory, 5);

        return (new AtomicTransition($root))->run(
            'delegate-senate-authority:'.hash('sha256', $authorityIdentity),
            $transition,
        );
    }

    public static function put(
        string $resultDirectory,
        string $resultId,
        array $record,
        string $consumer,
        string $persistenceError,
        string $conflictError,
    ): array {
        $root = dirname($resultDirectory, 5);
        $relativeDirectory = ltrim(substr($resultDirectory, strlen($root)), '/');
        $record = self::seal($resultId, $record, $consumer);

        try {
            return (new ImmutableRecordStore($root, new AtomicTransition($root)))->put($relativeDirectory, $resultId, $record);
        } catch (\RuntimeException) {
            throw new \RuntimeException(is_file($resultDirectory.'/'.$resultId.'.json') ? $conflictError : $persistenceError);
        }
    }

    public static function seal(string $resultId, array $record, string $consumer): array
    {
        [$authorityKey, $authority] = self::consumption($record);
        if (null === $authorityKey) {
            return $record;
        }
        $source = self::source($record);
        $holder = self::holder($record);
        $committedAt = self::committedAt($record);
        $authorityId = (string) $authority['id'];
        $authoritativeInputs = ['lifecycle_result' => $record];
        $fingerprint = ReplayFingerprint::of($authoritativeInputs);
        $authoritySet = [[
            'authority_id' => $authorityId,
            'authority_schema' => (string) $record['schema'],
            'source' => $source,
            'issuer' => ['kind' => 'source-record', 'id' => $source['id']],
            'holder' => $holder,
            'scope' => array_filter([
                'lifecycle_result_schema' => $record['schema'],
                'jurisdiction' => $record['jurisdiction'] ?? null,
                'authority_field' => $authorityKey,
            ], static fn (mixed $value): bool => null !== $value),
            'expires_at' => 'NO_EXPIRY_DECLARED',
            'single_use' => true,
            'expected_unconsumed' => true,
        ]];
        $consumerRecord = [
            'actor' => $holder,
            'competent_service' => $consumer,
            'bounded_act' => 'CONSUME_ONE_DELEGATE_SENATE_LIFECYCLE_AUTHORITY',
        ];
        $scope = 'delegate-senate-authority:'.hash('sha256', $authorityId);
        $transactionId = 'delegate-senate-consumption-'.substr(hash('sha256', $authorityId.'|'.$resultId), 0, 20);

        $record['transactional_consumption'] = TransactionalAuthorityConsumptionEnvelope::complete(
            $transactionId,
            (string) $record['instance_id'],
            $authoritySet,
            $authoritativeInputs,
            $fingerprint,
            $consumerRecord,
            [['order' => 1, 'scope' => $scope, 'authority_id' => $authorityId]],
            ['schema' => (string) $record['schema'], 'id' => $resultId, 'embedded' => true],
            $committedAt,
        );

        return $record;
    }

    public static function isExactOrHistorical(array $record): bool
    {
        if (!array_key_exists('transactional_consumption', $record)) {
            return true;
        }
        if (!is_array($record['transactional_consumption'])) {
            return false;
        }
        $actual = $record['transactional_consumption'];
        unset($record['record_digest'], $record['transactional_consumption']);

        try {
            $expectedConsumer = self::consumerFor($record);
            $expected = self::seal((string) self::resultId($record), $record, $expectedConsumer)['transactional_consumption'] ?? null;
        } catch (\Throwable) {
            return false;
        }

        return is_array($expected) && $actual === $expected;
    }

    private static function consumption(array $record): array
    {
        foreach ($record as $key => $value) {
            if (is_string($key)
                && str_ends_with($key, 'authority')
                && is_array($value)
                && true === ($value['consumed'] ?? null)
                && false === ($value['continuing_authority'] ?? null)
                && is_string($value['id'] ?? null)
                && '' !== trim($value['id'])) {
                return [$key, $value];
            }
        }

        return [null, null];
    }

    private static function source(array $record): array
    {
        foreach ($record as $key => $value) {
            if (is_string($key)
                && str_starts_with($key, 'source_')
                && is_array($value)
                && is_string($value['id'] ?? null)
                && preg_match('/^[a-f0-9]{64}$/', $value['digest'] ?? '')) {
                return ['id' => $value['id'], 'digest' => $value['digest']];
            }
        }

        throw new \InvalidArgumentException('S790_DELEGATE_MISSION_SENATE_TRANSACTION_INVALID');
    }

    private static function holder(array $record): array
    {
        foreach (['lord_speaker', 'trust_senator', 'security_senator', 'usability_senator', 'senator', 'bailiff', 'issuer', 'manifestation'] as $key) {
            if (is_array($record[$key] ?? null)) {
                return ['role' => $key, 'identity' => $record[$key]];
            }
        }

        throw new \InvalidArgumentException('S790_DELEGATE_MISSION_SENATE_TRANSACTION_INVALID');
    }

    private static function committedAt(array $record): \DateTimeImmutable
    {
        foreach ($record as $key => $value) {
            if (is_string($key) && str_ends_with($key, '_at') && is_string($value)) {
                return new \DateTimeImmutable($value);
            }
        }

        throw new \InvalidArgumentException('S790_DELEGATE_MISSION_SENATE_TRANSACTION_INVALID');
    }

    private static function resultId(array $record): string
    {
        foreach (['disposition_id', 'question_id', 'decision_id', 'dispatch_id', 'turn_id', 'commission_id', 'opening_id', 'finding_id', 'deliberation_id', 'reconciliation_id'] as $key) {
            if (is_string($record[$key] ?? null) && '' !== trim($record[$key])) {
                return $record[$key];
            }
        }

        throw new \InvalidArgumentException('S790_DELEGATE_MISSION_SENATE_TRANSACTION_INVALID');
    }

    private static function consumerFor(array $record): string
    {
        return match ($record['schema'] ?? null) {
            'imperium.senate-delegate-mission-profile-examination-question-commission-disposition/v1' => 'trust' === ($record['jurisdiction'] ?? null)
                ? DelegateMissionFirstQuestionCommissionDispositionService::class
                : DelegateMissionSubsequentQuestionCommissionDispositionEngine::class,
            'imperium.senate-delegate-mission-profile-examination-question-dispatch-decision/v1' => DelegateMissionQuestionDispatchAuthorizationEngine::class,
            'imperium.senate-delegate-mission-profile-examination-question-dispatch/v1' => DelegateMissionQuestionDispatchEngine::class,
            'imperium.senate-delegate-mission-profile-examination-question-commission/v1' => DelegateMissionSubsequentQuestionCommissionIssuanceEngine::class,
            'imperium.senate-delegate-mission-profile-examination-finding-authority-opening/v1' => DelegateMissionFindingAuthorityOpeningService::class,
            'imperium.senate-delegate-mission-profile-examination-deliberation-opening/v1' => DelegateMissionDeliberationOpeningService::class,
            'imperium.senate-delegate-mission-profile-examination-disposition-authority-opening/v1' => DelegateMissionDispositionAuthorityOpeningService::class,
            default => throw new \InvalidArgumentException('S790_DELEGATE_MISSION_SENATE_TRANSACTION_INVALID'),
        };
    }

    private function __construct()
    {
    }
}
