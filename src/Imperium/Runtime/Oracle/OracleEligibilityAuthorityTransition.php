<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\ReplayFingerprint;
use App\Imperium\Runtime\Persistence\TransactionalAuthorityConsumptionEnvelope;

final readonly class OracleEligibilityAuthorityTransition
{
    private const string DIRECTORY = 'var/imperium/offices/oracle/model-eligibility-authority-transitions';

    public function __construct(
        private string $root,
        private AtomicTransition $atomic,
        private ImmutableRecordStore $records,
    ) {
    }

    public function run(string $caseId, \Closure $transition): array
    {
        return $this->atomic->run(
            'oracle-eligibility-case:'.hash('sha256', $caseId),
            $transition,
        );
    }

    public function complete(array $case, array $authority, array $finding): array
    {
        $authorityId = (string) ($authority['authority_id'] ?? '');
        $id = 'oracle-eligibility-authority-transition-'.substr(hash('sha256', $authorityId), 0, 20);
        $record = [
            'schema' => 'imperium.oracle-eligibility-authority-transition/v1',
            'transition_id' => $id,
            'instance_id' => $case['instance_id'],
            'case' => ['id' => $case['case_id'], 'digest' => $case['record_digest']],
            'authority' => [
                'id' => $authorityId,
                'model_ref' => $authority['model_ref'],
                'criteria_digest' => $authority['criteria_digest'],
                'catalogue_snapshot_digest' => $authority['catalogue_snapshot_digest'],
            ],
            'finding' => ['id' => $finding['finding_id'], 'digest' => $finding['record_digest']],
            'actor' => $finding['actor'],
            'issued_at' => $finding['issued_at'],
            'checkpoint' => 'COMPLETE',
            'external_effect' => false,
            'sealed' => true,
        ];
        $record['transactional_consumption'] = $this->envelope($record, $case, $authority, $finding);

        return $this->records->put(self::DIRECTORY, $id, $record);
    }

    public function isExact(array $record, array $case, array $authority, array $finding): bool
    {
        $actual = $record['transactional_consumption'] ?? null;
        unset($record['record_digest'], $record['transactional_consumption']);
        if (!is_array($actual)) {
            return false;
        }

        try {
            return $actual === $this->envelope($record, $case, $authority, $finding);
        } catch (\Throwable) {
            return false;
        }
    }

    private function envelope(array $record, array $case, array $authority, array $finding): array
    {
        $authorityId = (string) ($authority['authority_id'] ?? '');
        $transitionId = 'oracle-eligibility-authority-transition-'.substr(hash('sha256', $authorityId), 0, 20);
        if ('imperium.oracle-eligibility-authority-transition/v1' !== ($record['schema'] ?? null)
            || $transitionId !== ($record['transition_id'] ?? null)
            || !is_string($case['instance_id'] ?? null)
            || '' === trim($case['instance_id'])
            || $case['instance_id'] !== ($record['instance_id'] ?? null)
            || !is_string($case['record_digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $case['record_digest'])
            || ['id' => $case['case_id'], 'digest' => $case['record_digest']] !== ($record['case'] ?? null)
            || !is_string($authority['model_ref'] ?? null)
            || !is_string($authority['criteria_digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $authority['criteria_digest'])
            || !is_string($authority['catalogue_snapshot_digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $authority['catalogue_snapshot_digest'])
            || true !== ($authority['eligibility_finding_authority'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || [
                'id' => $authorityId,
                'model_ref' => $authority['model_ref'],
                'criteria_digest' => $authority['criteria_digest'],
                'catalogue_snapshot_digest' => $authority['catalogue_snapshot_digest'],
            ] !== ($record['authority'] ?? null)
            || $authorityId !== ($finding['authority']['id'] ?? null)
            || $case['case_id'] !== ($finding['case']['id'] ?? null)
            || $case['record_digest'] !== ($finding['case']['digest'] ?? null)
            || !is_string($finding['record_digest'] ?? null)
            || !preg_match('/^[a-f0-9]{64}$/', $finding['record_digest'])
            || ['id' => $finding['finding_id'], 'digest' => $finding['record_digest']] !== ($record['finding'] ?? null)
            || ($finding['actor'] ?? null) !== ($record['actor'] ?? null)
            || !is_string($finding['issued_at'] ?? null)
            || $finding['issued_at'] !== ($record['issued_at'] ?? null)
            || 'COMPLETE' !== ($record['checkpoint'] ?? null)
            || false !== ($record['external_effect'] ?? null)
            || true !== ($record['sealed'] ?? null)) {
            throw new \InvalidArgumentException('OR59_ELIGIBILITY_TRANSACTION_INVALID');
        }
        $authoritativeInputs = ['transition_result' => $record];

        return TransactionalAuthorityConsumptionEnvelope::complete(
            'oracle-eligibility-consumption-'.substr(hash('sha256', $authorityId), 0, 20),
            $case['instance_id'],
            [[
                'authority_id' => $authorityId,
                'authority_schema' => 'imperium.oracle-model-evaluation-case/v1',
                'source' => ['id' => $case['case_id'], 'digest' => $case['record_digest']],
                'issuer' => ['kind' => 'source-record', 'id' => $case['case_id']],
                'holder' => ['role' => 'oracle.augur', 'identity' => $finding['actor']],
                'scope' => [
                    'model_ref' => $authority['model_ref'],
                    'criteria_digest' => $authority['criteria_digest'],
                    'catalogue_snapshot_digest' => $authority['catalogue_snapshot_digest'],
                ],
                'expires_at' => 'NO_EXPIRY_DECLARED',
                'single_use' => true,
                'expected_unconsumed' => true,
            ]],
            $authoritativeInputs,
            ReplayFingerprint::of($authoritativeInputs),
            [
                'actor' => ['role' => 'oracle.augur', 'identity' => $finding['actor']],
                'competent_service' => ModelEligibilityFindingService::class,
                'bounded_act' => 'CONSUME_ONE_ORACLE_MODEL_ELIGIBILITY_FINDING_AUTHORITY',
            ],
            [['order' => 1, 'scope' => 'oracle-eligibility-case:'.hash('sha256', $case['case_id']), 'authority_id' => $authorityId]],
            ['schema' => $finding['schema'], 'id' => $finding['finding_id'], 'embedded' => false],
            new \DateTimeImmutable($finding['issued_at']),
        );
    }
}
