<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Conscription;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;

final class DelegateMissionModelBindingAuthorityTransition
{
    public static function run(string $resultDirectory, string $authorityId, \Closure $transition): array
    {
        $root = dirname($resultDirectory, 5);

        return (new AtomicTransition($root))->run(
            'delegate-model-binding-authority:'.hash('sha256', $authorityId),
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
        $authority = $record['binding_authority'] ?? null;
        $source = $record['source_selection_decision'] ?? null;
        $holder = $record['binder'] ?? null;
        if ('imperium.conscription-delegate-mission-model-binding/v1' !== ($record['schema'] ?? null)
            || $resultId !== ($record['binding_id'] ?? null)
            || !preg_match('/^delegate-mission-model-binding-[a-f0-9]{20}$/', $resultId)
            || DelegateMissionModelBindingSealingService::class !== $consumer
            || !is_array($authority)
            || true !== ($authority['consumed'] ?? null)
            || false !== ($authority['continuing_authority'] ?? null)
            || !is_string($authority['id'] ?? null)
            || '' === trim($authority['id'])
            || !is_array($source)
            || !is_string($source['id'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $source['digest'] ?? '')
            || !is_array($holder)
            || !is_string($record['instance_id'] ?? null)
            || !is_string($record['sealed_at'] ?? null)) {
            throw new \InvalidArgumentException('R399_DELEGATE_MODEL_BINDING_TRANSACTION_INVALID');
        }
        $authorityId = $authority['id'];
        $authoritativeInputs = ['lifecycle_result' => $record];
        $scope = 'delegate-model-binding-authority:'.hash('sha256', $authorityId);

        $record['transactional_consumption'] = TransactionalAuthorityConsumptionEnvelope::complete(
            'delegate-model-binding-consumption-'.substr(hash('sha256', $authorityId.'|'.$resultId), 0, 20),
            $record['instance_id'],
            [[
                'authority_id' => $authorityId,
                'authority_schema' => (string) $record['schema'],
                'source' => ['id' => $source['id'], 'digest' => $source['digest']],
                'issuer' => ['kind' => 'source-record', 'id' => $source['id']],
                'holder' => ['role' => 'binder', 'identity' => $holder],
                'scope' => [
                    'lifecycle_result_schema' => $record['schema'],
                    'authority_field' => 'binding_authority',
                ],
                'expires_at' => 'NO_EXPIRY_DECLARED',
                'single_use' => true,
                'expected_unconsumed' => true,
            ]],
            $authoritativeInputs,
            ReplayFingerprint::of($authoritativeInputs),
            [
                'actor' => ['role' => 'binder', 'identity' => $holder],
                'competent_service' => $consumer,
                'bounded_act' => 'CONSUME_ONE_DELEGATE_MODEL_BINDING_AUTHORITY',
            ],
            [['order' => 1, 'scope' => $scope, 'authority_id' => $authorityId]],
            ['schema' => (string) $record['schema'], 'id' => $resultId, 'embedded' => true],
            new \DateTimeImmutable($record['sealed_at']),
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
            $expected = self::seal((string) ($record['binding_id'] ?? ''), $record, DelegateMissionModelBindingSealingService::class)['transactional_consumption'];
        } catch (\Throwable) {
            return false;
        }

        return $actual === $expected;
    }

    private function __construct()
    {
    }
}
