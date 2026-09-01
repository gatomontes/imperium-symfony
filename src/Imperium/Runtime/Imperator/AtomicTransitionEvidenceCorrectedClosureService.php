<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Revalidates the terminal chain and emits one read-only corrected closure. */
final readonly class AtomicTransitionEvidenceCorrectedClosureService
{
    public function __construct(private AtomicTransitionEvidenceTerminalRecomputer $recomputer)
    {
    }

    public function close(
        string $closureId,
        array $cases,
        array $results,
        array $manifest,
        array $secretProof,
        array $aggregateReceipt,
        array $terminalRecomputation,
    ): array {
        $recomputed = $this->recomputer->recompute(
            $terminalRecomputation['recomputation_id'] ?? '',
            $cases,
            $results,
            $manifest,
            $secretProof,
            $aggregateReceipt,
        );
        if ($recomputed !== $terminalRecomputation
            || AtomicTransitionEvidenceTerminalRecomputationContract::STATUS !== ($terminalRecomputation['status'] ?? null)
            || true !== ($terminalRecomputation['material_evidence_defect_corrected'] ?? null)
            || false !== ($terminalRecomputation['qualification_removed'] ?? null)
            || false !== ($terminalRecomputation['closure_replacement_authorized'] ?? null)
            || true !== ($terminalRecomputation['terminal_recomputation_performed'] ?? null)
            || true !== ($terminalRecomputation['read_only'] ?? null)) {
            throw new \RuntimeException('PBL993_CORRECTED_CLOSURE_TERMINAL_CHAIN_INVALID');
        }

        return $this->seal([
            'schema' => AtomicTransitionEvidenceCorrectedClosureContract::SCHEMA,
            'closure_id' => $closureId,
            'superseded_closure_status' => AtomicTransitionEvidenceCorrectedClosureContract::PRIOR_CLOSURE,
            'terminal_recomputation_reference' => $this->reference($terminalRecomputation, 'recomputation_id'),
            'aggregate_receipt_reference' => $this->reference($aggregateReceipt, 'receipt_id'),
            'terminal_evidence_chain_digest' => hash('sha256', CanonicalJson::encode([
                $aggregateReceipt['record_digest'],
                $terminalRecomputation['record_digest'],
            ])),
            'material_evidence_defect_corrected' => true,
            'qualification_removed' => true,
            'campaign_closed' => true,
            'provider_binding_status' => 'BOUND_INACTIVE',
            'required_v3_execution_admission' => 'NOT_IMPLEMENTED',
            'unknown_replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED',
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'provider_binding_changed' => false,
            'durable_winner_or_runtime_receipt_created' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionEvidenceCorrectedClosureContract::STATUS,
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
