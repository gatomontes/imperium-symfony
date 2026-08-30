<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\AuthorityConsumptionStore;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ActivationCorridorDispositionProducer
{
    public const string DISPOSITIONS = 'var/imperium/runtime/activation-corridor-dispositions';
    private const string CONSUMER = 'imperator.activation-corridor-disposition-producer';
    private AtomicTransition $atomic; private ImmutableRecordStore $records; private AuthorityConsumptionStore $consumptions; private RecordReferenceValidator $validator;
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root) { $this->atomic = new AtomicTransition($root); $this->records = new ImmutableRecordStore($root, $this->atomic); $this->consumptions = new AuthorityConsumptionStore($this->records, $this->atomic); $this->validator = new RecordReferenceValidator($root); }

    public function decide(array $grant, array $successor, array $activation, array $authorization, array $target, array $dossier, array $eligibility, string $rationale, array $limitations, \DateTimeImmutable $at): array
    {
        $targetId = (string) ($target['target_id'] ?? 'invalid');
        return $this->atomic->run('activation-corridor-disposition:'.hash('sha256', $targetId), function () use ($grant, $successor, $activation, $authorization, $target, $dossier, $eligibility, $rationale, $limitations, $at, $targetId): array {
            $audit = (new CorridorDispositionPrincipalAuthorityRemediationTerminalAudit($this->root))->audit($grant, $successor, $activation, $authorization, $target, $dossier, $eligibility, $at);
            if ('RETURN_GATE_SATISFIED' !== $audit['classification']) throw new \RuntimeException('ACD500_REMEDIATION_RETURN_GATE_REFUSED');
            $authorityId = (string) ($authorization['result_authority_id'] ?? '');
            $authority = $this->validator->requireIntact($this->validator->read($this->root.'/'.CorridorDispositionPrincipalAuthorityRemediationProducer::CALLER_AUTHORITIES.'/'.$authorityId.'.json', 'ACD501_CALLER_AUTHORITY_ABSENT'), 'ACD502_CALLER_AUTHORITY_INVALID');
            $principal = $this->validator->resolve($this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS, $authority['principal'], 'ACD503_PRINCIPAL_ABSENT', 'ACD504_PRINCIPAL_CONFLICT', 'principal_version_id');
            $lifecycle = (new ImperatorPrincipalLifecycleReconstructionService($this->root))->reconstruct($principal['principal_version_id'], $at);
            $contracts = new ActivationCorridorDispositionContractValidator(); $contracts->assertCallerAuthority($authority); $contracts->assertTarget($target); $contracts->assertEvidenceDossier($dossier); $contracts->assertEligibility($eligibility);
            if ('ACTIVE' !== $lifecycle['effective_status'] || $authority['instance_id'] !== $target['instance_id'] || $authority['target'] !== $this->reference($target, 'target_id') || $authority['evidence_dossier'] !== $this->reference($dossier, 'dossier_id') || $authority['eligibility'] !== $this->reference($eligibility, 'eligibility_id') || $authority['proposed_disposition'] !== $eligibility['proposed_disposition'] || new \DateTimeImmutable($authority['issued_at']) > $at || new \DateTimeImmutable($authority['expires_at']) <= $at || 'ELIGIBLE' !== $eligibility['classification'] || ActivationCorridorDispositionContract::CUSTODY_REFUSAL !== $eligibility['continuing_custody_refusal'] || '' === trim($rationale) || [] === $limitations || array_filter($limitations, static fn (mixed $v): bool => !is_string($v) || '' === trim($v))) throw new \RuntimeException('ACD502_CALLER_AUTHORITY_INVALID');
            $dispositionId = 'activation-corridor-disposition-'.substr(hash('sha256', CanonicalJson::encode([$authority['instance_id'], $authority['target']])), 0, 20);
            $record = ['schema' => ActivationCorridorDispositionContract::SCHEMA, 'disposition_id' => $dispositionId, 'instance_id' => $authority['instance_id'], 'principal' => $authority['principal'], 'caller_authority' => $this->reference($authority, 'authority_id'), 'target' => $authority['target'], 'evidence_dossier' => $authority['evidence_dossier'], 'eligibility' => $authority['eligibility'], 'disposition' => $authority['proposed_disposition'], 'rationale' => trim($rationale), 'limitations' => array_values($limitations), 'consequences' => $eligibility['consequences'], 'decided_at' => $authority['issued_at'], 'terminal_custody_refusal' => ActivationCorridorDispositionContract::CUSTODY_REFUSAL, 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'binding_activated' => false, 'external_action_performed' => false, 'sealed' => true];
            $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
            if (ActivationCorridorDispositionContract::REQUIRED_FIELDS !== array_keys($record)) throw new \LogicException('ACD506_DISPOSITION_RECORD_INVALID');
            try { $existing = $this->records->read(self::DISPOSITIONS, $dispositionId); if ($existing !== $record) throw new \RuntimeException('ACD505_DISPOSITION_CONTENTION'); return $existing; } catch (\RuntimeException $error) { if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $error->getMessage()) throw $error; }
            $this->consumptions->consume($authorityId, $principal['principal_version_id'], $principal['record_digest'], self::CONSUMER, $at);
            return $this->records->put(self::DISPOSITIONS, $dispositionId, $record);
        });
    }
    private function reference(array $record, string $id): array { return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']]; }
}
