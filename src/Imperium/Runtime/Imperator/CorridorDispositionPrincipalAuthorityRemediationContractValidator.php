<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;

final class CorridorDispositionPrincipalAuthorityRemediationContractValidator
{
    public function assertScopeGrant(array $grant, array $sourcePrincipal, \DateTimeImmutable $at): void
    {
        $this->common($grant, ImperatorCorridorDispositionScopeGrantContract::REQUIRED_FIELDS, ImperatorCorridorDispositionScopeGrantContract::SCHEMA, 'CPA200_SCOPE_GRANT_INVALID');
        $this->principal($sourcePrincipal, 'CPA201_SOURCE_PRINCIPAL_INVALID');
        $source = $grant['source_principal'] ?? null; $successor = $grant['successor_principal'] ?? null;
        if (!$this->identifier($grant['grant_id'] ?? null) || !$this->identifier($grant['instance_id'] ?? null)
            || !$this->operatorRoot($grant['operator_root'] ?? null) || !$this->principalReference($source)
            || !$this->principalReference($successor) || $source['id'] === $successor['id']
            || $source['generation'] + 1 !== $successor['generation']
            || !$this->matches($source, $sourcePrincipal, 'principal_version_id')
            || $grant['instance_id'] !== $sourcePrincipal['instance_id']
            || 'ACTIVE' !== $sourcePrincipal['status'] || true === $sourcePrincipal['authority_scope']['corridor_disposition_authority']
            || !$this->exact($grant['scope_delta'] ?? null, ImperatorCorridorDispositionScopeGrantContract::REQUIRED_SCOPE_DELTA_FIELDS)
            || true !== $grant['scope_delta']['corridor_disposition_authority']
            || !$this->scopePreserved($grant['preserved_scope'] ?? null, $sourcePrincipal['authority_scope'])
            || ImperatorCorridorDispositionScopeGrantContract::PERMITTED_TRANSITION !== ($grant['permitted_transition'] ?? null)
            || !is_string($grant['rationale'] ?? null) || '' === trim($grant['rationale'])
            || true !== ($grant['authority_single_use'] ?? null) || true !== ($grant['authority_exercisable'] ?? null)
            || true !== ($grant['issuance_winner_required'] ?? null) || true !== ($grant['consumption_winner_required'] ?? null)
            || !$this->activeWindow($grant['issued_at'] ?? null, $grant['expires_at'] ?? null, $at)
            || null !== ($grant['revocation'] ?? null) || false !== ($grant['consumed'] ?? null)
            || false !== ($grant['continuing_authority'] ?? null)) throw new \RuntimeException('CPA200_SCOPE_GRANT_INVALID');
    }

    public function assertScopeSuccessor(array $transition, array $grant): void
    {
        $this->common($transition, ImperatorCorridorDispositionScopeSuccessorContract::REQUIRED_FIELDS, ImperatorCorridorDispositionScopeSuccessorContract::SCHEMA, 'CPA210_SCOPE_SUCCESSOR_INVALID');
        $source = $transition['source_principal'] ?? null; $successor = $transition['successor_principal'] ?? null;
        if (!$this->identifier($transition['successor_transition_id'] ?? null) || !$this->identifier($transition['instance_id'] ?? null)
            || !$this->reference($transition['scope_grant'] ?? null) || !$this->matches($transition['scope_grant'], $grant, 'grant_id')
            || !$this->principalReference($source) || !$this->principalReference($successor)
            || $source !== $grant['source_principal'] || $successor !== $grant['successor_principal']
            || $transition['instance_id'] !== $grant['instance_id']
            || $source['generation'] !== ($transition['source_generation'] ?? null)
            || $successor['generation'] !== ($transition['successor_generation'] ?? null)
            || $transition['source_generation'] + 1 !== $transition['successor_generation']
            || true !== ($transition['identity_preserved'] ?? null) || true !== ($transition['binding_preserved'] ?? null)
            || ($transition['scope_delta'] ?? null) !== $grant['scope_delta'] || ($transition['preserved_scope'] ?? null) !== $grant['preserved_scope']
            || ImperatorCorridorDispositionScopeSuccessorContract::INITIAL_STATUS !== ($transition['initial_status'] ?? null)
            || true !== ($transition['activation_required'] ?? null) || true !== ($transition['separate_activation_authority_required'] ?? null)
            || true !== ($transition['transition_winner_required'] ?? null) || !$this->date($transition['committed_at'] ?? null)
            || true !== ($transition['grant_consumed'] ?? null) || false !== ($transition['source_principal_mutated'] ?? null)
            || false !== ($transition['source_principal_superseded'] ?? null) || false !== ($transition['caller_authority_issued'] ?? null)
            || false !== ($transition['continuing_authority'] ?? null)) throw new \RuntimeException('CPA210_SCOPE_SUCCESSOR_INVALID');
    }

