<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class AtomicTransitionIndependentVerificationContractValidator
{
    public function validate(array $record): void
    {
        $schema = $record['schema'] ?? null;
        [$fields, $error] = match ($schema) {
            AtomicTransitionIndependentVerificationInputContract::SCHEMA => [AtomicTransitionIndependentVerificationInputContract::REQUIRED_FIELDS, 'INPUT'],
            AtomicTransitionIndependentVerificationReportContract::SCHEMA => [AtomicTransitionIndependentVerificationReportContract::REQUIRED_FIELDS, 'REPORT'],
            AtomicTransitionIndependentVerificationIdentityContract::SCHEMA => [AtomicTransitionIndependentVerificationIdentityContract::REQUIRED_FIELDS, 'IDENTITY'],
            AtomicTransitionIndependentVerificationAttestationContract::SCHEMA => [AtomicTransitionIndependentVerificationAttestationContract::REQUIRED_FIELDS, 'ATTESTATION'],
            default => throw new \RuntimeException('PBL1022_INDEPENDENT_VERIFICATION_CONTRACT_SCHEMA_INVALID'),
        };
        if ($fields !== array_keys($record)) {
            throw new \RuntimeException('PBL1023_INDEPENDENT_VERIFICATION_'.$error.'_FIELDS_INVALID');
        }
        $plain = $record;
        $digest = array_pop($plain);
        if (!is_string($digest) || !preg_match('/\A[0-9a-f]{64}\z/', $digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))
            || true !== ($record['sealed'] ?? null)) {
            throw new \RuntimeException('PBL1024_INDEPENDENT_VERIFICATION_'.$error.'_SEAL_INVALID');
        }
        $this->assertAuthorityEmpty($record, $error);
        if (AtomicTransitionIndependentVerificationInputContract::SCHEMA === $schema) {
            if (!in_array($record['private_receipt_availability'], AtomicTransitionIndependentVerificationInputContract::AVAILABILITY, true)
                || true !== $record['read_only'] || true !== $record['authority_empty']
                || true === $record['producer_reconstruction_supplied'] || true === $record['producer_conclusion_supplied']) {
                throw new \RuntimeException('PBL1025_INDEPENDENT_VERIFICATION_INPUT_INVALID');
            }
        }
        if (AtomicTransitionIndependentVerificationReportContract::SCHEMA === $schema) {
            if (AtomicTransitionIndependentVerificationReportContract::DOMAINS !== array_keys($record['domain_outcomes'] ?? [])
                || array_diff($record['domain_outcomes'], AtomicTransitionIndependentVerificationReportContract::OUTCOMES)
                || true !== $record['sanitized'] || false !== $record['producer_disposition_imported']
                || false !== $record['producer_success_boolean_imported']) {
                throw new \RuntimeException('PBL1026_INDEPENDENT_VERIFICATION_REPORT_INVALID');
            }
        }
        if (AtomicTransitionIndependentVerificationIdentityContract::SCHEMA === $schema
            && (AtomicTransitionIndependentVerificationIdentityContract::ALGORITHM !== $record['algorithm']
                || 'atomic-transition-independent-verification-report/v1' !== $record['key_purpose'])) {
            throw new \RuntimeException('PBL1027_INDEPENDENT_VERIFICATION_IDENTITY_INVALID');
        }
        if (AtomicTransitionIndependentVerificationAttestationContract::SCHEMA === $schema
            && (AtomicTransitionIndependentVerificationIdentityContract::ALGORITHM !== $record['algorithm']
                || false !== $record['signature_created'] || null !== $record['signature'])) {
            throw new \RuntimeException('PBL1028_INDEPENDENT_VERIFICATION_ATTESTATION_NOT_AUTHORITY_EMPTY');
        }
    }

    private function assertAuthorityEmpty(array $record, string $error): void
    {
        foreach (['execution_authorized', 'provider_authorized', 'external_io_authorized',
            'runtime_write_authorized', 'runtime_state_written', 'authority_issued_or_consumed',
            'provider_invoked', 'external_io_started', 'continuing_authority',
            'receipt_content_retained', 'receipt_locator_retained', 'private_material_retained',
            'private_key_retained', 'signing_capability_retained'] as $field) {
            if (array_key_exists($field, $record) && false !== $record[$field]) {
                throw new \RuntimeException('PBL1029_INDEPENDENT_VERIFICATION_'.$error.'_AUTHORITY_BREACH:'.$field);
            }
        }
    }
}
