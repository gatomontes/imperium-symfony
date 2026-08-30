<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;

final class ProviderAssuranceEvidenceContractValidator
{
    public function assertSource(array $source): void
    {
        $this->common(
            $source,
            ProviderAssuranceEvidenceSourceContract::REQUIRED_FIELDS,
            ProviderAssuranceEvidenceSourceContract::SCHEMA,
            'PER200_EVIDENCE_SOURCE_INVALID',
        );
        if (!$this->identifier($source['source_id'] ?? null)
            || 'agentmail' !== ($source['provider_id'] ?? null)
            || !in_array(
                $source['source_kind'] ?? null,
                ProviderAssuranceEvidenceSourceContract::SOURCE_KINDS,
                true,
            )
            || !$this->httpsUri($source['canonical_uri'] ?? null)
            || !$this->date($source['observed_at'] ?? null)
            || !$this->digest($source['content_digest'] ?? null)
            || !is_string($source['version_identity'] ?? null)
            || '' === trim($source['version_identity'])
            || !in_array(
                $source['immutability_posture'] ?? null,
                ['MUTABLE_REMOTE_PAGE', 'IMMUTABLE_CONTENT_DIGEST', 'SEALED_OFFLINE_OBSERVATION'],
                true,
            )
            || 'DEFINED_EVIDENCE_ONLY' !== ($source['status'] ?? null)) {
            throw new \RuntimeException('PER200_EVIDENCE_SOURCE_INVALID');
        }
    }