    public function assertIssuanceAuthorization(array $authorization, array $issuerPrincipal, array $successor, array $activationDisposition, array $target, array $dossier, array $eligibility, \DateTimeImmutable $at): void
    {
        $this->common($authorization, ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::REQUIRED_FIELDS, ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::SCHEMA, 'CPA220_ISSUANCE_AUTHORIZATION_INVALID');
        $this->principal($issuerPrincipal, 'CPA221_ISSUER_PRINCIPAL_INVALID');
        $this->common($successor, ImperatorCorridorDispositionScopeSuccessorContract::REQUIRED_FIELDS, ImperatorCorridorDispositionScopeSuccessorContract::SCHEMA, 'CPA222_SCOPE_SUCCESSOR_INVALID');
        $this->common($activationDisposition, ImperatorPrincipalLifecycleDispositionContract::REQUIRED_FIELDS, ImperatorPrincipalLifecycleDispositionContract::SCHEMA, 'CPA223_ACTIVATION_DISPOSITION_INVALID');
        $basis = new ActivationCorridorDispositionContractValidator();
        $basis->assertTarget($target); $basis->assertEvidenceDossier($dossier); $basis->assertEligibility($eligibility);
        $lifecycle = $issuerPrincipal['lifecycle'] ?? null;
        if (!$this->identifier($authorization['issuance_authorization_id'] ?? null) || !$this->identifier($authorization['instance_id'] ?? null)
            || !$this->principalReference($authorization['issuer_principal'] ?? null)
            || !$this->matches($authorization['issuer_principal'], $issuerPrincipal, 'principal_version_id')
            || !$this->reference($authorization['scope_successor'] ?? null) || !$this->matches($authorization['scope_successor'], $successor, 'successor_transition_id')
            || !$this->reference($authorization['activation_disposition'] ?? null) || !$this->matches($authorization['activation_disposition'], $activationDisposition, 'disposition_id')
            || !$this->reference($authorization['target'] ?? null) || !$this->matches($authorization['target'], $target, 'target_id')
            || !$this->reference($authorization['evidence_dossier'] ?? null) || !$this->matches($authorization['evidence_dossier'], $dossier, 'dossier_id')
            || !$this->reference($authorization['eligibility'] ?? null) || !$this->matches($authorization['eligibility'], $eligibility, 'eligibility_id')
            || $authorization['instance_id'] !== $issuerPrincipal['instance_id'] || $authorization['instance_id'] !== ($successor['instance_id'] ?? null)
            || $authorization['instance_id'] !== ($target['instance_id'] ?? null) || $authorization['instance_id'] !== ($dossier['instance_id'] ?? null) || $authorization['instance_id'] !== ($eligibility['instance_id'] ?? null)
            || $authorization['issuer_principal']['generation'] !== $successor['successor_generation']
            || !$this->matches($successor['successor_principal'] ?? [], $issuerPrincipal, 'principal_version_id')
            || !in_array($issuerPrincipal['status'], ['PENDING_ACTIVATION', 'ACTIVE'], true) || true !== $issuerPrincipal['authority_scope']['corridor_disposition_authority']
            || false !== $issuerPrincipal['credential_reference_persisted'] || false !== $issuerPrincipal['credential_secret_persisted'] || false !== $issuerPrincipal['serialized_capability_persisted']
            || !$this->exact($lifecycle, ImperatorRuntimePrincipalVersionContract::REQUIRED_LIFECYCLE_FIELDS)
            || !$this->date($lifecycle['effective_at'] ?? null) || !$this->date($lifecycle['expires_at'] ?? null)
            || new \DateTimeImmutable($lifecycle['effective_at']) > $at || new \DateTimeImmutable($lifecycle['expires_at']) <= $at
            || null !== ($lifecycle['superseding_version'] ?? null) || null !== ($lifecycle['current_disposition'] ?? null)
            || !$this->activeWindow($authorization['issued_at'] ?? null, $authorization['expires_at'] ?? null, $at)
            || new \DateTimeImmutable($authorization['expires_at']) > new \DateTimeImmutable($lifecycle['expires_at'])
            || ImperatorCorridorDispositionScopeSuccessorContract::INITIAL_STATUS !== ($successor['initial_status'] ?? null)
            || true !== ($successor['activation_required'] ?? null) || true !== ($successor['separate_activation_authority_required'] ?? null)
            || 'PENDING_ACTIVATION' !== ($activationDisposition['source_status'] ?? null) || 'ACTIVATE' !== ($activationDisposition['disposition'] ?? null)
            || false !== ($activationDisposition['authority_scope_changed'] ?? null) || true !== ($activationDisposition['historical_attribution_preserved'] ?? null)
            || true !== ($activationDisposition['caller_authority_issuance_permitted_after_effective_at'] ?? null) || false !== ($activationDisposition['external_action_performed'] ?? null)
            || !$this->matches($activationDisposition['source_principal_version'] ?? [], $issuerPrincipal, 'principal_version_id')
            || !in_array($authorization['proposed_disposition'] ?? null, ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::DISPOSITIONS, true)
            || $authorization['proposed_disposition'] !== ($eligibility['proposed_disposition'] ?? null)
            || !$this->identifier($authorization['result_authority_id'] ?? null)
            || ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::PERMITTED_TRANSITION !== ($authorization['permitted_transition'] ?? null)
            || true !== ($authorization['authority_single_use'] ?? null) || true !== ($authorization['authority_exercisable'] ?? null)
            || true !== ($authorization['issuance_winner_required'] ?? null) || true !== ($authorization['consumption_winner_required'] ?? null)
            || null !== ($authorization['revocation'] ?? null) || false !== ($authorization['consumed'] ?? null) || false !== ($authorization['continuing_authority'] ?? null)
            || ActivationCorridorDispositionCallerAuthorityIssuanceAuthorizationContract::CONTINUING_CUSTODY_REFUSAL !== ($authorization['custody_refusal'] ?? null)) throw new \RuntimeException('CPA220_ISSUANCE_AUTHORIZATION_INVALID');
    }

