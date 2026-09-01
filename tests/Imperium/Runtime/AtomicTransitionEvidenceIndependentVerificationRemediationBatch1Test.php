<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationAttestationContract as Attestation;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationContractValidator as Validator;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationIdentityContract as Identity;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationInputContract as Input;
use PHPUnit\Framework\TestCase;

final class AtomicTransitionEvidenceIndependentVerificationRemediationBatch1Test extends TestCase
{
    public function testAuthorityEmptyInputIdentityAndAttestationContractsValidate(): void
    {
        $validator = new Validator();
        $validator->validate($this->seal([
            'schema' => Input::SCHEMA, 'verification_id' => 'verification.1',
            'sanitized_evidence' => $this->ref('evidence.1'), 'source_commit' => str_repeat('1', 40),
            'source_tree_digest' => str_repeat('2', 64), 'artifact_bindings' => [],
            'private_receipt_digest' => str_repeat('3', 64),
            'private_receipt_availability' => 'UNKNOWN', 'private_receipt_locator_supplied' => false,
            'producer_reconstruction_supplied' => false, 'producer_conclusion_supplied' => false,
            'read_only' => true, 'authority_empty' => true, 'execution_authorized' => false,
            'provider_authorized' => false, 'external_io_authorized' => false,
            'runtime_write_authorized' => false, 'continuing_authority' => false, 'sealed' => true,
        ]));
        $validator->validate($this->seal([
            'schema' => Identity::SCHEMA, 'identity_id' => 'identity.1', 'key_id' => 'key.1',
            'algorithm' => 'ed25519', 'public_key' => '', 'public_key_digest' => str_repeat('4', 64),
            'key_purpose' => 'atomic-transition-independent-verification-report/v1',
            'verifier_implementation_digest' => str_repeat('5', 64),
            'verifier_dependency_set_digest' => str_repeat('6', 64),
            'private_key_retained' => false, 'signing_capability_retained' => false,
            'authority_empty' => true, 'sealed' => true,
        ]));
        $validator->validate($this->seal([
            'schema' => Attestation::SCHEMA, 'attestation_id' => 'attestation.1',
            'report_id' => 'report.1', 'report_digest' => str_repeat('7', 64),
            'identity_id' => 'identity.1', 'key_id' => 'key.1', 'algorithm' => 'ed25519',
            'signature' => null, 'signature_created' => false, 'private_key_retained' => false,
            'signing_capability_retained' => false, 'authority_empty' => true, 'sealed' => true,
        ]));
        self::addToAssertionCount(1);
    }

    public function testProducerConclusionAndPrematureSignatureFailClosed(): void
    {
        $record = $this->seal([
            'schema' => Attestation::SCHEMA, 'attestation_id' => 'attestation.1',
            'report_id' => 'report.1', 'report_digest' => str_repeat('7', 64),
            'identity_id' => 'identity.1', 'key_id' => 'key.1', 'algorithm' => 'ed25519',
            'signature' => 'forbidden', 'signature_created' => true, 'private_key_retained' => false,
            'signing_capability_retained' => false, 'authority_empty' => true, 'sealed' => true,
        ]);
        $this->expectExceptionMessage('PBL1028_INDEPENDENT_VERIFICATION_ATTESTATION_NOT_AUTHORITY_EMPTY');
        (new Validator())->validate($record);
    }

    private function ref(string $id): array
    {
        return ['id' => $id, 'digest' => str_repeat('a', 64), 'schema' => 'evidence/v1'];
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
