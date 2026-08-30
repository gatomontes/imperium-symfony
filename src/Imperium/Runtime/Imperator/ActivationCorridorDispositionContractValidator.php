<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class ActivationCorridorDispositionContractValidator
{
    public function assertTarget(array $record): void
    {
        $this->common($record, ActivationCorridorDispositionTargetContract::REQUIRED_FIELDS, ActivationCorridorDispositionTargetContract::SCHEMA, 'ACD200_TARGET_INVALID');
        $scope = $record['scope'] ?? null;
        if (!$this->identifier($record['target_id'] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || !$this->identifier($record['corridor_id'] ?? null)
            || !is_int($record['corridor_generation'] ?? null) || $record['corridor_generation'] < 1
            || !$this->reference($record['terminal_custody_refusal'] ?? null)
            || !$this->reference($record['source_campaign'] ?? null)
            || !$this->exact($scope, ActivationCorridorDispositionTargetContract::REQUIRED_SCOPE_FIELDS)
            || 'provider-binding-activation' !== ($scope['provider_binding_activation_corridor'] ?? null)
            || !$this->digest($scope['activation_artifact_set_digest'] ?? null)
            || !$this->digest($scope['historical_evidence_set_digest'] ?? null)
            || !$this->date($record['identified_at'] ?? null)
            || false !== ($record['authority_created'] ?? null)
            || false !== ($record['binding_activated'] ?? null)) {
            throw new \RuntimeException('ACD200_TARGET_INVALID');
        }
    }

    public function assertEvidenceDossier(array $record): void
    {
        $this->common($record, ActivationCorridorDispositionEvidenceDossierContract::REQUIRED_FIELDS, ActivationCorridorDispositionEvidenceDossierContract::SCHEMA, 'ACD210_DOSSIER_INVALID');
        $cuts = $record['transition_interruption_evidence'] ?? null;
        $stranded = $record['stranded_artifact_dispositions'] ?? null;
        if (!$this->identifier($record['dossier_id'] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || !$this->reference($record['target'] ?? null)
            || !$this->reference($record['active_principal'] ?? null)
            || !$this->reference($record['activation_decision'] ?? null)
            || !$this->reference($record['activation_authority'] ?? null)
            || !$this->reference($record['activation_lease'] ?? null)
            || !$this->referenceList($cuts, ActivationCorridorDispositionEvidenceDossierContract::REQUIRED_INTERRUPTION_EVIDENCE_COUNT, true)
            || !$this->referenceList($stranded, 1, false)
            || !$this->reference($record['process_loss_custody_evidence'] ?? null)
            || !$this->reference($record['credential_secret_exclusion_evidence'] ?? null)
            || !$this->reference($record['terminal_custody_refusal'] ?? null)
            || 'COMPLETE' !== ($record['completeness'] ?? null)
            || [] !== ($record['conflicts'] ?? null)
            || !$this->date($record['assembled_at'] ?? null)
            || true !== ($record['read_only'] ?? null)
            || false !== ($record['authority_created'] ?? null)
            || false !== ($record['disposition_sealed'] ?? null)
            || false !== ($record['source_artifact_mutated'] ?? null)) {
            throw new \RuntimeException('ACD210_DOSSIER_INVALID');
        }
    }

    public function assertEligibility(array $record): void
    {
        $this->common($record, ActivationCorridorDispositionEligibilityContract::REQUIRED_FIELDS, ActivationCorridorDispositionEligibilityContract::SCHEMA, 'ACD220_ELIGIBILITY_INVALID');
        $candidate = $record['proposed_disposition'] ?? null;
        $predicates = $record['predicates'] ?? null;
        $consequences = $record['consequences'] ?? null;
        $requiredConsequences = 'QUARANTINED_PENDING_REMEDIATION' === $candidate
            ? ActivationCorridorDispositionEligibilityContract::REQUIRED_QUARANTINE_CONSEQUENCE_FIELDS
            : ActivationCorridorDispositionEligibilityContract::REQUIRED_RETIREMENT_CONSEQUENCE_FIELDS;
        if (!$this->identifier($record['eligibility_id'] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || !$this->reference($record['target'] ?? null)
            || !$this->reference($record['evidence_dossier'] ?? null)
            || !$this->reference($record['principal'] ?? null)
            || !in_array($candidate, ActivationCorridorDispositionEligibilityContract::DISPOSITIONS, true)
            || !$this->allTrue($predicates, ActivationCorridorDispositionEligibilityContract::REQUIRED_PREDICATE_FIELDS)
            || !$this->exact($consequences, $requiredConsequences)
            || false !== ($consequences['corridor_operationally_usable'] ?? null)
            || true !== ($consequences['historical_evidence_readable'] ?? null)
            || true !== ($consequences['terminal_custody_refusal_authoritative'] ?? null)
            || ('QUARANTINED_PENDING_REMEDIATION' === $candidate
                && (false !== ($consequences['remediation_authority_created'] ?? null)
                    || true !== ($consequences['future_reconsideration_requires_new_authority'] ?? null)))
            || ('RETIRE_CORRIDOR' === $candidate
                && (true !== ($consequences['retirement_irreversible'] ?? null)
                    || true !== ($consequences['replacement_corridor_requires_new_authority'] ?? null)
                    || true !== ($consequences['outstanding_artifacts_create_no_authority'] ?? null)))
            || 'ELIGIBLE' !== ($record['classification'] ?? null)
            || !is_array($record['reasons'] ?? null) || [] === $record['reasons']
            || array_filter($record['reasons'], static fn (mixed $reason): bool => !is_string($reason) || '' === trim($reason))
            || !$this->date($record['assessed_at'] ?? null)
            || false !== ($record['authority_created'] ?? null)
            || false !== ($record['disposition_sealed'] ?? null)
            || false !== ($record['source_artifact_mutated'] ?? null)
            || false !== ($record['successor_authority_created'] ?? null)
            || ActivationCorridorDispositionEligibilityContract::CONTINUING_CUSTODY_REFUSAL !== ($record['continuing_custody_refusal'] ?? null)) {
            throw new \RuntimeException('ACD220_ELIGIBILITY_INVALID');
        }
    }

    public function assertCallerAuthority(array $record): void
    {
        $this->common($record, ActivationCorridorDispositionCallerAuthorityContract::REQUIRED_FIELDS, ActivationCorridorDispositionCallerAuthorityContract::SCHEMA, 'ACD230_CALLER_AUTHORITY_INVALID');
        if (!$this->identifier($record['authority_id'] ?? null)
            || !$this->identifier($record['instance_id'] ?? null)
            || !$this->reference($record['principal'] ?? null)
            || !$this->reference($record['target'] ?? null)
            || !$this->reference($record['evidence_dossier'] ?? null)
            || !$this->reference($record['eligibility'] ?? null)
            || ActivationCorridorDispositionCallerAuthorityContract::PERMITTED_TRANSITION !== ($record['permitted_transition'] ?? null)
            || !in_array($record['proposed_disposition'] ?? null, ActivationCorridorDispositionEligibilityContract::DISPOSITIONS, true)
            || true !== ($record['authority_single_use'] ?? null)
            || true !== ($record['authority_exercisable'] ?? null)
            || !$this->timeWindow($record['issued_at'] ?? null, $record['expires_at'] ?? null, 15)
            || false !== ($record['consumed'] ?? null)
            || false !== ($record['continuing_authority'] ?? null)
            || true !== ($record['issuance_winner_required'] ?? null)
            || true !== ($record['consumption_winner_required'] ?? null)) {
            throw new \RuntimeException('ACD230_CALLER_AUTHORITY_INVALID');
        }
    }

    public function assertAuthorityBasis(array $authority, array $principal, array $target, array $dossier, array $eligibility, \DateTimeImmutable $at): void
    {
        $this->assertCallerAuthority($authority);
        $this->assertTarget($target);
        $this->assertEvidenceDossier($dossier);
        $this->assertEligibility($eligibility);
        $this->common($principal, ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, 'ACD240_PRINCIPAL_INVALID');
        $lifecycle = $principal['lifecycle'] ?? null;
        if (!$this->identifier($principal['principal_version_id'] ?? null)
            || !$this->identifier($principal['principal_id'] ?? null)
            || !$this->identifier($principal['instance_id'] ?? null)
            || !$this->identifier($principal['binding_id'] ?? null)
            || !is_int($principal['principal_generation'] ?? null) || $principal['principal_generation'] < 1
            || !in_array($principal['constitution_route'] ?? null, ImperatorPrincipalConstitutionAuthorityContract::ROUTES, true)
            || !$this->reference($principal['source_constitution_authority'] ?? null)
            || !$this->reference($principal['source_operator_root'] ?? null)
            || !$this->exact($principal['identity'] ?? null, ImperatorRuntimePrincipalVersionContract::REQUIRED_IDENTITY_FIELDS)
            || !$this->identifier($principal['identity']['operator_id'] ?? null)
            || !$this->digest($principal['identity']['operator_identity_digest'] ?? null)
            || !$this->identifier($principal['identity']['imperator_subject_id'] ?? null)
            || !$this->digest($principal['identity']['imperator_subject_digest'] ?? null)
            || !$this->exact($principal['authority_scope'] ?? null, ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS)
            || $authority['instance_id'] !== ($principal['instance_id'] ?? null)
            || $authority['instance_id'] !== $target['instance_id']
            || $authority['instance_id'] !== $dossier['instance_id']
            || $authority['instance_id'] !== $eligibility['instance_id']
            || !$this->matches($authority['principal'], $principal, 'principal_version_id')
            || !$this->matches($authority['target'], $target, 'target_id')
            || !$this->matches($authority['evidence_dossier'], $dossier, 'dossier_id')
            || !$this->matches($authority['eligibility'], $eligibility, 'eligibility_id')
            || !$this->matches($dossier['target'], $target, 'target_id')
            || !$this->matches($dossier['active_principal'], $principal, 'principal_version_id')
            || !$this->matches($eligibility['target'], $target, 'target_id')
            || !$this->matches($eligibility['evidence_dossier'], $dossier, 'dossier_id')
            || !$this->matches($eligibility['principal'], $principal, 'principal_version_id')
            || $authority['proposed_disposition'] !== $eligibility['proposed_disposition']
            || !in_array($principal['status'] ?? null, ['PENDING_ACTIVATION', 'ACTIVE'], true)
            || true !== ($principal['authority_scope']['corridor_disposition_authority'] ?? null)
            || false !== ($principal['authority_scope']['outbound_email_authority'] ?? null)
            || false !== ($principal['authority_scope']['credential_authority'] ?? null)
            || false !== ($principal['authority_scope']['provider_execution_authority'] ?? null)
            || false !== ($principal['credential_reference_persisted'] ?? null)
            || false !== ($principal['credential_secret_persisted'] ?? null)
            || false !== ($principal['serialized_capability_persisted'] ?? null)
            || !$this->exact($lifecycle, ImperatorRuntimePrincipalVersionContract::REQUIRED_LIFECYCLE_FIELDS)
            || !$this->date($lifecycle['effective_at'] ?? null)
            || !$this->date($lifecycle['expires_at'] ?? null)
            || !$this->date($lifecycle['constituted_at'] ?? null)
            || null !== ($lifecycle['superseding_version'] ?? null)
            || null !== ($lifecycle['current_disposition'] ?? null)
            || new \DateTimeImmutable($lifecycle['constituted_at']) > new \DateTimeImmutable($lifecycle['effective_at'])
            || new \DateTimeImmutable($lifecycle['effective_at']) > $at
            || new \DateTimeImmutable($lifecycle['expires_at']) <= $at
            || new \DateTimeImmutable($authority['issued_at']) > $at
            || new \DateTimeImmutable($authority['expires_at']) <= $at
            || new \DateTimeImmutable($authority['expires_at']) > new \DateTimeImmutable($lifecycle['expires_at'])) {
            throw new \RuntimeException('ACD240_PRINCIPAL_OR_BASIS_INVALID');
        }
    }

    private function common(array $record, array $fields, string $schema, string $failure): void
    {
        $digest = $record['record_digest'] ?? null;
        $unsealed = $record;
        unset($unsealed['record_digest']);
        if ($fields !== array_keys($record) || $schema !== ($record['schema'] ?? null)
            || true !== ($record['sealed'] ?? null) || !$this->digest($digest)
            || !hash_equals($digest, hash('sha256', CanonicalJson::encode($unsealed)))) {
            throw new \RuntimeException($failure);
        }
    }

    private function reference(mixed $value): bool
    {
        return $this->exact($value, ActivationCorridorDispositionCallerAuthorityContract::REQUIRED_REFERENCE_FIELDS)
            && $this->identifier($value['id']) && $this->digest($value['digest']) && $this->identifier($value['schema']);
    }

    private function referenceList(mixed $records, int $minimum, bool $exactCount): bool
    {
        if (!is_array($records) || ($exactCount ? count($records) !== $minimum : count($records) < $minimum)) return false;
        $ids = [];
        foreach ($records as $record) {
            if (!$this->reference($record)) return false;
            $ids[] = $record['id'];
        }
        return count($ids) === count(array_unique($ids));
    }

    private function matches(array $reference, array $record, string $idField): bool
    {
        return $reference['id'] === ($record[$idField] ?? null)
            && $reference['digest'] === ($record['record_digest'] ?? null)
            && $reference['schema'] === ($record['schema'] ?? null);
    }

    private function allTrue(mixed $value, array $fields): bool
    {
        if (!$this->exact($value, $fields)) return false;
        foreach ($value as $predicate) if (true !== $predicate) return false;
        return true;
    }

    private function timeWindow(mixed $start, mixed $end, int $minutes): bool
    {
        if (!$this->date($start) || !$this->date($end)) return false;
        $issued = new \DateTimeImmutable($start);
        $expires = new \DateTimeImmutable($end);
        return $issued < $expires && $expires <= $issued->modify('+'.$minutes.' minutes');
    }

    private function exact(mixed $value, array $fields): bool
    {
        return is_array($value) && $fields === array_keys($value);
    }

    private function identifier(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }

    private function digest(mixed $value): bool
    {
        return is_string($value) && (bool) preg_match('/^[a-f0-9]{64}$/', $value);
    }

    private function date(mixed $value): bool
    {
        if (!is_string($value)) return false;
        $date = \DateTimeImmutable::createFromFormat(DATE_ATOM, $value);
        return false !== $date && $date->format(DATE_ATOM) === $value;
    }
}