    public function assertProfile(array $profile, array $sources): void
    {
        $this->common(
            $profile,
            AgentMailDirectSendAssuranceProfileContract::REQUIRED_FIELDS,
            AgentMailDirectSendAssuranceProfileContract::SCHEMA,
            'PER210_ASSURANCE_PROFILE_INVALID',
        );
        if (!$this->identifier($profile['profile_id'] ?? null)
            || AgentMailDirectSendAssuranceProfileContract::PROVIDER_ID
                !== ($profile['provider_id'] ?? null)
            || AgentMailDirectSendAssuranceProfileContract::OPERATION
                !== ($profile['operation'] ?? null)
            || AgentMailDirectSendAssuranceProfileContract::ENDPOINT_TEMPLATE
                !== ($profile['endpoint'] ?? null)
            || !$this->referencesMatch($profile['evidence_sources'] ?? null, $sources, 'source_id')
            || !$this->exact(
                $profile['collision_scope'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_COLLISION_SCOPE_FIELDS,
            )
            || [true, true, true, true] !== array_values($profile['collision_scope'])
            || !$this->exact(
                $profile['idempotency_key'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_IDEMPOTENCY_KEY_FIELDS,
            )
            || 'Idempotency-Key' !== $profile['idempotency_key']['header_name']
            || 1 !== $profile['idempotency_key']['minimum_length']
            || 256 !== $profile['idempotency_key']['maximum_length']
            || 'A-Za-z0-9-._~' !== $profile['idempotency_key']['allowed_character_class']
            || false !== $profile['idempotency_key']['empty_permitted']
            || !$this->allTrue(
                $profile['request_equivalence'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_REQUEST_EQUIVALENCE_FIELDS,
            )
            || !$this->exact(
                $profile['completed_duplicate'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_COMPLETED_DUPLICATE_FIELDS,
            )
            || [false, true, true] !== array_values($profile['completed_duplicate'])
            || !$this->exact(
                $profile['changed_request'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_CHANGED_REQUEST_FIELDS,
            )
            || 409 !== $profile['changed_request']['same_key_changed_request_expected_status']
            || true !== $profile['changed_request']['local_collision_refusal_required']
            || !$this->exact(
                $profile['retention'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_RETENTION_FIELDS,
            )
            || 24 !== $profile['retention']['declared_duration_hours']
            || 'PROVIDER_COMPLETION' !== $profile['retention']['anchor']
            || false !== $profile['retention']['local_effect_start_may_establish_anchor']
            || !$this->allUnknown(
                $profile['explicit_unknowns'] ?? null,
                AgentMailDirectSendAssuranceProfileContract::REQUIRED_UNKNOWN_FIELDS,
            )
            || AgentMailDirectSendAssuranceProfileContract::REPLAY_POSTURE
                !== ($profile['replay_posture'] ?? null)
            || 'DEFINED_EVIDENCE_ONLY' !== ($profile['status'] ?? null)) {
            throw new \RuntimeException('PER210_ASSURANCE_PROFILE_INVALID');
        }
    }

    public function assertAdmission(array $admission, array $profile, array $sources): void
    {
        $this->common(
            $admission,
            ProviderAssuranceEvidenceAdmissionContract::REQUIRED_FIELDS,
            ProviderAssuranceEvidenceAdmissionContract::SCHEMA,
            'PER220_EVIDENCE_ADMISSION_INVALID',
        );
        $this->assertProfile($profile, $sources);
        if (!$this->identifier($admission['admission_id'] ?? null)
            || !$this->identifier($admission['instance_id'] ?? null)
            || 'agentmail' !== ($admission['provider_id'] ?? null)
            || 'email.send' !== ($admission['operation'] ?? null)
            || !$this->matches($admission['assurance_profile'] ?? null, $profile, 'profile_id')
            || !$this->referencesMatch($admission['evidence_sources'] ?? null, $sources, 'source_id')
            || !$this->allTrue(
                $admission['admitted_claims'] ?? null,
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_ADMITTED_CLAIM_FIELDS,
            )
            || !$this->allUnknown(
                $admission['explicit_unknowns'] ?? null,
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_UNKNOWN_FIELDS,
            )
            || !$this->exact(
                $admission['threat_model'] ?? null,
                ProviderAssuranceEvidenceAdmissionContract::REQUIRED_THREAT_MODEL_FIELDS,
            )
            || 'TRUSTED_WRITER_CANONICAL_INTEGRITY'
                !== $admission['threat_model']['integrity_posture']
            || 'SINGLE_AUTHORITATIVE_ROOT_ONLY'
                !== $admission['threat_model']['deployment_posture']
            || true !== $admission['threat_model']['authenticated_channel_trust_only']
            || false !== $admission['threat_model']['hostile_writer_non_forgeability_claimed']
            || false !== $admission['threat_model']['distributed_execution_claimed']
            || !$this->validity($admission['validity'] ?? null)
            || 'EVIDENCE_ADMITTED_NO_EXECUTION_AUTHORITY' !== ($admission['status'] ?? null)
            || !$this->date($admission['admitted_at'] ?? null)
            || $admission['admitted_at'] !== $admission['validity']['effective_at']) {
            throw new \RuntimeException('PER220_EVIDENCE_ADMISSION_INVALID');
        }
    }

    private function common(array $record, array $fields, string $schema, string $failure): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function referencesMatch(mixed $references, array $records, string $idField): bool
    {
        if (!is_array($references) || [] === $references || count($references) !== count($records)) {
            return false;
        }
        foreach ($records as $index => $record) {
            $this->assertSource($record);
            if (!$this->matches($references[$index] ?? null, $record, $idField)) {
                return false;
            }
        }

        return true;
    }

    private function matches(mixed $reference, array $record, string $idField): bool
    {
        return $this->exact(
            $reference,
            ProviderAssuranceEvidenceAdmissionContract::REQUIRED_REFERENCE_FIELDS,
        )
            && $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function allTrue(mixed $value, array $fields): bool
    {
        return $this->exact($value, $fields) && array_fill(0, count($fields), true) === array_values($value);
    }

    private function allUnknown(mixed $value, array $fields): bool
    {
        return $this->exact($value, $fields)
            && array_fill(0, count($fields), 'UNKNOWN') === array_values($value);
    }

    private function validity(mixed $value): bool
    {
        return $this->exact($value, ProviderAssuranceEvidenceAdmissionContract::REQUIRED_VALIDITY_FIELDS)
            && $this->date($value['effective_at'])
            && $this->date($value['review_due_at'])
            && new \DateTimeImmutable($value['effective_at'])
                < new \DateTimeImmutable($value['review_due_at'])
            && null === $value['supersession_reference']
            && null === $value['revocation_reference'];
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);

        return false !== $date && $date->format(DATE_ATOM) === $value;
    }

    private function httpsUri(mixed $value): bool
    {
        return is_string($value)
            && str_starts_with($value, 'https://')
            && false !== filter_var($value, FILTER_VALIDATE_URL);
    }
}
