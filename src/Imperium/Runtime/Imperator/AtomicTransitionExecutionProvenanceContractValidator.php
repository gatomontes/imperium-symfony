<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class AtomicTransitionExecutionProvenanceContractValidator
{
    public function assertOrigin(array $origin): void
    {
        $this->sealed(
            $origin,
            AtomicTransitionEvidenceOriginContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceOriginContract::SCHEMA,
            'PBL994_EVIDENCE_ORIGIN_INVALID',
        );

        if (!$this->identifier($origin['evidence_origin_id'] ?? null)
            || !$this->identifier($origin['experiment_id'] ?? null)
            || !$this->identifier($origin['disposable_mission_id'] ?? null)
            || !$this->identifier($origin['replay_contention_root'] ?? null)
            || !$this->reference($origin['disposable_mission_authorization'] ?? null)
            || !$this->identifier($origin['authorized_case_profile'] ?? null)
            || !$this->repository($origin['source_repository'] ?? null)
            || !$this->commit($origin['source_commit'] ?? null)
            || !$this->digest($origin['source_tree_digest'] ?? null)
            || true !== ($origin['dirty_tree_refused'] ?? null)
            || !$this->identifier($origin['build_id'] ?? null)
            || !$this->digest($origin['build_artifact_digest'] ?? null)
            || !$this->digest($origin['dependency_lock_digest'] ?? null)
            || !$this->identifier($origin['runtime_version'] ?? null)
            || !$this->identifier($origin['build_command_identity'] ?? null)
            || !$this->reference($origin['executor_principal'] ?? null)
            || !$this->digest($origin['executor_implementation_digest'] ?? null)
            || !$this->identifier($origin['executor_entry_point'] ?? null)
            || !$this->identifier($origin['execution_environment_class'] ?? null)
            || !$this->reference($origin['mission_dossier'] ?? null)
            || !$this->digest($origin['fixture_set_root'] ?? null)
            || !$this->reference($origin['recovery_plan'] ?? null)
            || !$this->digest($origin['mutation_set_root'] ?? null)
            || !$this->digest($origin['expected_result_set_root'] ?? null)
            || !$this->digest($origin['case_set_root'] ?? null)
            || !$this->identifier($origin['authoritative_evidence_root'] ?? null)
            || !$this->identifier($origin['fixture_custodian'] ?? null)
            || !$this->identifier($origin['origin_producer'] ?? null)
            || !$this->freshness($origin)
            || !in_array(
                $origin['prior_origin_disposition'] ?? null,
                ['ABSENT', 'EXACT_REPLAY_ONLY', 'CONFLICT_REFUSED'],
                true,
            )
            || AtomicTransitionEvidenceOriginContract::LIMITATIONS
                !== ($origin['limitations'] ?? null)
            || !$this->identifier($origin['sanitized_evidence_package_id'] ?? null)
            || !$this->digest($origin['sanitized_evidence_package_digest'] ?? null)
            || true !== ($origin['raw_private_evidence_excluded'] ?? null)
            || true !== ($origin['single_use'] ?? null)
            || true !== ($origin['authority_empty'] ?? null)
            || false !== ($origin['execution_performed'] ?? null)
            || false !== ($origin['operational_receipt_created'] ?? null)
            || false !== ($origin['continuing_authority'] ?? null)
            || AtomicTransitionEvidenceOriginContract::STATUS
                !== ($origin['status'] ?? null)
            || $this->containsProhibitedMaterial($origin)) {
            throw new \RuntimeException('PBL994_EVIDENCE_ORIGIN_INVALID');
        }
    }

    public function assertExecutionProvenance(array $provenance, array $origin): void
    {
        $this->assertOrigin($origin);
        $this->sealed(
            $provenance,
            AtomicTransitionExecutionProvenanceContract::REQUIRED_FIELDS,
            AtomicTransitionExecutionProvenanceContract::SCHEMA,
            'PBL995_EXECUTION_PROVENANCE_INVALID',
        );

        $joined = [
            'experiment_id', 'disposable_mission_id', 'replay_contention_root',
            'source_commit', 'source_tree_digest', 'build_id',
            'build_artifact_digest', 'dependency_lock_digest', 'runtime_version',
            'executor_principal', 'executor_implementation_digest',
            'executor_entry_point', 'execution_environment_class',
            'mission_dossier', 'fixture_set_root', 'recovery_plan',
            'mutation_set_root', 'expected_result_set_root', 'case_set_root',
            'authoritative_evidence_root', 'fixture_custodian', 'origin_producer',
            'limitations', 'sanitized_evidence_package_id',
            'sanitized_evidence_package_digest',
        ];
        foreach ($joined as $field) {
            if (($provenance[$field] ?? null) !== ($origin[$field] ?? null)) {
                throw new \RuntimeException('PBL995_EXECUTION_PROVENANCE_INVALID');
            }
        }

        if (!$this->identifier($provenance['execution_provenance_id'] ?? null)
            || ($provenance['evidence_origin'] ?? null)
                !== $this->referenceFor($origin, 'evidence_origin_id')
            || ($provenance['authorized_not_before'] ?? null)
                !== $origin['not_before']
            || ($provenance['authorized_expires_at'] ?? null)
                !== $origin['expires_at']
            || false !== ($provenance['trusted_executor_implemented'] ?? null)
            || false !== ($provenance['execution_performed'] ?? null)
            || false !== ($provenance['caller_result_accepted'] ?? null)
            || false !== ($provenance['result_produced'] ?? null)
            || false !== ($provenance['dependency_graph_derived'] ?? null)
            || false !== ($provenance['complete_chain_exclusion_proved'] ?? null)
            || false !== ($provenance['operational_receipt_created'] ?? null)
            || true !== ($provenance['authority_empty'] ?? null)
            || false !== ($provenance['continuing_authority'] ?? null)
            || AtomicTransitionExecutionProvenanceContract::STATUS
                !== ($provenance['status'] ?? null)
            || $this->containsProhibitedMaterial($provenance)) {
            throw new \RuntimeException('PBL995_EXECUTION_PROVENANCE_INVALID');
        }
    }

    private function freshness(array $origin): bool
    {
        try {
            $issued = new \DateTimeImmutable((string) ($origin['issued_at'] ?? ''));
            $notBefore = new \DateTimeImmutable((string) ($origin['not_before'] ?? ''));
            $expires = new \DateTimeImmutable((string) ($origin['expires_at'] ?? ''));

            return $issued->format(DATE_ATOM) === $origin['issued_at']
                && $notBefore->format(DATE_ATOM) === $origin['not_before']
                && $expires->format(DATE_ATOM) === $origin['expires_at']
                && $issued <= $notBefore
                && $notBefore < $expires;
        } catch (\Throwable) {
            return false;
        }
    }

    private function sealed(array $record, array $fields, string $schema, string $error): void
    {
        $digest = $record['record_digest'] ?? null;
        $plain = $record;
        unset($plain['record_digest']);
        if ($fields !== array_keys($record)
            || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null)
            || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException($error);
        }
    }

    private function referenceFor(array $record, string $id): array
    {
        return [
            'id' => $record[$id],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function reference(mixed $value): bool
    {
        return is_array($value)
            && ['id', 'digest', 'schema'] === array_keys($value)
            && $this->identifier($value['id'] ?? null)
            && $this->digest($value['digest'] ?? null)
            && $this->identifier($value['schema'] ?? null);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9][a-z0-9._:\\/-]{2,220}$/', $value);
    }

    private function repository(mixed $value): bool
    {
        return is_string($value)
            && (bool) preg_match('/^[a-z0-9._-]+\/[a-z0-9._-]+$/', $value);
    }

    private function commit(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{40}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function containsProhibitedMaterial(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        foreach ($value as $key => $child) {
            if (is_string($key) && (bool) preg_match(
                '/(?:secret|password|api[_-]?key|access[_-]?token|credential_(?:value|reference)|capability_(?:identity|bytes|token)|environment_(?:value|variable)|callback_identity|object_identity)/i',
                $key,
            )) {
                return true;
            }
            if (is_object($child) || is_resource($child) || is_callable($child)
                || $this->containsProhibitedMaterial($child)) {
                return true;
            }
        }

        return false;
    }
}
