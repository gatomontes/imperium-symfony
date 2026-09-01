<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract;

/** Pure structural and recursive content inspection of the complete evidence chain. */
final class AtomicTransitionCompleteChainExclusionService
{
    private const array SECTION_SCHEMAS = [
        'evidence_origin' => [AtomicTransitionEvidenceOriginContract::SCHEMA],
        'execution_provenance' => [AtomicTransitionExecutionProvenanceContract::SCHEMA],
        'fixtures' => [AtomicTransitionEvidenceFixtureContract::SCHEMA],
        'recovery_plans' => [ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::SCHEMA],
        'mutations' => [AtomicTransitionEvidenceMutationContract::SCHEMA],
        'cases' => [AtomicTransitionEvidenceAdversarialCaseContract::SCHEMA],
        'expectations' => [AtomicTransitionEvidenceExpectedResultContract::SCHEMA],
        'results' => [AtomicTransitionProvenanceBoundCaseResultContract::SCHEMA],
        'dependency_graph' => [AtomicTransitionExecutorDependencyCapabilityGraphContract::SCHEMA],
        'aggregates' => [AtomicTransitionEvidenceAggregateAuditReceiptContract::SCHEMA],
        'exceptions' => [AtomicTransitionSanitizedExceptionEvidenceContract::SCHEMA],
        'closure_material' => [AtomicTransitionEvidenceCorrectedClosureContract::SCHEMA],
    ];

    private const array ATTACKS = [
        'SENSITIVE_KEY' => ['payload' => ['access_token' => 'opaque']],
        'CREDENTIAL_VALUE' => ['payload' => 'Bearer forbidden-secret'],
        'NESTED_BASE64_CREDENTIAL' => ['payload' => 'UW1WaGNtVnlJR1p2Y21KcFpHUmxiaTF6WldOeVpYUT0='],
        'BASE64URL_CREDENTIAL' => ['payload' => 'QmVhcmVyIGZvcmJpZGRlbi1zZWNyZXQ'],
        'HEX_CREDENTIAL' => ['payload' => '42656172657220666f7262696464656e2d736563726574'],
        'PERCENT_CREDENTIAL' => ['payload' => 'Bearer%20forbidden-secret'],
        'JSON_STRING_CREDENTIAL' => ['payload' => '"Bearer forbidden-secret"'],
        'SPLIT_CREDENTIAL_VALUE' => ['payload' => ['Bearer ', 'forbidden-secret']],
        'PROCESS_LOCAL_CAPABILITY_VALUE' => ['payload' => 'process-local-capability://provider/1'],
        'PROCESS_LOCAL_OBJECT' => ['payload' => 'stdClass-object-vector'],
        'PROCESS_LOCAL_RESOURCE' => ['payload' => 'resource(17) of type (stream)'],
        'EXCEPTION_SECRET' => ['message' => 'failure: ghp_forbiddenSecretValue'],
    ];

    public function prove(string $proofId, array $chain): array
    {
        if (!preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $proofId)
            || AtomicTransitionCompleteChainExclusionProofContract::SECTIONS !== array_keys($chain)) {
            throw new \RuntimeException('PBL1010_COMPLETE_CHAIN_SHAPE_INVALID');
        }

        $digests = [];
        foreach ($chain as $section => $artifacts) {
            if (!is_array($artifacts) || ([] === $artifacts && 'exceptions' !== $section)) {
                throw new \RuntimeException('PBL1011_COMPLETE_CHAIN_SECTION_INCOMPLETE:'.$section);
            }
            foreach ($artifacts as $artifact) {
                $this->assertStructurallyAllowed($section, $artifact);
                $this->assertClean($artifact);
                $digests[] = $artifact['record_digest'];
            }
        }

