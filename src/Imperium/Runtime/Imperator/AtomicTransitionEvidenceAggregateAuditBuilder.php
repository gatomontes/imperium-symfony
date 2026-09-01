<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Builds a non-durable receipt from already-derived, sealed in-memory results. */
final class AtomicTransitionEvidenceAggregateAuditBuilder
{
    public const array EVALUATORS = [
        AtomicTransitionEvidenceDeterministicCaseExecutor::class,
        AtomicTransitionEvidenceDerivationContractValidator::class,
        AtomicTransitionEvidenceValueAwareSecretExclusionService::class,
        self::class,
    ];

    public function manifest(string $manifestId): array
    {
        $capabilities = array_fill_keys(AtomicTransitionEvidenceActionCapabilityManifestContract::CAPABILITIES, false);
        return $this->seal([
            'schema' => AtomicTransitionEvidenceActionCapabilityManifestContract::SCHEMA,
            'manifest_id' => $manifestId,
            'evaluator_classes' => self::EVALUATORS,
            'capabilities' => $capabilities,
            'dependency_closure_digest' => hash('sha256', CanonicalJson::encode(self::EVALUATORS)),
            'read_only' => true,
            'status' => AtomicTransitionEvidenceActionCapabilityManifestContract::STATUS,
            'sealed' => true,
        ]);
    }

    public function build(string $receiptId, string $root, array $cases, array $results, array $manifest, array $secretProof): array
    {
        $this->assertManifest($manifest);
        $this->assertSecretProof($secretProof);
        if (count($cases) !== count($results)) {
            throw new \RuntimeException('PBL983_AGGREGATE_CASE_RESULT_COUNT_MISMATCH');
        }

        $byKind = [];
        foreach ($cases as $index => $case) {
            $result = $results[$index] ?? null;
            $this->assertSealed($case, AtomicTransitionEvidenceAdversarialCaseContract::REQUIRED_FIELDS, AtomicTransitionEvidenceAdversarialCaseContract::SCHEMA, 'PBL984_AGGREGATE_CASE_INVALID');
            $this->assertSealed($result, AtomicTransitionEvidenceDerivedCaseResultContract::REQUIRED_FIELDS, AtomicTransitionEvidenceDerivedCaseResultContract::SCHEMA, 'PBL985_AGGREGATE_RESULT_INVALID');
            $kind = $case['case_kind'] ?? null;
            if (($case['replay_contention_root'] ?? null) !== $root
                || ($result['case_reference'] ?? null) !== $this->reference($case, 'case_id')
                || true !== ($result['expectation_matched'] ?? null)
                || true !== ($result['case_executed'] ?? null)
                || true !== ($result['finding_derived'] ?? null)
                || true !== ($result['read_only'] ?? null)
                || !$this->resultDeclaresNoAction($result)
                || isset($byKind[$kind])) {
                throw new \RuntimeException('PBL986_AGGREGATE_RESULT_UNBOUND');
            }
            $byKind[$kind] = $this->reference($result, 'record_digest');
        }
        if (AtomicTransitionEvidenceAdversarialCaseContract::KINDS !== array_keys($byKind)) {
            throw new \RuntimeException('PBL987_COMPLETE_CASE_SET_REQUIRED');
        }
        $references = array_values($byKind);

        return $this->seal([
            'schema' => AtomicTransitionEvidenceAggregateAuditReceiptContract::SCHEMA,
            'receipt_id' => $receiptId,
            'replay_contention_root' => $root,
            'ordered_case_kinds' => array_keys($byKind),
            'ordered_case_result_references' => $references,
            'ordered_result_set_digest' => hash('sha256', CanonicalJson::encode($references)),
            'capability_manifest_reference' => $this->reference($manifest, 'manifest_id'),
            'secret_exclusion_proof_reference' => $this->reference($secretProof, 'proof_id'),
            'all_cases_matched' => true,
            'read_only' => true,
            'qualification_removed' => false,
            'terminal_recomputation_performed' => false,
            'durable_receipt_created' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionEvidenceAggregateAuditReceiptContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function assertManifest(array $manifest): void
    {
        $this->assertSealed($manifest, AtomicTransitionEvidenceActionCapabilityManifestContract::REQUIRED_FIELDS, AtomicTransitionEvidenceActionCapabilityManifestContract::SCHEMA, 'PBL988_CAPABILITY_MANIFEST_INVALID');
        if (self::EVALUATORS !== ($manifest['evaluator_classes'] ?? null)
            || array_fill_keys(AtomicTransitionEvidenceActionCapabilityManifestContract::CAPABILITIES, false) !== ($manifest['capabilities'] ?? null)
            || hash('sha256', CanonicalJson::encode(self::EVALUATORS)) !== ($manifest['dependency_closure_digest'] ?? null)
            || true !== ($manifest['read_only'] ?? null)) {
            throw new \RuntimeException('PBL988_CAPABILITY_MANIFEST_INVALID');
        }
    }

    private function assertSecretProof(array $proof): void
    {
        $this->assertSealed($proof, AtomicTransitionEvidenceSecretExclusionProofContract::REQUIRED_FIELDS, AtomicTransitionEvidenceSecretExclusionProofContract::SCHEMA, 'PBL989_SECRET_EXCLUSION_PROOF_INVALID');
        if (AtomicTransitionEvidenceSecretExclusionProofContract::REQUIRED_VECTOR_KINDS !== ($proof['attack_vector_kinds'] ?? null)
            || true !== ($proof['all_records_clean'] ?? null)
            || true !== ($proof['all_attacks_refused'] ?? null)
            || true !== ($proof['value_aware'] ?? null)
            || true !== ($proof['read_only'] ?? null)
            || array_fill(0, count(AtomicTransitionEvidenceSecretExclusionProofContract::REQUIRED_VECTOR_KINDS), 'PBL982_SECRET_OR_CAPABILITY_VALUE_REFUSED') !== ($proof['derived_refusal_codes'] ?? null)) {
            throw new \RuntimeException('PBL989_SECRET_EXCLUSION_PROOF_INVALID');
        }
    }

    private function resultDeclaresNoAction(array $result): bool
    {
        foreach ([
            'journal_persisted', 'live_lock_acquired',
            'state_written_or_repaired', 'authority_issued_or_consumed',
            'execution_admitted', 'successor_adopted',
            'binding_state_changed', 'durable_winner_or_receipt_created',
            'provider_effect_started', 'continuing_authority',
        ] as $field) {
            if (false !== ($result[$field] ?? null)) {
                return false;
            }
        }

        return true;
    }

    private function assertSealed(mixed $record, array $fields, string $schema, string $error): void
    {
        if (!is_array($record)) {
            throw new \RuntimeException($error);
        }
        $plain = $record;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record) || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null) || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
