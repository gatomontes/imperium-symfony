<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Final pure consumer of the retained operational package and its reconstruction. */
final readonly class AtomicTransitionEvidenceTerminalAdversarialAuditor
{
    private const array HISTORICAL_REFUSALS = [
        'PBL1015_HISTORICAL_BOOLEAN_AUDIT_DISABLED',
        'PBL1016_HISTORICAL_SELF_RECOMPUTED_CLOSURE_DISABLED',
    ];

    public function __construct(
        private AtomicTransitionEvidenceIndependentReconstructor $reconstructor,
    ) {
    }

    public function close(string $closureId, array $evidence, array $reconstruction): array
    {
        $recomputed = $this->reconstructor->reconstruct(
            $reconstruction['reconstruction_id'] ?? '',
            $evidence,
        );
        if ($recomputed !== $reconstruction
            || AtomicTransitionEvidenceIndependentReconstructionContract::REQUIRED_FIELDS !== array_keys($reconstruction)
            || AtomicTransitionEvidenceIndependentReconstructionContract::STATUS !== ($reconstruction['status'] ?? null)
            || true !== ($reconstruction['source_and_build_binding_reconstructed'] ?? null)
            || true !== ($reconstruction['trusted_execution_binding_reconstructed'] ?? null)
            || true !== ($reconstruction['acceptance_matrix_reconstructed'] ?? null)
            || true !== ($reconstruction['complete_chain_exclusion_reconstructed'] ?? null)
            || true !== ($reconstruction['non_authority_perimeter_reconstructed'] ?? null)
            || false !== ($reconstruction['producer_disposition_imported'] ?? null)
            || false !== ($reconstruction['historical_boolean_audit_accepted'] ?? null)
            || false !== ($reconstruction['historical_self_recomputed_closure_accepted'] ?? null)
            || false !== ($reconstruction['qualification_removed'] ?? null)
            || false !== ($reconstruction['campaign_closed'] ?? null)
            || true !== ($reconstruction['read_only'] ?? null)) {
            throw new \RuntimeException('PBL1021_TERMINAL_ADVERSARIAL_RECONSTRUCTION_INVALID');
        }

        return $this->seal([
            'schema' => AtomicTransitionEvidenceAuthenticatedClosureContract::SCHEMA,
            'closure_id' => $closureId,
            'sanitized_evidence_reference' => $reconstruction['sanitized_evidence_reference'],
            'independent_reconstruction_reference' => $this->reference(
                $reconstruction,
                'reconstruction_id',
            ),
            'terminal_evidence_chain_digest' => hash('sha256', CanonicalJson::encode([
                $evidence['record_digest'],
                $reconstruction['record_digest'],
                self::HISTORICAL_REFUSALS,
            ])),
            'authenticated_operational_evidence_survived' => true,
            'independent_reconstruction_survived' => true,
            'historical_boolean_audit_disabled' => true,
            'historical_self_recomputed_closure_disabled' => true,
            'producer_disposition_imported' => false,
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
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'retry_authorized' => false,
            'live_command_adopted' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionEvidenceAuthenticatedClosureContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function reference(array $record, string $id): array
    {
        return [
            'id' => $record[$id],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
