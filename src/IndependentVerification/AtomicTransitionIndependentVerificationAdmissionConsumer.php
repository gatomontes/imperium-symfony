<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationReportContract as Report;

/** Sole repository-side admission of a fully attested independent report. */
final readonly class AtomicTransitionIndependentVerificationAdmissionConsumer
{
    public function __construct(
        private AtomicTransitionDetachedAttestationVerifier $attestations,
        private string $trustedIdentityId,
        private string $trustedPublicKeyDigest,
        private string $trustedVerifierImplementationDigest,
        private string $trustedVerifierDependencySetDigest,
    ) {
    }

    public function admit(string $admissionId, array $report, array $identity, array $attestation): array
    {
        if (Report::SCHEMA !== ($report['schema'] ?? null)
            || Report::DOMAINS !== array_keys($report['domain_outcomes'] ?? [])
            || array_fill_keys(Report::DOMAINS, 'PASS') !== $report['domain_outcomes']
            || 'PASS' !== ($report['disposition'] ?? null)
            || $this->trustedIdentityId !== ($identity['identity_id'] ?? null)
            || $this->trustedPublicKeyDigest !== ($identity['public_key_digest'] ?? null)
            || $this->trustedVerifierImplementationDigest !== ($identity['verifier_implementation_digest'] ?? null)
            || $this->trustedVerifierDependencySetDigest !== ($identity['verifier_dependency_set_digest'] ?? null)) {
            throw new \RuntimeException('PBL1032_INDEPENDENT_VERIFICATION_ADMISSION_REFUSED');
        }
        $this->attestations->verify($report, $identity, $attestation);
        $record = [
            'schema' => 'imperium.atomic-transition-independent-verification-admission/v1',
            'admission_id' => $admissionId,
            'report' => $this->reference($report, 'report_id'),
            'public_identity' => $this->reference($identity, 'identity_id'),
            'detached_attestation' => $this->reference($attestation, 'attestation_id'),
            'all_domains_independently_verified' => true,
            'legacy_reconstruction_accepted' => false,
            'unsigned_report_accepted' => false,
            'producer_conclusion_accepted' => false,
            'qualification_removed' => false,
            'campaign_closed' => false,
            'provider_binding_status' => 'BOUND_INACTIVE',
            'required_v3_execution_admission' => 'NOT_IMPLEMENTED',
            'unknown_replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED',
            'read_only' => true,
            'runtime_state_written' => false,
            'continuing_authority' => false,
            'status' => 'INDEPENDENT_VERIFICATION_ADMITTED_PENDING_TERMINAL_AUDIT',
            'sealed' => true,
        ];
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }
}
