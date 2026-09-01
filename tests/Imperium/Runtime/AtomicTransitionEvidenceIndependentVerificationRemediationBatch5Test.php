<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\AtomicTransitionDetachedAttestationVerifier;
use App\IndependentVerification\AtomicTransitionIndependentVerificationAdmissionConsumer as Consumer;

final class AtomicTransitionEvidenceIndependentVerificationRemediationBatch5Test extends AtomicTransitionEvidenceIndependentVerificationRemediationBatch3Test
{
    public function testExactTrustedReportIdentityAndSignatureAdmitPendingTerminalAudit(): void
    {
        $report = $this->passingReport();
        [$identity, $attestation] = $this->signed($report);
        $admission = $this->consumer($identity)->admit('admission.1', $report, $identity, $attestation);
        self::assertSame('INDEPENDENT_VERIFICATION_ADMITTED_PENDING_TERMINAL_AUDIT', $admission['status']);
        self::assertTrue($admission['all_domains_independently_verified']);
        foreach (['legacy_reconstruction_accepted', 'unsigned_report_accepted',
            'producer_conclusion_accepted', 'qualification_removed', 'campaign_closed',
            'runtime_state_written', 'continuing_authority'] as $field) {
            self::assertFalse($admission[$field]);
        }
    }

    public function testLegacyIndeterminateUnsignedAndUntrustedInputsRefuse(): void
    {
        $report = $this->passingReport();
        [$identity, $attestation] = $this->signed($report);
        foreach (['legacy', 'indeterminate', 'unsigned', 'untrusted'] as $case) {
            $candidateReport = $report;
            $candidateIdentity = $identity;
            $candidateAttestation = $attestation;
            if ('legacy' === $case) {
                $candidateReport['schema'] = 'imperium.atomic-transition-evidence-independent-reconstruction/v1';
                $candidateReport['record_digest'] = $this->digest($candidateReport);
            } elseif ('indeterminate' === $case) {
                $candidateReport['domain_outcomes']['acceptance_matrix'] = 'INDETERMINATE';
                $candidateReport['disposition'] = 'INDETERMINATE';
                $candidateReport['record_digest'] = $this->digest($candidateReport);
            } elseif ('unsigned' === $case) {
                $candidateAttestation['signature'] = null;
                $candidateAttestation['signature_created'] = false;
                $candidateAttestation['record_digest'] = $this->digest($candidateAttestation);
            } else {
                $candidateIdentity['verifier_implementation_digest'] = str_repeat('9', 64);
                $candidateIdentity['record_digest'] = $this->digest($candidateIdentity);
            }
            try {
                $this->consumer($identity)->admit('admission.'.$case, $candidateReport, $candidateIdentity, $candidateAttestation);
                self::fail('Inadmissible case accepted: '.$case);
            } catch (\RuntimeException $error) {
                self::assertStringStartsWith('PBL103', $error->getMessage());
            }
        }
    }

    public function testHistoricalTerminalClosureNowRefusesAndIsContainerExcluded(): void
    {
        $source = (string) file_get_contents(dirname(__DIR__, 3).'/src/Imperium/Runtime/Imperator/AtomicTransitionEvidenceTerminalAdversarialAuditor.php');
        $services = (string) file_get_contents(dirname(__DIR__, 3).'/config/services.yaml');
        self::assertStringContainsString('PBL1033_LEGACY_UNSIGNED_TERMINAL_CLOSURE_DISABLED', $source);
        self::assertStringContainsString('AtomicTransitionEvidenceTerminalAdversarialAuditor.php', $services);
    }

    private function consumer(array $identity): Consumer
    {
        return new Consumer(new AtomicTransitionDetachedAttestationVerifier(),
            $identity['identity_id'], $identity['public_key_digest'],
            $identity['verifier_implementation_digest'], $identity['verifier_dependency_set_digest']);
    }
}
