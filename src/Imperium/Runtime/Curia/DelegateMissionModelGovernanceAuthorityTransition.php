<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;

final class DelegateMissionModelGovernanceAuthorityTransition
{
    public static function run(string $resultDirectory, string $authorityId, \Closure $transition): array
    {
        $root = dirname($resultDirectory, 5);

        return (new AtomicTransition($root))->run(
            'delegate-model-governance-authority:'.hash('sha256', $authorityId),
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
        $store = new ImmutableRecordStore($root, new AtomicTransition($root));
        if (is_file($resultDirectory.'/'.$resultId.'.json')) {
            try {
                $existing = $store->read($relativeDirectory, $resultId);
                $historical = $record;
                unset($historical['record_digest']);
                $historical['record_digest'] = hash('sha256', CanonicalJson::encode($historical));
                if (!array_key_exists('transactional_consumption', $existing)
                    && CanonicalJson::encode($existing) === CanonicalJson::encode($historical)) {
                    return $existing;
                }
            } catch (\RuntimeException) {
                throw new \RuntimeException($conflictError);
            }
        }
        unset($record['record_digest']);
        $record = self::seal($resultId, $record, $consumer);

        try {
            return $store->put($relativeDirectory, $resultId, $record);
        } catch (\RuntimeException) {
            throw new \RuntimeException(is_file($resultDirectory.'/'.$resultId.'.json') ? $conflictError : $persistenceError);
        }
    }

    public static function seal(string $resultId, array $record, string $consumer): array
    {
        [$authorityKey, $authority] = self::consumption($record);
        $source = self::source($record);
        $holder = self::holder($record);
        $authorityId = (string) $authority['id'];
        $authoritativeInputs = ['lifecycle_result' => $record];
        $scope = 'delegate-model-governance-authority:'.hash('sha256', $authorityId);

        $record['transactional_consumption'] = TransactionalAuthorityConsumptionEnvelope::complete(
            'delegate-model-governance-consumption-'.substr(hash('sha256', $authorityId.'|'.$resultId), 0, 20),
            (string) $record['instance_id'],
            [[
                'authority_id' => $authorityId,
                'authority_schema' => (string) $record['schema'],
                'source' => $source,
                'issuer' => ['kind' => 'source-record', 'id' => $source['id']],
                'holder' => $holder,
                'scope' => [
                    'lifecycle_result_schema' => $record['schema'],
                    'authority_field' => $authorityKey,
                ],
                'expires_at' => 'NO_EXPIRY_DECLARED',
                'single_use' => true,
                'expected_unconsumed' => true,
            ]],
            $authoritativeInputs,
            ReplayFingerprint::of($authoritativeInputs),
            [
                'actor' => $holder,
                'competent_service' => $consumer,
                'bounded_act' => 'CONSUME_ONE_DELEGATE_MODEL_GOVERNANCE_AUTHORITY',
            ],
            [['order' => 1, 'scope' => $scope, 'authority_id' => $authorityId]],
            ['schema' => (string) $record['schema'], 'id' => $resultId, 'embedded' => true],
            self::committedAt($record),
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
            $expected = self::seal(self::resultId($record), $record, self::consumerFor($record))['transactional_consumption'];
        } catch (\Throwable) {
            return false;
        }

        return $actual === $expected;
    }

    private static function consumption(array $record): array
    {
        foreach (['criteria_proposal_authority', 'selection_authority'] as $key) {
            $authority = $record[$key] ?? null;
            if (is_array($authority)
                && true === ($authority['consumed'] ?? null)
                && false === ($authority['continuing_authority'] ?? null)
                && is_string($authority['id'] ?? null)
                && '' !== trim($authority['id'])) {
                return [$key, $authority];
            }
        }

        throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID');
    }

    private static function source(array $record): array
    {
        foreach (['source_readiness', 'source_recommendation'] as $key) {
            $source = $record[$key] ?? null;
            if (is_array($source)
                && is_string($source['id'] ?? null)
                && preg_match('/^[a-f0-9]{64}$/', $source['digest'] ?? '')) {
                return ['id' => $source['id'], 'digest' => $source['digest']];
            }
        }

        throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID');
    }

    private static function holder(array $record): array
    {
        foreach (['requester', 'decision_maker'] as $key) {
            if (is_array($record[$key] ?? null)) {
                return ['role' => $key, 'identity' => $record[$key]];
            }
        }

        throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID');
    }

    private static function committedAt(array $record): \DateTimeImmutable
    {
        foreach (['presented_at', 'decided_at'] as $key) {
            if (is_string($record[$key] ?? null)) {
                return new \DateTimeImmutable($record[$key]);
            }
        }

        throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID');
    }

    private static function resultId(array $record): string
    {
        foreach (['request_id', 'decision_id'] as $key) {
            if (is_string($record[$key] ?? null) && '' !== trim($record[$key])) {
                return $record[$key];
            }
        }

        throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID');
    }

    private static function consumerFor(array $record): string
    {
        return match ($record['schema'] ?? null) {
            'imperium.curia-delegate-mission-model-criteria-request/v1' => DelegateMissionModelCriteriaRequestService::class,
            'imperium.curia-delegate-mission-model-selection-decision/v1' => DelegateMissionModelSelectionDecisionService::class,
            default => throw new \InvalidArgumentException('C399_DELEGATE_MODEL_GOVERNANCE_TRANSACTION_INVALID'),
        };
    }

    private function __construct()
    {
    }
}
