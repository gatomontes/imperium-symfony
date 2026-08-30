<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Evidence\CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration;

/** Pure reconstruction of caller-supplied offline evidence. No persistence dependency by design. */
final readonly class CorridorDispositionPrincipalAuthorityRemediationReadOnlyAggregateReconstructionService
{
    private const array REQUIRED = ['source_principal', 'scope_grant', 'scope_successor', 'issuer_principal', 'activation_disposition', 'target', 'evidence_dossier', 'eligibility', 'issuance_authorization', 'interruption_evidence'];
    private const string CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';

    public function reconstruct(array $evidence, \DateTimeImmutable $at): array
    {
        $missing = array_values(array_filter(self::REQUIRED, static fn (string $key): bool => !array_key_exists($key, $evidence)));
        if ([] !== $missing) return $this->result($evidence, 'INCOMPLETE', array_map(static fn (string $key): string => 'EVIDENCE_ABSENT:'.$key, $missing), [], [], $at);

        $grant = $evidence['scope_grant']; $authorization = $evidence['issuance_authorization']; $issuer = $evidence['issuer_principal'];
        if (!is_array($grant) || !is_array($authorization) || !is_array($issuer)) return $this->result($evidence, 'CONFLICTED', ['REMEDIATION_RECORD_TYPE_CONFLICT'], [], [], $at);
        if (null !== ($grant['revocation'] ?? null) || null !== ($authorization['revocation'] ?? null)) return $this->result($evidence, 'REFUSED', ['AUTHORITY_REVOKED'], [], [], $at);
        if (!$this->active($grant, $at) || !$this->active($authorization, $at)) return $this->result($evidence, 'REFUSED', ['AUTHORITY_EXPIRED_OR_NOT_YET_EFFECTIVE'], [], [], $at);
        if ('ACTIVE' !== ($issuer['status'] ?? null) || true !== ($issuer['authority_scope']['corridor_disposition_authority'] ?? null)) return $this->result($evidence, 'REFUSED', ['COMPETENT_ACTIVE_PRINCIPAL_INELIGIBLE'], [], [], $at);
        if (self::CUSTODY_REFUSAL !== ($authorization['custody_refusal'] ?? null) || self::CUSTODY_REFUSAL !== ($evidence['eligibility']['continuing_custody_refusal'] ?? null)) return $this->result($evidence, 'REFUSED', ['CONTINUING_CUSTODY_REFUSAL_MISMATCH'], [], [], $at);

        [$coverage, $coverageError] = $this->coverage($evidence['interruption_evidence']);
        if (null !== $coverageError) return $this->result($evidence, $coverageError[0], [$coverageError[1]], [], $coverage, $at);

        try {
            $validator = new CorridorDispositionPrincipalAuthorityRemediationContractValidator();
            $validator->assertScopeGrant($grant, $evidence['source_principal'], $at);
            $validator->assertScopeSuccessor($evidence['scope_successor'], $grant);
            $validator->assertIssuanceAuthorization($authorization, $issuer, $evidence['scope_successor'], $evidence['activation_disposition'], $evidence['target'], $evidence['evidence_dossier'], $evidence['eligibility'], $at);
        } catch (\Throwable $error) {
            return $this->result($evidence, 'CONFLICTED', ['EXACT_FIXTURE_VALIDATION_CONFLICT:'.$error->getMessage()], [], $coverage, $at);
        }

        $references = [];
        foreach (['scope_grant' => 'grant_id', 'scope_successor' => 'successor_transition_id', 'issuer_principal' => 'principal_version_id', 'activation_disposition' => 'disposition_id', 'target' => 'target_id', 'evidence_dossier' => 'dossier_id', 'eligibility' => 'eligibility_id', 'issuance_authorization' => 'issuance_authorization_id'] as $key => $id) $references[$key] = $this->reference($evidence[$key], $id);
        return $this->result($evidence, 'ELIGIBLE', ['COMPLETE_EXACT_OFFLINE_REMEDIATION_BASIS'], $references, $coverage, $at);
    }

    private function coverage(mixed $records): array
    {
        if (!is_array($records)) return [[], ['INCOMPLETE', 'INTERRUPTION_EVIDENCE_ABSENT']];
        $coverage = [];
        foreach ($records as $record) {
            if (!is_array($record) || 'CONVERGENT_RECOVERABLE' !== ($record['classification'] ?? null) || true !== ($record['recovery']['read_only'] ?? null) || false !== ($record['live_authority_issued_or_consumed'] ?? null) || false !== ($record['live_principal_or_binding_activated'] ?? null) || false !== ($record['activation_artifact_mutated'] ?? null) || false !== ($record['external_action_performed'] ?? null) || self::CUSTODY_REFUSAL !== ($record['continuing_custody_refusal'] ?? null)) return [$coverage, ['CONFLICTED', 'INTERRUPTION_EVIDENCE_SEMANTIC_CONFLICT']];
            $coverage[] = ($record['transition'] ?? '').'|'.($record['cut'] ?? '');
        }
        $expected = [];
        foreach (CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration::TRANSITIONS as $transition) foreach (CorridorDispositionPrincipalAuthorityRemediationInterruptionDemonstration::CUTS as $cut) $expected[] = $transition.'|'.$cut;
        sort($coverage); sort($expected);
        if (count($coverage) < count($expected)) return [$coverage, ['INCOMPLETE', 'INTERRUPTION_EVIDENCE_INCOMPLETE']];
        if ($coverage !== $expected) return [$coverage, ['CONFLICTED', 'INTERRUPTION_EVIDENCE_COVERAGE_CONFLICT']];
        return [$coverage, null];
    }

    private function active(array $record, \DateTimeImmutable $at): bool { try { return $at >= new \DateTimeImmutable($record['issued_at'] ?? '') && $at < new \DateTimeImmutable($record['expires_at'] ?? ''); } catch (\Throwable) { return false; } }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
    private function result(array $evidence, string $classification, array $reasons, array $references, array $coverage, \DateTimeImmutable $at): array
    {
        return ['schema' => CorridorDispositionPrincipalAuthorityRemediationAggregateResultContract::SCHEMA, 'instance_id' => is_array($evidence['target'] ?? null) ? ($evidence['target']['instance_id'] ?? null) : null, 'classification' => $classification, 'reasons' => $reasons, 'references' => $references, 'interruption_coverage' => $coverage, 'reconstructed_at' => $at->format(DATE_ATOM), 'read_only' => true, 'authority_created' => false, 'authority_issued' => false, 'authority_consumed' => false, 'principal_created' => false, 'principal_activated' => false, 'binding_activated' => false, 'caller_authority_created' => false, 'disposition_selected' => false, 'disposition_sealed' => false, 'source_artifact_mutated' => false, 'continuing_custody_refusal' => self::CUSTODY_REFUSAL, 'external_action_performed' => false];
    }
}