    private function principal(array $record, string $failure): void
    {
        $this->common($record, ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, $failure);
        if (!$this->identifier($record['principal_version_id'] ?? null) || !$this->identifier($record['instance_id'] ?? null)
            || !is_int($record['principal_generation'] ?? null) || $record['principal_generation'] < 1
            || !$this->exact($record['authority_scope'] ?? null, ImperatorRuntimePrincipalVersionContract::REQUIRED_AUTHORITY_SCOPE_FIELDS)) throw new \RuntimeException($failure);
    }

    private function common(array $record, array $fields, string $schema, string $failure): void
    { $digest=$record['record_digest']??null;$plain=$record;unset($plain['record_digest']);if($fields!==array_keys($record)||$schema!==($record['schema']??null)||true!==($record['sealed']??null)||!$this->digest($digest)||!hash_equals($digest,hash('sha256',CanonicalJson::encode($plain))))throw new \RuntimeException($failure); }
    private function operatorRoot(mixed $v): bool { return $this->exact($v, ImperatorCorridorDispositionScopeGrantContract::REQUIRED_OPERATOR_ROOT_FIELDS)&&$this->identifier($v['operator_id'])&&$this->digest($v['source_identity_digest'])&&$this->identifier($v['decision_id'])&&$this->digest($v['decision_digest']); }
    private function principalReference(mixed $v): bool { return $this->exact($v, ImperatorCorridorDispositionScopeGrantContract::REQUIRED_PRINCIPAL_FIELDS)&&$this->identifier($v['id'])&&$this->digest($v['digest'])&&ImperatorRuntimePrincipalVersionContract::SCHEMA===$v['schema']&&is_int($v['generation'])&&$v['generation']>0; }
    private function reference(mixed $v): bool { return $this->exact($v, ImperatorCorridorDispositionScopeGrantContract::REQUIRED_REFERENCE_FIELDS)&&$this->identifier($v['id'])&&$this->digest($v['digest'])&&$this->identifier($v['schema']); }
    private function matches(array $r,array $v,string $id): bool { return ($r['id']??null)===($v[$id]??null)&&($r['digest']??null)===($v['record_digest']??null)&&($r['schema']??null)===($v['schema']??null); }
    private function scopePreserved(mixed $v,array $source): bool { if(!$this->exact($v,ImperatorCorridorDispositionScopeGrantContract::REQUIRED_PRESERVED_SCOPE_FIELDS))return false;foreach($v as $k=>$value)if($value!==$source[$k])return false;return true; }
    private function activeWindow(mixed $a,mixed $b,\DateTimeImmutable $at): bool { if(!$this->date($a)||!$this->date($b))return false;$start=new \DateTimeImmutable($a);$end=new \DateTimeImmutable($b);return $start<=$at&&$at<$end&&$end<=$start->modify('+15 minutes'); }
    private function exact(mixed $v,array $fields): bool { return is_array($v)&&$fields===array_keys($v); }
    private function identifier(mixed $v): bool { return is_string($v)&&(bool)preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/',$v); }
    private function digest(mixed $v): bool { return is_string($v)&&(bool)preg_match('/^[a-f0-9]{64}$/',$v); }
    private function date(mixed $v): bool { if(!is_string($v))return false;$d=\DateTimeImmutable::createFromFormat(DATE_ATOM,$v);return false!==$d&&$d->format(DATE_ATOM)===$v; }
}
