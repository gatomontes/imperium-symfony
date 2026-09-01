<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Pure terminal recomputation of the complete sealed Batch 3 evidence chain. */
final readonly class AtomicTransitionEvidenceTerminalRecomputer
{
    public function __construct(
        private AtomicTransitionEvidenceAggregateAuditBuilder $aggregateBuilder,
        private AtomicTransitionEvidenceValueAwareSecretExclusionService $secretService,
    ) {
    }

    public function recompute(
        string $recomputationId,
        array $cases,
        array $results,
        array $manifest,
        array $secretProof,
        array $aggregateReceipt,
    ): array {
        $recomputedManifest = $this->aggregateBuilder->manifest($manifest['manifest_id'] ?? '');
        if ($recomputedManifest !== $manifest) {
            throw new \RuntimeException('PBL990_TERMINAL_MANIFEST_RECOMPUTATION_MISMATCH');
        }

        $recomputedProof = $this->secretService->prove($secretProof['proof_id'] ?? '', $results);
        if ($recomputedProof !== $secretProof) {
            throw new \RuntimeException('PBL991_TERMINAL_SECRET_PROOF_RECOMPUTATION_MISMATCH');
        }

        $recomputedAggregate = $this->aggregateBuilder->build(
            $aggregateReceipt['receipt_id'] ?? '',
            $aggregateReceipt['replay_contention_root'] ?? '',
            $cases,
            $results,
            $manifest,
            $secretProof,
        );
        if ($recomputedAggregate !== $aggregateReceipt) {
            throw new \RuntimeException('PBL992_TERMINAL_AGGREGATE_RECOMPUTATION_MISMATCH');
        }

        $chain = [];
        foreach ($cases as $index => $case) {
            $chain[] = [
                'case_digest' => $case['record_digest'],
                'result_digest' => $results[$index]['record_digest'],
            ];
        }

        return $this->seal([
            'schema' => AtomicTransitionEvidenceTerminalRecomputationContract::SCHEMA,
            'recomputation_id' => $recomputationId,
            'aggregate_receipt_reference' => $this->reference($aggregateReceipt, 'receipt_id'),
            'ordered_case_chain_digest' => hash('sha256', CanonicalJson::encode($chain)),
            'recomputed_result_set_digest' => hash('sha256', CanonicalJson::encode($aggregateReceipt['ordered_case_result_references'])),
            'recomputed_capability_manifest_digest' => $recomputedManifest['record_digest'],
            'recomputed_secret_exclusion_proof_digest' => $recomputedProof['record_digest'],
            'recomputed_aggregate_receipt_digest' => $recomputedAggregate['record_digest'],
            'all_record_seals_recomputed' => true,
            'all_references_recomputed' => true,
            'ordered_result_set_recomputed' => true,
            'capability_manifest_recomputed' => true,
            'secret_exclusion_proof_recomputed' => true,
            'aggregate_receipt_recomputed' => true,
            'material_evidence_defect_corrected' => true,
            'qualification_removed' => false,
            'closure_replacement_authorized' => false,
            'terminal_recomputation_performed' => true,
            'read_only' => true,
            'journal_persisted' => false,
            'live_lock_acquired' => false,
            'state_written_or_repaired' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'successor_adopted' => false,
            'binding_state_changed' => false,
            'durable_winner_or_receipt_created' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionEvidenceTerminalRecomputationContract::STATUS,
            'sealed' => true,
        ]);
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
