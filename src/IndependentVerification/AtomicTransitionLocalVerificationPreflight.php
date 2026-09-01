<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\Bootstrap\CanonicalJson;

/** Fail-stop before private receipt selection or signing-custody opening. */
final class AtomicTransitionLocalVerificationPreflight
{
    public function assess(string $assessmentId, array $sanitizedSummary): array
    {
        $matrix = $sanitizedSummary['acceptance_matrix'] ?? null;
        $caseEvidenceDigest = $sanitizedSummary['acceptance_case_evidence_digest'] ?? null;
        $eligible = is_array($matrix) && 8 === count($matrix)
            && is_string($caseEvidenceDigest)
            && (bool) preg_match('/\A[0-9a-f]{64}\z/', $caseEvidenceDigest);

        $record = [
            'schema' => 'imperium.atomic-transition-local-verification-preflight/v1',
            'assessment_id' => $assessmentId,
            'sanitized_evidence_digest' => $sanitizedSummary['record_digest'] ?? null,
            'acceptance_matrix_present' => is_array($matrix),
            'acceptance_case_evidence_binding_present' => is_string($caseEvidenceDigest),
            'private_receipt_intake_permitted' => $eligible,
            'signing_custody_opening_permitted' => $eligible,
            'local_verifier_executed' => false,
            'private_receipt_inspected' => false,
            'signing_capability_handled' => false,
            'detached_signature_created' => false,
            'mission_rerun_permitted' => false,
            'replacement_receipt_permitted' => false,
            'disposition' => $eligible
                ? 'ELIGIBLE_FOR_EXPLICITLY_AUTHORIZED_LOCAL_VERIFICATION'
                : 'REFUSED_ACCEPTANCE_CASE_EVIDENCE_NOT_RETAINED',
            'read_only' => true,
            'runtime_state_written' => false,
            'continuing_authority' => false,
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
