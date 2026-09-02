<?php

declare(strict_types=1);

namespace App\IndependentVerification;

use App\ReproofV2\Contract;
use App\ReproofV2\Records;

/** Public-only admission for this separately approved proof event. Not a closure authority. */
final readonly class ReproofV2AdmissionConsumer
{
    public const string TRUST_ANCHOR_DIGEST = '25248ea1624c3ba315e59ead45d1a88fb6ca80f3f4a2ef03995e52e7674addb0';
    public const string STATUS = 'INDEPENDENTLY_ATTESTED_REPROOF_ADMITTED_PENDING_TERMINAL_AUDIT';
    public const string SCHEMA = 'imperium.atomic-transition-reproof.repository-admission/v2';
    public const array FIELDS = ['schema', 'proof_id', 'trust_anchor_digest', 'source_commit', 'source_manifest_root',
        'execution_request_digest', 'verification_request_digest', 'verifier_root', 'candidate_digest', 'receipt_digest',
        'identity_digest', 'report_digest', 'attestation_digest', 'input_root', 'expected_root', 'observed_root', 'admitted_at',
        'disposition', 'qualification_removed', 'campaign_closed', 'provider_binding_status', 'required_v3_execution_admission',
        'unknown_replay_posture', 'continuing_authority', 'record_digest'];

    public function __construct(private array $operatorAnchor)
    {
        try {
            $this->require(($operatorAnchor['schema'] ?? null) === 'imperium.atomic-transition-reproof.operator-provisioned-trust-anchor/v2'
                && ($operatorAnchor['record_digest'] ?? null) === self::TRUST_ANCHOR_DIGEST
                && Records::same($operatorAnchor, Records::seal($operatorAnchor)));
        } catch (\Throwable) { throw new \RuntimeException('REPROOF_V2_UNTRUSTED_ADMISSION_ANCHOR'); }
    }

    /** $now is supplied by the trusted local caller's clock, never a field of submitted evidence. */
    public function admit(array $candidate, array $identity, array $report, array $attestation, \DateTimeImmutable $now): array
    {
        try {
            foreach (['candidate' => $candidate, 'identity' => $identity, 'report' => $report, 'attestation' => $attestation] as $kind => $record) {
                $keys = array_keys($record); $fields = Contract::FIELDS[$kind]; sort($keys); sort($fields);
                $this->require($keys === $fields && $record['schema'] === Contract::SCHEMAS[$kind]
                    && $record['record_digest'] === $this->operatorAnchor[$kind.'_digest']
                    && Records::same($record, Records::seal($record)));
            }
            $anchor = $this->operatorAnchor;
            $this->require($candidate['proof_id'] === $anchor['proof_id'] && $report['proof_id'] === $anchor['proof_id']
                && $candidate['source_commit'] === $anchor['source_commit'] && $report['source_commit'] === $anchor['source_commit']
                && $candidate['source_manifest_root'] === $anchor['source_manifest_root']
                && $candidate['receipt_digest'] === $anchor['receipt_digest'] && $report['receipt_digest'] === $anchor['receipt_digest']
                && $report['candidate_digest'] === $candidate['record_digest']
                && $report['trusted_identity_digest'] === $identity['record_digest']
                && $report['verifier_root'] === $anchor['verifier_root'] && $identity['verifier_root'] === $anchor['verifier_root']
                && $identity['identity_id'] === $anchor['identity_id'] && $identity['purpose'] === Contract::PURPOSE
                && $attestation['purpose'] === Contract::PURPOSE && $anchor['key_purpose'] === Contract::PURPOSE
                && $attestation['report_digest'] === $report['record_digest']
                && $attestation['identity_digest'] === $identity['record_digest']);
            $this->require($candidate['disposition'] === 'CANDIDATE_NOT_VERIFIED' && $candidate['retention'] === Contract::RETENTION
                && $report['disposition'] === 'PASS' && Records::same($report['domain_outcomes'], array_fill_keys(Contract::DOMAINS, 'PASS'))
                && $report['qualification_removed'] === false && $report['campaign_closed'] === false);
            $this->require(is_string($identity['public_key']) && 1 === preg_match('/^[a-f0-9]{64}$/D', $identity['public_key'])
                && is_string($attestation['signature']) && 1 === preg_match('/^[a-f0-9]{128}$/D', $attestation['signature']));
            $key = hex2bin($identity['public_key']);
            $this->require(hash('sha256', $key) === $anchor['public_key_digest']);
            $start = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $identity['not_before'], new \DateTimeZone('UTC'));
            $end = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $identity['expires_at'], new \DateTimeZone('UTC'));
            $this->require(false !== $start && false !== $end && $start->format('Y-m-d\TH:i:s\Z') === $identity['not_before']
                && $end->format('Y-m-d\TH:i:s\Z') === $identity['expires_at'] && $start < $end
                && $end->getTimestamp() - $start->getTimestamp() === 86400 && $now >= $start && $now < $end);
            $this->require(sodium_crypto_sign_verify_detached(hex2bin($attestation['signature']),
                Contract::PURPOSE."\0".$report['record_digest'], $key));
            return Records::seal(['schema' => self::SCHEMA, 'proof_id' => $anchor['proof_id'],
                'trust_anchor_digest' => $anchor['record_digest'], 'source_commit' => $anchor['source_commit'],
                'source_manifest_root' => $anchor['source_manifest_root'], 'execution_request_digest' => $anchor['execution_request_digest'],
                'verification_request_digest' => $anchor['verification_request_digest'], 'verifier_root' => $anchor['verifier_root'],
                'candidate_digest' => $candidate['record_digest'], 'receipt_digest' => $anchor['receipt_digest'],
                'identity_digest' => $identity['record_digest'], 'report_digest' => $report['record_digest'],
                'attestation_digest' => $attestation['record_digest'], 'input_root' => $candidate['input_root'],
                'expected_root' => $candidate['expected_root'], 'observed_root' => $candidate['observed_root'],
                'admitted_at' => $now->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z'),
                'disposition' => self::STATUS, 'qualification_removed' => false, 'campaign_closed' => false,
                'provider_binding_status' => 'BOUND_INACTIVE', 'required_v3_execution_admission' => 'NOT_IMPLEMENTED',
                'unknown_replay_posture' => 'UNKNOWN_REPLAY_PROHIBITED', 'continuing_authority' => false]);
        } catch (\Throwable) { throw new \RuntimeException('REPROOF_V2_ADMISSION_REFUSED'); }
    }

    private function require(bool $condition): void
    {
        if (!$condition) { throw new \RuntimeException('REPROOF_V2_ADMISSION_REFUSED'); }
    }
}
