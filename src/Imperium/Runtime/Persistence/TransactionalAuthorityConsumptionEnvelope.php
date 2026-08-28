<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;

final class TransactionalAuthorityConsumptionEnvelope
{
    public static function complete(
        string $transactionId,
        string $instanceId,
        array $authoritySet,
        array $authoritativeInputs,
        string $replayFingerprint,
        array $consumer,
        array $lockPlan,
        array $immutableResult,
        \DateTimeImmutable $committedAt,
    ): array {
        self::validate(
            $transactionId,
            $instanceId,
            $authoritySet,
            $authoritativeInputs,
            $replayFingerprint,
            $consumer,
            $lockPlan,
            $immutableResult,
        );

        $record = [
            'schema' => TransactionalAuthorityConsumptionContract::SCHEMA,
            'transaction_id' => $transactionId,
            'instance_id' => $instanceId,
            'authority_set' => $authoritySet,
            'authoritative_inputs' => $authoritativeInputs,
            'replay_fingerprint' => $replayFingerprint,
            'consumer' => $consumer,
            'lock_plan' => $lockPlan,
            'consumption_result' => [
                'state' => 'COMMITTED',
                'authority_consumptions' => array_map(
                    static fn (array $authority): array => [
                        'authority_id' => $authority['authority_id'],
                        'consumed' => true,
                        'consumed_at' => $committedAt->format(DATE_ATOM),
                        'continuing_authority' => false,
                    ],
                    $authoritySet,
                ),
                'immutable_result' => $immutableResult,
                'continuing_authority' => false,
            ],
            'recovery' => [
                'schema' => AuthorityConsumptionRecoveryContract::SCHEMA,
                'checkpoint' => 'COMPLETE',
                'outcome' => 'COMPLETE',
                'retry' => [
                    'automatic_retry_permitted' => false,
                    'same_replay_fingerprint_required' => true,
                    'provider_reinvocation_permitted' => false,
                ],
                'rollback' => [
                    'automatic_rollback_permitted' => false,
                    'authority_unconsume_permitted' => false,
                ],
                'external_effect' => [
                    'started' => false,
                    'outcome_known' => true,
                    'response_identity' => null,
                ],
            ],
            'created_at' => $committedAt->format(DATE_ATOM),
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }

    public static function requireExact(array $record, array $expected, string $error): void
    {
        if (CanonicalJson::encode($record) !== CanonicalJson::encode($expected)) {
            throw new \RuntimeException($error);
        }
    }

    private static function validate(
        string $transactionId,
        string $instanceId,
        array $authoritySet,
        array $authoritativeInputs,
        string $replayFingerprint,
        array $consumer,
        array $lockPlan,
        array $immutableResult,
    ): void {
        if ('' === trim($transactionId)
            || '' === trim($instanceId)
            || !preg_match('/^[a-f0-9]{64}$/', $replayFingerprint)
            || !hash_equals($replayFingerprint, ReplayFingerprint::of($authoritativeInputs))
            || [] === $authoritySet
            || !array_is_list($authoritySet)
            || count($authoritySet) !== count($lockPlan)
            || !array_is_list($lockPlan)
            || array_keys($consumer) !== TransactionalAuthorityConsumptionContract::REQUIRED_CONSUMER_FIELDS
            || !self::text($consumer['competent_service'])
            || !self::text($consumer['bounded_act'])
            || array_keys($immutableResult) !== ['schema', 'id', 'embedded']) {
            throw new \InvalidArgumentException('PST140_TRANSACTIONAL_AUTHORITY_CONSUMPTION_INVALID');
        }

        $authorityIds = [];
        foreach ($authoritySet as $index => $authority) {
            if (array_keys($authority) !== TransactionalAuthorityConsumptionContract::REQUIRED_AUTHORITY_FIELDS
                || !self::text($authority['authority_id'] ?? null)
                || !self::text($authority['authority_schema'] ?? null)
                || array_keys($authority['source'] ?? []) !== TransactionalAuthorityConsumptionContract::REQUIRED_SOURCE_FIELDS
                || !self::text($authority['source']['id'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/', $authority['source']['digest'] ?? '')
                || !is_array($authority['issuer'] ?? null)
                || !is_array($authority['holder'] ?? null)
                || !is_array($authority['scope'] ?? null)
                || !self::text($authority['expires_at'] ?? null)
                || true !== ($authority['single_use'] ?? null)
                || true !== ($authority['expected_unconsumed'] ?? null)) {
                throw new \InvalidArgumentException('PST140_TRANSACTIONAL_AUTHORITY_CONSUMPTION_INVALID');
            }
            $lock = $lockPlan[$index] ?? [];
            if (array_keys($lock) !== TransactionalAuthorityConsumptionContract::REQUIRED_LOCK_FIELDS
                || $index + 1 !== ($lock['order'] ?? null)
                || !self::text($lock['scope'] ?? null)
                || $authority['authority_id'] !== ($lock['authority_id'] ?? null)) {
                throw new \InvalidArgumentException('PST140_TRANSACTIONAL_AUTHORITY_CONSUMPTION_INVALID');
            }
            $authorityIds[] = $authority['authority_id'];
        }
        if (count($authorityIds) !== count(array_unique($authorityIds))) {
            throw new \InvalidArgumentException('PST140_TRANSACTIONAL_AUTHORITY_CONSUMPTION_INVALID');
        }
    }

    private static function text(mixed $value): bool
    {
        return is_string($value) && '' !== trim($value);
    }

    private function __construct()
    {
    }
}
