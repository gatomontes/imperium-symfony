<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\IndependentVerification\AtomicTransitionArtifactAndReceiptVerifier as Verifier;
use App\IndependentVerification\AtomicTransitionDetachedAttestationVerifier as AttestationVerifier;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationAttestationContract as Attestation;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationIdentityContract as Identity;

class AtomicTransitionEvidenceIndependentVerificationRemediationBatch3Test extends AtomicTransitionEvidenceIndependentVerificationRemediationBatch2Test
{
    public function testSelfHashedRetainedV1PackageCannotReceiveAdmissibleAttestation(): void
    {
        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $report = (new Verifier())->verify('report.3', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));
        [$identity, $attestation] = $this->signed($report);

        $this->expectExceptionMessage('PBL1030_INDEPENDENT_ATTESTATION_BINDING_INVALID');
        (new AttestationVerifier())->verify($report, $identity, $attestation);
    }

    public function testSyntheticCompleteReportAcceptsOnlyExactDetachedBinding(): void
    {
        $report = $this->passingReport();
        [$identity, $attestation] = $this->signed($report);
        (new AttestationVerifier())->verify($report, $identity, $attestation);
        self::addToAssertionCount(1);

        $wrong = $identity;
        $wrong['key_id'] = 'wrong-key';
        $wrong['record_digest'] = $this->digest($wrong);
        try {
            (new AttestationVerifier())->verify($report, $wrong, $attestation);
            self::fail('Wrong public identity accepted.');
        } catch (\RuntimeException $error) {
            self::assertSame('PBL1030_INDEPENDENT_ATTESTATION_BINDING_INVALID', $error->getMessage());
        }

        $bad = $attestation;
        $bad['signature'] = base64_encode(str_repeat("\0", 64));
        $bad['record_digest'] = $this->digest($bad);
        $this->expectExceptionMessage('PBL1031_INDEPENDENT_ATTESTATION_SIGNATURE_INVALID');
        (new AttestationVerifier())->verify($report, $identity, $bad);
    }

    public function testProducerConclusionAndSecretLeakageRefuse(): void
    {
        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $input['producer_conclusion_supplied'] = true;
        $input['record_digest'] = $this->digest($input);
        try {
            (new Verifier())->verify('report.4', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));
            self::fail('Producer conclusion accepted.');
        } catch (\RuntimeException $error) {
            self::assertSame('PBL1025_INDEPENDENT_VERIFICATION_INPUT_INVALID', $error->getMessage());
        }

        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $receipt['diagnostic'] = ['credential_secret' => 'forbidden'];
        $receipt['record_digest'] = $this->digest($receipt);
        $input['private_receipt_digest'] = $receipt['record_digest'];
        $input['record_digest'] = $this->digest($input);
        $summary['private_receipt_digest'] = $receipt['record_digest'];
        $report = (new Verifier())->verify('report.5', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));
        self::assertSame('REFUSED', $report['domain_outcomes']['complete_chain_exclusion']);
    }

    protected function passingReport(): array
    {
        [$input, $summary, $receipt, $bytes] = $this->fixture();
        $report = (new Verifier())->verify('report.pass', $input, $summary, $receipt, $bytes, $this->ref('identity.1'));
        $report['domain_outcomes']['acceptance_matrix'] = 'PASS';
        $report['disposition'] = 'PASS';
        $report['record_digest'] = $this->digest($report);
        return $report;
    }

    protected function signed(array $report): array
    {
        $seed = hash('sha256', 'imperium-batch-3-synthetic-only', true);
        $pair = sodium_crypto_sign_seed_keypair($seed);
        $public = sodium_crypto_sign_publickey($pair);
        $secret = sodium_crypto_sign_secretkey($pair);
        $identity = $this->seal([
            'schema' => Identity::SCHEMA, 'identity_id' => 'identity.synthetic.1',
            'key_id' => 'key.synthetic.1', 'algorithm' => 'ed25519',
            'public_key' => base64_encode($public), 'public_key_digest' => hash('sha256', $public),
            'key_purpose' => 'atomic-transition-independent-verification-report/v1',
            'verifier_implementation_digest' => str_repeat('5', 64),
            'verifier_dependency_set_digest' => str_repeat('6', 64),
            'private_key_retained' => false, 'signing_capability_retained' => false,
            'authority_empty' => true, 'sealed' => true,
        ]);
        $attestation = $this->seal([
            'schema' => Attestation::SCHEMA, 'attestation_id' => 'attestation.synthetic.1',
            'report_id' => $report['report_id'], 'report_digest' => $report['record_digest'],
            'identity_id' => $identity['identity_id'], 'key_id' => $identity['key_id'],
            'algorithm' => 'ed25519',
            'signature' => base64_encode(sodium_crypto_sign_detached($report['record_digest'], $secret)),
            'signature_created' => true, 'private_key_retained' => false,
            'signing_capability_retained' => false, 'authority_empty' => true, 'sealed' => true,
        ]);
        sodium_memzero($secret);
        return [$identity, $attestation];
    }
}
