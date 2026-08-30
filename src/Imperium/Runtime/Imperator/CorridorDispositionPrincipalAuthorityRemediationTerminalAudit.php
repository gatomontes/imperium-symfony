<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CorridorDispositionPrincipalAuthorityRemediationTerminalAudit
{
    private const string CONSUMPTIONS = 'var/imperium/runtime/authority-consumptions';
    private const string CUSTODY_REFUSAL = 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE';
    private RecordReferenceValidator $records;
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) { $this->records = new RecordReferenceValidator($root); }

    public function audit(array $grant, array $successor, array $activation, array $authorization, array $target, array $dossier, array $eligibility, \DateTimeImmutable $at): array
    {
        try {
            $source = $this->resolve(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $grant['source_principal'], 'principal_version_id');
            $principal = $this->resolve(FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $grant['successor_principal'], 'principal_version_id');
            $storedSuccessor = $this->resolve(CorridorDispositionPrincipalAuthorityRemediationProducer::SCOPE_SUCCESSORS, $this->reference($successor, 'successor_transition_id'), 'successor_transition_id');
            $storedActivation = $this->resolve(ImperatorPrincipalProvenanceFixtureStore::LIFECYCLE_DISPOSITIONS, $this->reference($activation, 'disposition_id'), 'disposition_id');
            $caller = $this->resolve(CorridorDispositionPrincipalAuthorityRemediationProducer::CALLER_AUTHORITIES, ['id' => $authorization['result_authority_id'], 'digest' => $this->callerDigest($authorization, $principal), 'schema' => ActivationCorridorDispositionCallerAuthorityContract::SCHEMA], 'authority_id');
            $contracts = new CorridorDispositionPrincipalAuthorityRemediationContractValidator(); $contracts->assertScopeGrant($grant, $source, $at); $contracts->assertScopeSuccessor($storedSuccessor, $grant); $contracts->assertIssuanceAuthorization($authorization, $principal, $storedSuccessor, $storedActivation, $target, $dossier, $eligibility, $at);
            (new ActivationCorridorDispositionContractValidator())->assertCallerAuthority($caller);
            $lifecycle = (new ImperatorPrincipalLifecycleReconstructionService($this->root))->reconstruct($principal['principal_version_id'], $at);
            if ('ACTIVE' !== $lifecycle['effective_status'] || ($lifecycle['effective_disposition']['record_digest'] ?? null) !== $activation['record_digest']) throw new \RuntimeException('CPA601_EFFECTIVE_LIFECYCLE_REFUSED');
            $this->assertUniqueCurrentGeneration($principal);
            $this->assertConsumption($grant['grant_id'], $grant['record_digest'], 'imperator.corridor-scope-successor-committer');
            $this->assertConsumption($activation['disposition_id'], $activation['record_digest'], 'operator-root.corridor-scope-successor-activator');
            $this->assertConsumption($authorization['issuance_authorization_id'], $authorization['record_digest'], 'imperator.corridor-caller-authority-issuer');
            $this->assertScopeAndSecrets($source, $principal);
            if ($caller['principal'] !== $this->reference($principal, 'principal_version_id') || $caller['target'] !== $authorization['target'] || $caller['evidence_dossier'] !== $authorization['evidence_dossier'] || $caller['eligibility'] !== $authorization['eligibility'] || $caller['proposed_disposition'] !== $authorization['proposed_disposition'] || self::CUSTODY_REFUSAL !== ($authorization['custody_refusal'] ?? null) || self::CUSTODY_REFUSAL !== ($eligibility['continuing_custody_refusal'] ?? null) || true === ($target['binding_activated'] ?? false) || true === ($dossier['source_artifact_mutated'] ?? false) || true === ($eligibility['source_artifact_mutated'] ?? false)) throw new \RuntimeException('CPA602_BINDING_OR_PERIMETER_REFUSED');
            return $this->result('RETURN_GATE_SATISFIED', [], $principal, $caller, $at);
        } catch (\Throwable $error) { return $this->result('RETURN_GATE_REFUSED', [$error->getMessage()], null, null, $at); }
    }

    private function resolve(string $directory, array $reference, string $id): array { return $this->records->resolve($this->root.'/'.$directory, $reference, 'CPA610_AUDIT_RECORD_ABSENT', 'CPA611_AUDIT_RECORD_CONFLICT', $id); }
    private function assertConsumption(string $id, string $digest, string $consumer): void
    {
        $record = $this->records->requireIntact($this->records->read($this->root.'/'.self::CONSUMPTIONS.'/authority-consumption-'.hash('sha256', $id).'.json', 'CPA612_CONSUMPTION_ABSENT'), 'CPA613_CONSUMPTION_CONFLICT');
        if ($record['authority_id'] !== $id || $record['source'] !== ['id' => $id, 'digest' => $digest] || $record['consumer'] !== $consumer || true !== $record['consumed'] || false !== $record['continuing_authority']) throw new \RuntimeException('CPA613_CONSUMPTION_CONFLICT');
    }
    private function assertUniqueCurrentGeneration(array $principal): void
    {
        $matches = []; $higher = [];
        foreach (glob($this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS.'/*.json') ?: [] as $path) { $record = $this->records->requireIntact($this->records->read($path, 'CPA610_AUDIT_RECORD_ABSENT'), 'CPA611_AUDIT_RECORD_CONFLICT'); if (($record['instance_id'] ?? null) === $principal['instance_id'] && ($record['principal_id'] ?? null) === $principal['principal_id']) { if (($record['principal_generation'] ?? null) === $principal['principal_generation']) $matches[] = $record; if (($record['principal_generation'] ?? 0) > $principal['principal_generation']) $higher[] = $record; } }
        if (1 !== count($matches) || [] !== $higher || $matches[0]['record_digest'] !== $principal['record_digest']) throw new \RuntimeException('CPA614_CURRENT_GENERATION_NOT_UNIQUE');
    }
    private function assertScopeAndSecrets(array $source, array $principal): void
    {
        $expected = $source['authority_scope']; $expected['corridor_disposition_authority'] = true;
        if ($principal['authority_scope'] !== $expected || false !== $principal['authority_scope']['outbound_email_authority'] || false !== $principal['authority_scope']['credential_authority'] || false !== $principal['authority_scope']['provider_execution_authority'] || false !== $principal['credential_reference_persisted'] || false !== $principal['credential_secret_persisted'] || false !== $principal['serialized_capability_persisted']) throw new \RuntimeException('CPA615_SCOPE_OR_SECRET_EXCLUSION_REFUSED');
    }
    private function callerDigest(array $authorization, array $principal): string { $record = ['schema' => ActivationCorridorDispositionCallerAuthorityContract::SCHEMA, 'authority_id' => $authorization['result_authority_id'], 'instance_id' => $authorization['instance_id'], 'principal' => $this->reference($principal, 'principal_version_id'), 'target' => $authorization['target'], 'evidence_dossier' => $authorization['evidence_dossier'], 'eligibility' => $authorization['eligibility'], 'permitted_transition' => ActivationCorridorDispositionCallerAuthorityContract::PERMITTED_TRANSITION, 'proposed_disposition' => $authorization['proposed_disposition'], 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => $authorization['issued_at'], 'expires_at' => $authorization['expires_at'], 'consumed' => false, 'continuing_authority' => false, 'issuance_winner_required' => true, 'consumption_winner_required' => true, 'sealed' => true]; return hash('sha256', \App\Bootstrap\CanonicalJson::encode($record)); }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
    private function result(string $classification, array $reasons, ?array $principal, ?array $caller, \DateTimeImmutable $at): array { return ['schema' => 'imperium.imperator.corridor-disposition-principal-authority-remediation-terminal-audit/v1', 'classification' => $classification, 'reasons' => $reasons, 'principal' => null === $principal ? null : $this->reference($principal, 'principal_version_id'), 'caller_authority' => null === $caller ? null : $this->reference($caller, 'authority_id'), 'audited_at' => $at->format(DATE_ATOM), 'read_only' => true, 'state_repaired' => false, 'authority_created' => false, 'authority_issued' => false, 'authority_consumed' => false, 'principal_activated' => false, 'binding_activated' => false, 'disposition_selected' => false, 'disposition_sealed' => false, 'source_artifact_mutated' => false, 'credential_or_capability_handled' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => self::CUSTODY_REFUSAL]; }
}
