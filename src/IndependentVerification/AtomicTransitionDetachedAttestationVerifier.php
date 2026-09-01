<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationAttestationContract as Attestation;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationIdentityContract as Identity;
use App\Imperium\Runtime\Imperator\AtomicTransitionIndependentVerificationReportContract as Report;

/** Public, read-only detached-attestation verification; owns no signing capability. */
final class AtomicTransitionDetachedAttestationVerifier
{
    public function verify(array $report, array $identity, array $attestation): void
    {
        if (!$this->sealed($report, Report::REQUIRED_FIELDS, Report::SCHEMA)
            || !$this->sealed($identity, Identity::REQUIRED_FIELDS, Identity::SCHEMA)
            || !$this->sealed($attestation, Attestation::REQUIRED_FIELDS, Attestation::SCHEMA)
            || 'PASS' !== ($report['disposition'] ?? null)
            || Identity::ALGORITHM !== ($identity['algorithm'] ?? null)
            || Identity::ALGORITHM !== ($attestation['algorithm'] ?? null)
            || 'atomic-transition-independent-verification-report/v1' !== ($identity['key_purpose'] ?? null)
            || ($attestation['report_id'] ?? null) !== ($report['report_id'] ?? null)
            || ($attestation['report_digest'] ?? null) !== ($report['record_digest'] ?? null)
            || ($attestation['identity_id'] ?? null) !== ($identity['identity_id'] ?? null)
            || ($attestation['key_id'] ?? null) !== ($identity['key_id'] ?? null)
            || true !== ($attestation['signature_created'] ?? null)
            || true !== ($identity['authority_empty'] ?? null)
            || true !== ($attestation['authority_empty'] ?? null)
            || false !== ($identity['private_key_retained'] ?? null)
            || false !== ($identity['signing_capability_retained'] ?? null)
            || false !== ($attestation['private_key_retained'] ?? null)
            || false !== ($attestation['signing_capability_retained'] ?? null)) {
            throw new \RuntimeException('PBL1030_INDEPENDENT_ATTESTATION_BINDING_INVALID');
        }
        $public = base64_decode((string) $identity['public_key'], true);
        $signature = base64_decode((string) $attestation['signature'], true);
        if (false === $public || false === $signature
            || !hash_equals((string) $identity['public_key_digest'], hash('sha256', $public))
            || !function_exists('sodium_crypto_sign_verify_detached')
            || !sodium_crypto_sign_verify_detached($signature, $report['record_digest'], $public)) {
            throw new \RuntimeException('PBL1031_INDEPENDENT_ATTESTATION_SIGNATURE_INVALID');
        }
    }

    private function sealed(array $record, array $fields, string $schema): bool
    {
        $plain = $record;
        $digest = array_pop($plain);
        return $fields === array_keys($record) && $schema === ($record['schema'] ?? null)
            && true === ($record['sealed'] ?? null) && is_string($digest)
            && hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)));
    }
}