        $attackDigests = [];
        $refusalCodes = [];
        foreach (self::ATTACKS as $kind => $attack) {
            $attackDigests[] = hash('sha256', CanonicalJson::encode($attack));
            $candidate = 'PROCESS_LOCAL_OBJECT' === $kind
                ? ['payload' => new \stdClass()]
                : $attack;
            try {
                $this->assertClean($candidate);
                throw new \RuntimeException('PBL1012_COMPLETE_CHAIN_ATTACK_NOT_REFUSED:'.$kind);
            } catch (\RuntimeException $error) {
                if ('PBL1013_SECRET_OR_PROCESS_LOCAL_CAPABILITY_REFUSED' !== $error->getMessage()) {
                    throw $error;
                }
                $refusalCodes[] = $error->getMessage();
            }
        }

        return $this->seal([
            'schema' => AtomicTransitionCompleteChainExclusionProofContract::SCHEMA,
            'proof_id' => $proofId,
            'evidence_origin_reference' => $this->reference($chain['evidence_origin'][0], 'evidence_origin_id'),
            'execution_provenance_reference' => $this->reference($chain['execution_provenance'][0], 'execution_provenance_id'),
            'dependency_graph_reference' => $this->reference($chain['dependency_graph'][0], 'graph_id'),
            'scanned_sections' => array_keys($chain),
            'scanned_artifact_count' => count($digests),
            'scanned_artifact_digests' => $digests,
            'structural_allowlist_digest' => hash('sha256', CanonicalJson::encode(self::SECTION_SCHEMAS)),
            'normalizations_applied' => AtomicTransitionCompleteChainExclusionProofContract::NORMALIZATIONS,
            'attack_vector_kinds' => array_keys(self::ATTACKS),
            'attack_vector_digests' => $attackDigests,
            'derived_refusal_codes' => $refusalCodes,
            'all_sections_complete' => true,
            'all_artifacts_structurally_allowed' => true,
            'all_artifacts_clean' => true,
            'all_attacks_refused' => true,
            'value_aware' => true,
            'encoding_aware' => true,
            'split_value_aware' => true,
            'exception_aware' => true,
            'read_only' => true,
            'runtime_state_written' => false,
            'authority_issued_or_consumed' => false,
            'execution_admitted' => false,
            'provider_effect_started' => false,
            'continuing_authority' => false,
            'status' => AtomicTransitionCompleteChainExclusionProofContract::STATUS,
            'sealed' => true,
        ]);
    }

    public function assertClean(mixed $value, ?string $key = null): void
    {
        if (null !== $key && preg_match(
            '/^(?:secret|token|access[_-]?token|password|api[_-]?key|'
            .'credential(?:[_-]?(?:value|reference))?|'
            .'capability[_-]?(?:object|identity)|callback[_-]?identity|'
            .'object[_-]?id)$/i',
            $key,
        )) {
            $this->refuse();
        }
        if (is_object($value) || is_resource($value) || is_callable($value)) {
            $this->refuse();
        }
        if (is_array($value)) {
            $fragments = [];
            foreach ($value as $childKey => $child) {
                $this->assertClean($child, is_string($childKey) ? $childKey : null);
                if (is_string($child)) {
                    $fragments[] = $child;
                }
            }
            if (count($fragments) > 1) {
                $this->assertStringClean(implode('', $fragments));
            }
            return;
        }
        if (is_string($value)) {
            $this->assertStringClean($value);
        }
    }

    private function assertStringClean(string $value, int $depth = 0): void
    {
        if ($this->prohibitedValue($value)) {
            $this->refuse();
        }
        if ($depth >= 3 || '' === $value) {
            return;
        }

        $decoded = [];
        $base64 = base64_decode($value, true);
        if (false !== $base64 && $base64 !== $value && '' !== $base64) {
            $decoded[] = $base64;
        }
        if (preg_match('/^[A-Za-z0-9_-]{8,}$/', $value)) {
            $padded = strtr($value, '-_', '+/');
            $padded .= str_repeat('=', (4 - strlen($padded) % 4) % 4);
            $base64Url = base64_decode($padded, true);
            if (false !== $base64Url && $base64Url !== $value && '' !== $base64Url) {
                $decoded[] = $base64Url;
            }
        }
        if (strlen($value) % 2 === 0 && preg_match('/^[a-f0-9]{16,}$/i', $value)) {
            $hex = hex2bin($value);
            if (false !== $hex) {
                $decoded[] = $hex;
            }
        }
        $percent = rawurldecode($value);
        if ($percent !== $value) {
            $decoded[] = $percent;
        }
        try {
            $json = json_decode($value, true, 8, JSON_THROW_ON_ERROR);
            if (is_string($json) && $json !== $value) {
                $decoded[] = $json;
            }
        } catch (\JsonException) {
        }
        foreach (array_unique($decoded) as $candidate) {
            $this->assertStringClean($candidate, $depth + 1);
        }
    }

    private function prohibitedValue(string $value): bool
    {
        return (bool) preg_match(
            '/(?:^|[\s:{])Bearer\s+\S+|^sk-[A-Za-z0-9_-]+|'
            .'^gh[pousr]_[A-Za-z0-9]+|^AKIA[A-Z0-9]{12,}|'
            .'-----BEGIN [A-Z ]+PRIVATE KEY-----|'
            .'process-local-(?:credential|capability):\/\/|'
            .'resource\(\d+\)|object\([A-Za-z_\\\\]|spl_object_id|\x00/',
            $value,
        );
    }

    private function assertStructurallyAllowed(string $section, mixed $artifact): void
    {
        if (!is_array($artifact) || !isset($artifact['schema'])
            || !in_array($artifact['schema'], self::SECTION_SCHEMAS[$section], true)) {
            throw new \RuntimeException('PBL1014_COMPLETE_CHAIN_SCHEMA_REFUSED:'.$section);
        }
        $fields = $this->fieldsFor($artifact['schema']);
        $plain = $artifact;
        $digest = $plain['record_digest'] ?? null;
        unset($plain['record_digest']);
        if ($fields !== array_keys($artifact) || true !== ($artifact['sealed'] ?? null)
            || !is_string($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($plain)))) {
            throw new \RuntimeException('PBL1015_COMPLETE_CHAIN_STRUCTURE_REFUSED:'.$section);
        }
    }

    private function fieldsFor(string $schema): array
    {
        return match ($schema) {
            AtomicTransitionEvidenceOriginContract::SCHEMA => AtomicTransitionEvidenceOriginContract::REQUIRED_FIELDS,
            AtomicTransitionExecutionProvenanceContract::SCHEMA => AtomicTransitionExecutionProvenanceContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceFixtureContract::SCHEMA => AtomicTransitionEvidenceFixtureContract::REQUIRED_FIELDS,
            ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::SCHEMA =>
                ProviderBindingSuccessorAtomicLiveTransitionRecoveryPlanContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceMutationContract::SCHEMA => AtomicTransitionEvidenceMutationContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceAdversarialCaseContract::SCHEMA => AtomicTransitionEvidenceAdversarialCaseContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceExpectedResultContract::SCHEMA => AtomicTransitionEvidenceExpectedResultContract::REQUIRED_FIELDS,
            AtomicTransitionProvenanceBoundCaseResultContract::SCHEMA => AtomicTransitionProvenanceBoundCaseResultContract::REQUIRED_FIELDS,
            AtomicTransitionExecutorDependencyCapabilityGraphContract::SCHEMA => AtomicTransitionExecutorDependencyCapabilityGraphContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceAggregateAuditReceiptContract::SCHEMA => AtomicTransitionEvidenceAggregateAuditReceiptContract::REQUIRED_FIELDS,
            AtomicTransitionSanitizedExceptionEvidenceContract::SCHEMA => AtomicTransitionSanitizedExceptionEvidenceContract::REQUIRED_FIELDS,
            AtomicTransitionEvidenceCorrectedClosureContract::SCHEMA => AtomicTransitionEvidenceCorrectedClosureContract::REQUIRED_FIELDS,
            default => throw new \RuntimeException('PBL1014_COMPLETE_CHAIN_SCHEMA_REFUSED'),
        };
    }

    private function reference(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function refuse(): never
    {
        throw new \RuntimeException('PBL1013_SECRET_OR_PROCESS_LOCAL_CAPABILITY_REFUSED');
    }

    private function seal(array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));

        return $record;
    }
}
