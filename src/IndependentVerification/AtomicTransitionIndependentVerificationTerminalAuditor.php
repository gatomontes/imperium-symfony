<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\Bootstrap\CanonicalJson;

/** Terminal audit of the public preflight result; cannot inspect private evidence or close accepted. */
final class AtomicTransitionIndependentVerificationTerminalAuditor
{
    public function audit(string $auditId, array $preflight): array
    {
        $plain = $preflight;
        $digest = array_pop($plain);
        if ('imperium.atomic-transition-local-verification-preflight/v1' !== ($preflight['schema'] ?? null)
            || true !== ($preflight['sealed'] ?? null) || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || 'REFUSED_ACCEPTANCE_CASE_EVIDENCE_NOT_RETAINED' !== ($preflight['disposition'] ?? null)
            || false !== ($preflight['private_receipt_inspected'] ?? null)
            || false !== ($preflight['signing_capability_handled'] ?? null)
            || false !== ($preflight['detached_signature_created'] ?? null)) {
            throw new \RuntimeException('PBL1034_INDEPENDENT_VERIFICATION_TERMINAL_EVIDENCE_INVALID');
        }
        $record = [
            'schema' => 'imperium.atomic-transition-independent-verification-terminal-audit/v1',
            'audit_id' => $auditId,
            'local_preflight' => ['id' => $preflight['assessment_id'], 'digest' => $digest, 'schema' => $preflight['schema']],
            'acceptance_case_evidence_retained' => false,
            'passing_independent_report_available' => false,
            'detached_attestation_available' => false,
            'independent_verification_admission_available' => false,
            'legacy_unsigned_closure_accepted' => false,
            'closure_restored' => false,
            'requalification_retained' => true,
            'campaign_terminal' => true,
            'provider_binding_status' => 'BOUND_INACTIVE',
            'required_v3_execution_admission' => 'NOT_IMPLEMENTED',
            'unknown_replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED',
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'provider_invoked' => false,
            'external_io_started' => false,
            'continuing_authority' => false,
            'status' => 'CAMPAIGN_TERMINATED_INDEPENDENT_VERIFICATION_EVIDENCE_INSUFFICIENT',
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
