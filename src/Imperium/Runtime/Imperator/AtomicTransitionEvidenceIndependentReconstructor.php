<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

/** Pure reconstruction of the one retained, source-pinned sanitized mission package. */
final class AtomicTransitionEvidenceIndependentReconstructor
{
    private const string EVIDENCE_SCHEMA =
        'imperium.sanitized-atomic-transition-integrated-disposable-mission-evidence/v1';
    private const string MISSION_ID = 'ATOMIC-TRANSITION-DISPOSABLE-PROOF-1';
    private const string SOURCE_COMMIT = '44b9e9151d522c3ff6ac82fe0166947c1d8d8377';
    private const string RECORD_DIGEST = '1f98e4efb8ef93235793590e1fd80b13601dc80a4a689c6e15986494d3f765d3';
    private const array EXACT_BINDINGS = [
        'source_tree_digest' => 'f5933d0c1b9d717e54620f9c5ae1cff2732dea3dc710a9500c64ff5c02ee01f1',
        'build_artifact_digest' => '60db8dff08ae2fad53086540ccfc1559f02279c75b20de29aaafda562eeaa050',
        'dependency_lock_digest' => '4ab52c72c931bcc945385cdbae48dd9ae3f192e4c207c59a7783e4cf38b8e6e9',
        'runner_digest' => '7773fccb96e1555c72d242a2d307ec795523ded163a18a5a211b774845e99573',
        'mission_implementation_digest' => 'e5f8d8aa34a2401fee465e0f1b0430158ebace678a4dabac371ac6bb8b59a9b0',
        'evidence_origin_digest' => 'c00c304b1f5ac299fe2943c6f454930a1480c5964345f5ce6217a66a61cf4b6e',
        'execution_provenance_digest' => '4132a8a4906a6a646f5aa7a8ef10b1bd08610d5fb62823dadf149a206836953a',
        'trusted_result_digest' => 'a398462aacd0da0f18754145b8265204df6df8f2d1cd9bf8aa34dc0a439712bb',
        'dependency_graph_digest' => '326f0a83a5e529b79dd24fb8466fd003785b99eeefe66f7a2456675d1a3ab3eb',
        'private_receipt_digest' => '21933ac0e9d76326dfd8b8da10114a6029c6868adfb67ef8d65584a11c9d0896',
    ];
    private const array ACCEPTANCE_MATRIX = [
        'interruption_before_journal' => 'ABSENT',
        'interruption_after_journal' => 'PREPARED',
        'interruption_after_winner' => 'COMMITTING',
        'interruption_after_receipt' => 'COMMITTED',
        'exact_replay' => 'EXACT_REPLAY',
        'changed_evidence' => 'CHANGED_EVIDENCE_REFUSED',
        'same_root_contention' => 'SAME_ROOT_CONTENTION_REFUSED',
        'partial_write' => 'INCOMPLETE',
    ];

    public function reconstruct(string $reconstructionId, array $evidence): array
    {
        $this->assertEvidence($evidence);

        return $this->seal([
            'schema' => AtomicTransitionEvidenceIndependentReconstructionContract::SCHEMA,
            'reconstruction_id' => $reconstructionId,
            'sanitized_evidence_reference' => [
                'mission_id' => self::MISSION_ID,
                'source_commit' => self::SOURCE_COMMIT,
                'record_digest' => self::RECORD_DIGEST,
            ],
            'source_and_build_binding_reconstructed' => true,
            'trusted_execution_binding_reconstructed' => true,
            'acceptance_matrix_reconstructed' => true,
            'complete_chain_exclusion_reconstructed' => true,
            'non_authority_perimeter_reconstructed' => true,
            'producer_disposition_imported' => false,
            'historical_boolean_audit_accepted' => false,
            'historical_self_recomputed_closure_accepted' => false,
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'provider_binding_changed' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'qualification_removed' => false,
            'campaign_closed' => false,
            'status' => AtomicTransitionEvidenceIndependentReconstructionContract::STATUS,
            'sealed' => true,
        ]);
    }

    private function assertEvidence(array $evidence): void
    {
        $plain = $evidence;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if (self::RECORD_DIGEST !== $digest
            || !hash_equals(self::RECORD_DIGEST, hash('sha256', CanonicalJson::encode($plain)))
            || self::EVIDENCE_SCHEMA !== ($evidence['schema'] ?? null)
            || self::MISSION_ID !== ($evidence['mission_id'] ?? null)
            || self::SOURCE_COMMIT !== ($evidence['source_commit'] ?? null)
            || '8.4.14' !== ($evidence['php_version'] ?? null)
            || self::ACCEPTANCE_MATRIX !== ($evidence['acceptance_matrix'] ?? null)
            || 'OPERATOR_LOCAL_ONLY_NOT_FOR_UPLOAD_OR_COMMIT' !== ($evidence['private_receipt_retention'] ?? null)) {
            throw new \RuntimeException('PBL1017_INDEPENDENT_RECONSTRUCTION_EVIDENCE_INVALID');
        }
        foreach (self::EXACT_BINDINGS as $field => $expected) {
            if ($expected !== ($evidence[$field] ?? null)) {
                throw new \RuntimeException('PBL1018_INDEPENDENT_RECONSTRUCTION_BINDING_MISMATCH:'.$field);
            }
        }
        foreach ([
            'complete_chain_content_exclusion_observed',
            'integrated_operational_receipt_created',
        ] as $required) {
            if (true !== ($evidence[$required] ?? null)) {
                throw new \RuntimeException('PBL1019_INDEPENDENT_RECONSTRUCTION_REQUIRED_OBSERVATION_MISSING:'.$required);
            }
        }
        foreach ([
            'caller_result_accepted', 'provider_or_external_effect_authorized',
            'live_credential_or_capability_authorized', 'runtime_state_written',
            'continuing_authority',
        ] as $prohibited) {
            if (false !== ($evidence[$prohibited] ?? null)) {
                throw new \RuntimeException('PBL1020_INDEPENDENT_RECONSTRUCTION_NON_AUTHORITY_PERIMETER_BREACHED:'.$prohibited);
            }
        }
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
