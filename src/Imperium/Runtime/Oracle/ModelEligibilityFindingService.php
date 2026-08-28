<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Oracle;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;

final readonly class ModelEligibilityFindingService
{
    private string $cases;
    private string $occupancy;
    private string $findings;
    private string $phases;
    private ImmutableRecordStore $records;
    private OracleEligibilityAuthorityTransition $transition;

    public function __construct(
        private string $root,
        private ?OracleEligibilityTransitionFaultInjector $faults = null,
    ) {
        $this->cases = $root.'/var/imperium/offices/oracle/model-evaluation-cases';
        $this->occupancy = $root.'/var/imperium/offices/oracle/occupancy';
        $this->findings = $root.'/var/imperium/offices/oracle/model-eligibility-findings';
        $this->phases = $root.'/var/imperium/offices/oracle/model-eligibility-phases';
        $atomic = new AtomicTransition($root);
        $this->records = new ImmutableRecordStore($root, $atomic);
        $this->transition = new OracleEligibilityAuthorityTransition($root, $atomic, $this->records);
    }

    public function issue(
        string $caseId,
        string $authorityId,
        string $augurBindingId,
        string $disposition,
        array $criterionFindings,
        array $sourceIds,
        array $claimIds,
        array $reasonCodes,
        \DateTimeImmutable $issuedAt,
    ): array {
        return $this->transition->run(
            $caseId,
            fn (): array => $this->issueLocked(
                $caseId,
                $authorityId,
                $augurBindingId,
                $disposition,
                $criterionFindings,
                $sourceIds,
                $claimIds,
                $reasonCodes,
                $issuedAt,
            ),
        );
    }

    private function issueLocked(
        string $caseId,
        string $authorityId,
        string $augurBindingId,
        string $disposition,
        array $criterionFindings,
        array $sourceIds,
        array $claimIds,
        array $reasonCodes,
        \DateTimeImmutable $issuedAt,
    ): array {
        $case = $this->read($this->cases.'/'.$caseId.'.json', 'OR50_MODEL_EVALUATION_CASE_ABSENT');
        $authority = $this->authority($case, $authorityId);
        $existing = $this->existing($authorityId);
        if (null !== $existing) {
            if (!$this->existingMatches($existing, $case, $authorityId, $augurBindingId)) {
                throw new \RuntimeException('OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
            }
            $this->closeIfComplete($case);
            $this->faults?->at('PHASE_RECONCILED');
            $this->transition->complete($case, $authority, $existing);
            $this->faults?->at('TRANSACTION_COMMITTED');

            return $existing;
        }

        $occupancy = $this->read($this->occupancy.'/'.$augurBindingId.'.json', 'OR51_AUGUR_OCCUPANCY_ABSENT');
        $modelRef = $authority['model_ref'] ?? '';
        if (!$this->ok($case)
            || 'imperium.oracle-model-evaluation-case/v1' !== ($case['schema'] ?? null)
            || $caseId !== ($case['case_id'] ?? null)
            || 'ORACLE_MODEL_EVALUATION_CASE_OPENED_PENDING_AUGUR_ELIGIBILITY_FINDINGS' !== ($case['status'] ?? null)
            || true !== ($case['candidate_universe_frozen'] ?? null)
            || !$this->ok($occupancy)
            || 'imperium.oracle-augur-occupancy/v1' !== ($occupancy['schema'] ?? null)
            || $augurBindingId !== ($occupancy['binding_id'] ?? null)
            || ($case['instance_id'] ?? null) !== ($occupancy['instance_id'] ?? null)
            || ($case['actor']['binding_id'] ?? null) !== $augurBindingId
            || 'ORACLE_AUGUR_BOUND_ACTIVE_NO_MODEL_SELECTION_AUTHORITY' !== ($occupancy['status'] ?? null)
            || true !== ($authority['eligibility_finding_authority'] ?? null)
            || true !== ($authority['authority_single_use'] ?? null)
            || !in_array($disposition, ['ELIGIBLE', 'INELIGIBLE', 'INDETERMINATE'], true)
            || array_keys($criterionFindings) !== ($case['criteria']['evaluation_rubric'] ?? [])
            || !$this->criteriaValid($criterionFindings, $disposition)
            || !$this->subset($sourceIds, $authority['source_ids'] ?? [])
            || !$this->subset($claimIds, $authority['claim_ids'] ?? [])
            || ([] === $sourceIds && [] === $claimIds)
            || ('ELIGIBLE' !== $disposition && [] === $reasonCodes)
            || !$this->strings($reasonCodes, true)) {
            throw new \RuntimeException('OR52_MODEL_ELIGIBILITY_FINDING_INVALID');
        }

        $actor = [
            'office' => 'oracle',
            'seat' => 'oracle.augur',
            'binding_id' => $augurBindingId,
            'manifestation_id' => $occupancy['manifestation_id'],
            'occupancy_generation' => $occupancy['occupancy_generation'],
        ];
        $id = 'oracle-model-eligibility-finding-'.substr(hash('sha256', CanonicalJson::encode([
            $caseId,
            $case['record_digest'],
            $authorityId,
            $modelRef,
            $disposition,
            $criterionFindings,
            $sourceIds,
            $claimIds,
            $reasonCodes,
            $actor,
        ])), 0, 20);
        $finding = $this->records->put(
            'var/imperium/offices/oracle/model-eligibility-findings',
            $id,
            [
                'schema' => 'imperium.oracle-model-eligibility-finding/v1',
                'finding_id' => $id,
                'case' => ['id' => $caseId, 'digest' => $case['record_digest']],
                'authority' => [
                    'id' => $authorityId,
                    'criteria_digest' => $authority['criteria_digest'],
                    'catalogue_snapshot_digest' => $authority['catalogue_snapshot_digest'],
                ],
                'model_ref' => $modelRef,
                'actor' => $actor,
                'disposition' => $disposition,
                'criterion_findings' => $criterionFindings,
                'evidence' => ['source_ids' => $sourceIds, 'claim_ids' => $claimIds],
                'reason_codes' => $reasonCodes,
                'issued_at' => $issuedAt->format(DATE_ATOM),
                'eligibility_finding_authority_consumed' => true,
                'continuing_finding_authority' => false,
                'ranking_authority' => false,
                'recommendation_authority' => false,
                'selection_authority' => false,
                'model_assignment_authority' => false,
                'profile_mutation_authority' => false,
                'provider_invocation_authority' => false,
                'deployment_authority' => false,
                'execution_authority' => false,
                'status' => 'ORACLE_MODEL_ELIGIBILITY_FINDING_SEALED_NO_SELECTION_AUTHORITY',
                'sealed' => true,
            ],
        );
        $this->faults?->at('FINDING_COMMITTED');
        $this->closeIfComplete($case);
        $this->faults?->at('PHASE_RECONCILED');
        $this->transition->complete($case, $authority, $finding);
        $this->faults?->at('TRANSACTION_COMMITTED');

        return $finding;
    }

    private function closeIfComplete(array $case): ?array
    {
        $all = [];
        $latest = null;
        foreach ($case['eligibility_authorities'] as $authority) {
            $finding = $this->existing($authority['authority_id']);
            if (null === $finding) {
                return null;
            }
            if (!$this->ok($finding)
                || ($finding['case']['id'] ?? null) !== ($case['case_id'] ?? null)
                || ($finding['case']['digest'] ?? null) !== ($case['record_digest'] ?? null)
                || ($finding['authority']['id'] ?? null) !== ($authority['authority_id'] ?? null)
                || !is_string($finding['issued_at'] ?? null)) {
                throw new \RuntimeException('OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
            }
            $issuedAt = new \DateTimeImmutable($finding['issued_at']);
            if (null === $latest || $issuedAt > $latest) {
                $latest = $issuedAt;
            }
            $all[$finding['model_ref']] = [
                'finding_id' => $finding['finding_id'],
                'finding_digest' => $finding['record_digest'],
                'disposition' => $finding['disposition'],
                'reason_codes' => $finding['reason_codes'],
            ];
        }
        ksort($all, SORT_STRING);
        $eligible = array_keys(array_filter($all, static fn (array $finding): bool => 'ELIGIBLE' === $finding['disposition']));
        $id = 'oracle-model-eligibility-phase-'.substr(hash('sha256', CanonicalJson::encode([
            $case['case_id'],
            $case['record_digest'],
            $all,
        ])), 0, 20);
        $path = $this->phases.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = $this->read($path, 'OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
            if (!$this->ok($existing)
                || ($existing['case']['id'] ?? null) !== ($case['case_id'] ?? null)
                || ($existing['case']['digest'] ?? null) !== ($case['record_digest'] ?? null)
                || ($existing['findings'] ?? null) !== $all) {
                throw new \RuntimeException('OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
            }

            return $existing;
        }
        $status = [] === $eligible
            ? 'ORACLE_NO_ELIGIBLE_MODEL_PENDING_CURIA_FALLBACK_ORDER'
            : 'ORACLE_ELIGIBILITY_FINDINGS_COMPLETE_PENDING_COMPARATIVE_ASSESSMENT';

        return $this->records->put(
            'var/imperium/offices/oracle/model-eligibility-phases',
            $id,
            [
                'schema' => 'imperium.oracle-model-eligibility-phase/v1',
                'phase_id' => $id,
                'case' => ['id' => $case['case_id'], 'digest' => $case['record_digest']],
                'findings' => $all,
                'eligible_models' => $eligible,
                'no_eligible_model' => [] === $eligible,
                'closed_at' => $latest?->format(DATE_ATOM),
                'comparative_assessment_authority' => [] !== $eligible,
                'curia_fallback_order_authority_required' => [] === $eligible,
                'ranking_authority' => false,
                'recommendation_authority' => false,
                'selection_authority' => false,
                'model_assignment_authority' => false,
                'profile_mutation_authority' => false,
                'provider_invocation_authority' => false,
                'deployment_authority' => false,
                'execution_authority' => false,
                'status' => $status,
                'sealed' => true,
            ],
        );
    }

    private function existingMatches(array $finding, array $case, string $authorityId, string $augurBindingId): bool
    {
        return $this->ok($case)
            && ($case['case_id'] ?? null) === ($finding['case']['id'] ?? null)
            && ($case['record_digest'] ?? null) === ($finding['case']['digest'] ?? null)
            && $authorityId === ($finding['authority']['id'] ?? null)
            && $augurBindingId === ($finding['actor']['binding_id'] ?? null)
            && $this->ok($finding)
            && 'imperium.oracle-model-eligibility-finding/v1' === ($finding['schema'] ?? null)
            && 'ORACLE_MODEL_ELIGIBILITY_FINDING_SEALED_NO_SELECTION_AUTHORITY' === ($finding['status'] ?? null);
    }

    private function authority(array $case, string $id): array
    {
        foreach ($case['eligibility_authorities'] ?? [] as $authority) {
            if ($id === ($authority['authority_id'] ?? null)) {
                return $authority;
            }
        }
        throw new \RuntimeException('OR52_MODEL_ELIGIBILITY_FINDING_INVALID');
    }

    private function criteriaValid(array $criteria, string $disposition): bool
    {
        foreach ($criteria as $criterion) {
            if (!is_array($criterion)
                || !in_array($criterion['disposition'] ?? null, ['SATISFIED', 'FAILED', 'UNPROVEN'], true)
                || !is_string($criterion['rationale'] ?? null)
                || '' === trim($criterion['rationale'])) {
                return false;
            }
        }
        if ('ELIGIBLE' === $disposition) {
            foreach ($criteria as $criterion) {
                if ('SATISFIED' !== $criterion['disposition']) {
                    return false;
                }
            }
        }
        if ('INELIGIBLE' === $disposition && !in_array('FAILED', array_column($criteria, 'disposition'), true)) {
            return false;
        }

        return 'INDETERMINATE' !== $disposition || in_array('UNPROVEN', array_column($criteria, 'disposition'), true);
    }

    private function subset(array $values, array $allowed): bool
    {
        return $this->strings($values) && [] === array_diff($values, $allowed);
    }

    private function strings(array $values, bool $empty = false): bool
    {
        if (!$empty && [] === $values) {
            return false;
        }
        foreach ($values as $value) {
            if (!is_string($value) || '' === trim($value)) {
                return false;
            }
        }

        return array_values(array_unique($values)) === $values;
    }

    private function existing(string $authorityId): ?array
    {
        if (!is_dir($this->findings)) {
            return null;
        }
        foreach (glob($this->findings.'/oracle-model-eligibility-finding-*.json') ?: [] as $path) {
            $record = $this->read($path, 'OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
            if ($authorityId === ($record['authority']['id'] ?? null)) {
                if (!$this->ok($record)) {
                    throw new \RuntimeException('OR53_MODEL_ELIGIBILITY_FINDING_FAILED');
                }

                return $record;
            }
        }

        return null;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function ok(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
}
