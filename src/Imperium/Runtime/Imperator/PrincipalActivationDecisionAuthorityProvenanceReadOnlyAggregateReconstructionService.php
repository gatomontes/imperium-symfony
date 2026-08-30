<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Evidence\PrincipalActivationDecisionAuthorityProvenanceRemediationInterruptionDemonstration as Interruption;

/** Pure reconstruction of caller-supplied offline evidence. No persistence dependency. */
final readonly class PrincipalActivationDecisionAuthorityProvenanceReadOnlyAggregateReconstructionService
{
    private const array REQUIRED = [
        'source_principal',
        'scope_grant',
        'scope_successor',
        'activation_disposition',
        'principal_attestation',
        'provider_assurance_admission',
        'execution_boundary',
        'issuance_authorization',
        'interruption_evidence',
    ];

    public function reconstruct(array $evidence, \DateTimeImmutable $at): array
    {
        $missing = array_values(array_filter(
            self::REQUIRED,
            static fn (string $key): bool => !array_key_exists($key, $evidence),
        ));
        if ([] !== $missing) {
            return $this->result(
                $evidence,
                'INCOMPLETE',
                array_map(
                    static fn (string $key): string => 'EVIDENCE_ABSENT:'.$key,
                    $missing,
                ),
                [],
                [],
                $at,
            );
        }

        foreach (self::REQUIRED as $key) {
            if (!is_array($evidence[$key])) {
                return $this->result(
                    $evidence,
                    'CONFLICTED',
                    ['EVIDENCE_RECORD_TYPE_CONFLICT:'.$key],
                    [],
                    [],
                    $at,
                );
            }
        }

        $grant = $evidence['scope_grant'];
        $authorization = $evidence['issuance_authorization'];
        if (null !== ($grant['revocation'] ?? null)
            || null !== ($authorization['revocation'] ?? null)) {
            return $this->result(
                $evidence,
                'REFUSED',
                ['AUTHORITY_REVOKED'],
                [],
                [],
                $at,
            );
        }
        if (true === ($grant['consumed'] ?? null)
            || true === ($authorization['consumed'] ?? null)) {
            return $this->result(
                $evidence,
                'REFUSED',
                ['AUTHORITY_ALREADY_CONSUMED'],
                [],
                [],
                $at,
            );
        }
        if (!$this->active($grant, $at) || !$this->active($authorization, $at)) {
            return $this->result(
                $evidence,
                'REFUSED',
                ['AUTHORITY_EXPIRED_OR_NOT_YET_EFFECTIVE'],
                [],
                [],
                $at,
            );
        }

        $activation = $evidence['activation_disposition'];
        if ('PENDING_ACTIVATION' !== ($activation['source_status'] ?? null)
            || 'ACTIVATE' !== ($activation['disposition'] ?? null)
            || true !== ($activation['caller_authority_issuance_permitted_after_effective_at'] ?? null)
            || false !== ($activation['authority_scope_changed'] ?? null)
            || false !== ($activation['external_action_performed'] ?? null)
            || !$this->effective($activation['effective_at'] ?? null, $at)) {
            return $this->result(
                $evidence,
                'REFUSED',
                ['SUCCESSOR_LIFECYCLE_INELIGIBLE'],
                [],
                [],
                $at,
            );
        }

        [$coverage, $coverageError] = $this->coverage($evidence['interruption_evidence']);
        if (null !== $coverageError) {
            return $this->result(
                $evidence,
                $coverageError[0],
                [$coverageError[1]],
                [],
                $coverage,
                $at,
            );
        }

        try {
            $validator = new PrincipalActivationDecisionAuthorityProvenanceRemediationContractValidator();
            $validator->assertScopeGrant(
                $grant,
                $evidence['source_principal'],
                $at,
            );
            $validator->assertScopeSuccessor(
                $evidence['scope_successor'],
                $grant,
            );
            $validator->assertIssuanceAuthorization(
                $authorization,
                $evidence['scope_successor'],
                $activation,
                $evidence['principal_attestation'],
                $evidence['provider_assurance_admission'],
                $evidence['execution_boundary'],
                $at,
            );
        } catch (\Throwable $error) {
            return $this->result(
                $evidence,
                'CONFLICTED',
                ['EXACT_FIXTURE_VALIDATION_CONFLICT:'.$error->getMessage()],
                [],
                $coverage,
                $at,
            );
        }

        $references = [];
        foreach ([
            'scope_grant' => 'grant_id',
            'scope_successor' => 'successor_transition_id',
            'activation_disposition' => 'disposition_id',
            'principal_attestation' => 'principal_attestation_id',
            'provider_assurance_admission' => 'admission_id',
            'execution_boundary' => 'boundary_id',
            'issuance_authorization' => 'issuance_authorization_id',
        ] as $key => $idField) {
            $references[$key] = $this->reference($evidence[$key], $idField);
        }

        return $this->result(
            $evidence,
            'ELIGIBLE',
            ['COMPLETE_EXACT_OFFLINE_DECISION_AUTHORITY_PROVENANCE_BASIS'],
            $references,
            $coverage,
            $at,
        );
    }

    private function coverage(array $records): array
    {
        $coverage = [];
        foreach ($records as $record) {
            if (!is_array($record)
                || 'CONVERGENT_RECOVERABLE' !== ($record['classification'] ?? null)
                || true !== ($record['recovery']['read_only'] ?? null)
                || false !== ($record['recovery']['repair_performed'] ?? null)
                || false !== ($record['authority_issued_or_consumed'] ?? null)
                || false !== ($record['principal_or_binding_activated'] ?? null)
                || false !== ($record['credential_or_capability_handled'] ?? null)
                || false !== ($record['provider_invoked'] ?? null)
                || false !== ($record['external_action_performed'] ?? null)) {
                return [
                    $coverage,
                    ['CONFLICTED', 'INTERRUPTION_EVIDENCE_SEMANTIC_CONFLICT'],
                ];
            }
            $coverage[] = ($record['fixture_path'] ?? '').'|'.($record['cut'] ?? '');
        }

        $expected = [];
        foreach (Interruption::FIXTURE_PATHS as $fixturePath) {
            foreach (Interruption::CUTS as $cut) {
                $expected[] = $fixturePath.'|'.$cut;
            }
        }
        sort($coverage);
        sort($expected);

        if (count($coverage) < count($expected)) {
            return [
                $coverage,
                ['INCOMPLETE', 'INTERRUPTION_EVIDENCE_INCOMPLETE'],
            ];
        }
        if ($coverage !== $expected) {
            return [
                $coverage,
                ['CONFLICTED', 'INTERRUPTION_EVIDENCE_COVERAGE_CONFLICT'],
            ];
        }

        return [$coverage, null];
    }

    private function active(array $record, \DateTimeImmutable $at): bool
    {
        try {
            return $at >= new \DateTimeImmutable($record['issued_at'] ?? '')
                && $at < new \DateTimeImmutable($record['expires_at'] ?? '');
        } catch (\Throwable) {
            return false;
        }
    }

    private function effective(mixed $effectiveAt, \DateTimeImmutable $at): bool
    {
        try {
            return is_string($effectiveAt)
                && new \DateTimeImmutable($effectiveAt) <= $at;
        } catch (\Throwable) {
            return false;
        }
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function result(
        array $evidence,
        string $classification,
        array $reasons,
        array $references,
        array $coverage,
        \DateTimeImmutable $at,
    ): array {
        return [
            'schema' => PrincipalActivationDecisionAuthorityProvenanceAggregateResultContract::SCHEMA,
            'instance_id' => is_array($evidence['scope_grant'] ?? null)
                ? ($evidence['scope_grant']['instance_id'] ?? null)
                : null,
            'classification' => $classification,
            'reasons' => $reasons,
            'references' => $references,
            'interruption_coverage' => $coverage,
            'reconstructed_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'record_created' => false,
            'record_repaired' => false,
            'scope_granted' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'principal_created' => false,
            'principal_activated' => false,
            'binding_activated' => false,
            'activation_decision_created' => false,
            'source_artifact_mutated' => false,
            'credential_or_capability_handled' => false,
            'provider_invoked' => false,
            'external_action_performed' => false,
        ];
    }
}
