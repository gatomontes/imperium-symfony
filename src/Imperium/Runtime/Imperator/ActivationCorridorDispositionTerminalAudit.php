<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ActivationCorridorDispositionTerminalAudit
{
    private const string CONSUMPTIONS = 'var/imperium/runtime/authority-consumptions';
    private RecordReferenceValidator $records;
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) { $this->records = new RecordReferenceValidator($root); }

    public function audit(array $grant, array $successor, array $activation, array $authorization, array $target, array $dossier, array $eligibility, \DateTimeImmutable $auditedAt): array
    {
        try {
            $authorityId = (string) ($authorization['result_authority_id'] ?? '');
            $authority = $this->intact($this->root.'/'.CorridorDispositionPrincipalAuthorityRemediationProducer::CALLER_AUTHORITIES.'/'.$authorityId.'.json');
            $dispositionId = 'activation-corridor-disposition-'.substr(hash('sha256', CanonicalJson::encode([$authority['instance_id'], $authority['target']])), 0, 20);
            $disposition = $this->intact($this->root.'/'.ActivationCorridorDispositionProducer::DISPOSITIONS.'/'.$dispositionId.'.json');
            if (ActivationCorridorDispositionContract::REQUIRED_FIELDS !== array_keys($disposition) || ActivationCorridorDispositionContract::SCHEMA !== $disposition['schema'] || $disposition['caller_authority'] !== $this->reference($authority, 'authority_id') || $disposition['target'] !== $this->reference($target, 'target_id') || $disposition['evidence_dossier'] !== $this->reference($dossier, 'dossier_id') || $disposition['eligibility'] !== $this->reference($eligibility, 'eligibility_id') || $disposition['principal'] !== $authority['principal'] || $disposition['disposition'] !== $authority['proposed_disposition'] || $disposition['consequences'] !== $eligibility['consequences'] || !in_array($disposition['disposition'], ActivationCorridorDispositionContract::DISPOSITIONS, true) || '' === trim($disposition['rationale']) || [] === $disposition['limitations'] || array_filter($disposition['limitations'], static fn (mixed $v): bool => !is_string($v) || '' === trim($v)) || true === $disposition['source_artifact_mutated'] || true === $disposition['successor_authority_created'] || true === $disposition['binding_activated'] || true === $disposition['external_action_performed'] || ActivationCorridorDispositionContract::CUSTODY_REFUSAL !== $disposition['terminal_custody_refusal']) throw new \RuntimeException('ACD600_TERMINAL_DISPOSITION_CONFLICT');
            $contracts = new ActivationCorridorDispositionContractValidator(); $contracts->assertCallerAuthority($authority); $contracts->assertTarget($target); $contracts->assertEvidenceDossier($dossier); $contracts->assertEligibility($eligibility);
            $decisionAt = new \DateTimeImmutable($disposition['decided_at']);
            $gate = (new CorridorDispositionPrincipalAuthorityRemediationTerminalAudit($this->root))->audit($grant, $successor, $activation, $authorization, $target, $dossier, $eligibility, $decisionAt);
            if ('RETURN_GATE_SATISFIED' !== $gate['classification']) throw new \RuntimeException('ACD601_REMEDIATION_LINEAGE_REFUSED');
            $principal = $this->records->resolve($this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $disposition['principal'], 'ACD602_PRINCIPAL_ABSENT', 'ACD603_PRINCIPAL_CONFLICT', 'principal_version_id');
            $lifecycle = (new ImperatorPrincipalLifecycleReconstructionService($this->root))->reconstruct($principal['principal_version_id'], $decisionAt);
            if ('ACTIVE' !== $lifecycle['effective_status']) throw new \RuntimeException('ACD604_DECIDING_PRINCIPAL_INELIGIBLE');
            $this->assertConsumption($authorityId, $principal, $disposition);
            $this->assertOneOutcome($disposition);
            if (true === ($target['binding_activated'] ?? false) || true === ($dossier['source_artifact_mutated'] ?? false) || true === ($eligibility['source_artifact_mutated'] ?? false) || true === ($eligibility['successor_authority_created'] ?? false) || ($dossier['terminal_custody_refusal'] ?? null) !== ($target['terminal_custody_refusal'] ?? null) || ActivationCorridorDispositionContract::CUSTODY_REFUSAL !== ($eligibility['continuing_custody_refusal'] ?? null)) throw new \RuntimeException('ACD605_HISTORICAL_EVIDENCE_OR_REFUSAL_CONFLICT');
            return $this->result('TERMINAL_AUDIT_SATISFIED', [], $disposition, $auditedAt);
        } catch (\Throwable $error) { return $this->result('TERMINAL_AUDIT_REFUSED', [$error->getMessage()], null, $auditedAt); }
    }
    private function intact(string $path): array { return $this->records->requireIntact($this->records->read($path, 'ACD610_RECORD_ABSENT'), 'ACD611_RECORD_CONFLICT'); }
    private function assertConsumption(string $authorityId, array $principal, array $disposition): void { $record = $this->intact($this->root.'/'.self::CONSUMPTIONS.'/authority-consumption-'.hash('sha256', $authorityId).'.json'); if ($record['authority_id'] !== $authorityId || $record['source'] !== ['id' => $principal['principal_version_id'], 'digest' => $principal['record_digest']] || 'imperator.activation-corridor-disposition-producer' !== $record['consumer'] || true !== $record['consumed'] || false !== $record['continuing_authority'] || $disposition['caller_authority']['id'] !== $authorityId) throw new \RuntimeException('ACD612_CALLER_AUTHORITY_CONSUMPTION_CONFLICT'); }
    private function assertOneOutcome(array $disposition): void { $matches = []; foreach (glob($this->root.'/'.ActivationCorridorDispositionProducer::DISPOSITIONS.'/*.json') ?: [] as $path) { $record = $this->intact($path); if (($record['instance_id'] ?? null) === $disposition['instance_id'] && ($record['target'] ?? null) === $disposition['target']) $matches[] = $record; } if (1 !== count($matches) || $matches[0]['record_digest'] !== $disposition['record_digest']) throw new \RuntimeException('ACD613_TARGET_OUTCOME_NOT_UNIQUE'); }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
    private function result(string $classification, array $reasons, ?array $disposition, \DateTimeImmutable $at): array { return ['schema' => 'imperium.imperator.activation-corridor-disposition-terminal-audit/v1', 'classification' => $classification, 'reasons' => $reasons, 'disposition' => null === $disposition ? null : $this->reference($disposition, 'disposition_id'), 'audited_at' => $at->format(DATE_ATOM), 'read_only' => true, 'state_repaired' => false, 'authority_created' => false, 'authority_issued' => false, 'authority_consumed' => false, 'principal_activated' => false, 'binding_activated' => false, 'disposition_selected' => false, 'disposition_resealed' => false, 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'credential_or_capability_handled' => false, 'external_action_performed' => false, 'continuing_custody_refusal' => ActivationCorridorDispositionContract::CUSTODY_REFUSAL]; }
}
