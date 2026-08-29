<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ActivationTransitionInterruptionEvidenceContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationAuthorityContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class StrandedActivationArtifactDispositionService
{
    public const string DISPOSITIONS = 'var/imperium/offices/la-cortine/stranded-activation-artifact-dispositions';
    private const string TERMINAL_REFUSAL = 'docs/handoffs/provider-binding-activation-capability-custody-campaign-terminal-refusal.md';
    private RecordReferenceValidator $validator;
    private ImmutableRecordStore $records;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->validator = new RecordReferenceValidator($root);
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function quarantineExpiredAuthority(string $issuanceId, array $interruptionEvidence, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^provider-binding-activation-authority-issuance-[a-f0-9]{20}$/', $issuanceId)) throw new \InvalidArgumentException('PBI300_ISSUANCE_ID_INVALID');
        $issuance = $this->validator->read($this->root.'/'.ProviderBindingActivationIssuanceService::ISSUANCES.'/'.$issuanceId.'.json', 'PBI301_ISSUANCE_ABSENT');
        $authority = $issuance['issued_activation_authority'] ?? null;
        if (!$this->validator->isIntact($issuance) || ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_FIELDS !== array_keys($issuance) || !is_array($authority) || !$this->validator->isIntact($authority) || ProviderBindingActivationAuthorityContract::REQUIRED_FIELDS !== array_keys($authority) || false !== ($authority['consumed'] ?? null) || new \DateTimeImmutable($authority['expires_at']) > $decidedAt) throw new \RuntimeException('PBI302_AUTHORITY_NOT_EXPIRED_UNUSED');
        return $this->dispose($issuance['instance_id'], ['id' => $authority['authority_id'], 'digest' => $authority['record_digest'], 'schema' => $authority['schema']], null, $interruptionEvidence, $decidedAt);
    }

    public function quarantineExpiredLease(string $activationId, array $interruptionEvidence, \DateTimeImmutable $decidedAt): array
    {
        if (!preg_match('/^single-execution-provider-binding-activation-[a-f0-9]{20}$/', $activationId)) throw new \InvalidArgumentException('PBI303_ACTIVATION_ID_INVALID');
        $activation = $this->validator->read($this->root.'/'.SingleExecutionProviderBindingActivationService::ACTIVATIONS.'/'.$activationId.'.json', 'PBI304_ACTIVATION_ABSENT');
        if (!$this->validator->isIntact($activation) || SingleExecutionProviderBindingActivationContract::REQUIRED_FIELDS !== array_keys($activation) || 'ACTIVATED_UNCONSUMED' !== ($activation['status'] ?? null) || new \DateTimeImmutable($activation['expires_at']) > $decidedAt) throw new \RuntimeException('PBI305_LEASE_NOT_EXPIRED_UNUSED');
        return $this->dispose($activation['instance_id'], null, ['id' => $activationId, 'digest' => $activation['record_digest'], 'schema' => $activation['schema']], $interruptionEvidence, $decidedAt);
    }

    private function dispose(string $instanceId, ?array $authority, ?array $lease, array $evidence, \DateTimeImmutable $at): array
    {
        $references = $this->validateEvidence($evidence);
        $refusalPath = $this->root.'/'.self::TERMINAL_REFUSAL;
        if (!is_file($refusalPath)) throw new \RuntimeException('PBI306_TERMINAL_REFUSAL_ABSENT');
        $refusal = ['id' => basename($refusalPath), 'digest' => hash_file('sha256', $refusalPath), 'disposition' => 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE'];
        $artifact = $authority ?? $lease;
        $dispositionId = 'stranded-activation-artifact-disposition-'.substr(hash('sha256', CanonicalJson::encode([$artifact, $refusal, $references])), 0, 20);
        return $this->records->put(self::DISPOSITIONS, $dispositionId, ['schema' => StrandedActivationArtifactDispositionContract::SCHEMA, 'disposition_id' => $dispositionId, 'instance_id' => $instanceId, 'terminal_custody_refusal' => $refusal, 'activation_authority' => $authority, 'activation_lease' => $lease, 'evidence' => $references, 'disposition' => 'QUARANTINED_EXPIRED_UNUSED', 'rationale' => 'The exact artifact expired unused and the terminal custody refusal prohibits every operational successor.', 'limitations' => 'This disposition neither mutates nor revokes the source and creates no successor authority.', 'decided_at' => $at->format(DATE_ATOM), 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'sealed' => true]);
    }

    private function validateEvidence(array $evidence): array
    {
        if (6 !== count($evidence)) throw new \RuntimeException('PBI307_INTERRUPTION_EVIDENCE_INCOMPLETE');
        $coverage = $references = [];
        foreach ($evidence as $record) {
            if (!is_array($record) || !$this->validator->isIntact($record) || ActivationTransitionInterruptionEvidenceContract::REQUIRED_FIELDS !== array_keys($record) || 'CONVERGENT_RECOVERABLE' !== ($record['classification'] ?? null) || true === ($record['external_action_performed'] ?? null)) throw new \RuntimeException('PBI307_INTERRUPTION_EVIDENCE_INCOMPLETE');
            $coverage[] = $record['transition'].'|'.$record['cut'];
            $references[] = ['id' => $record['evidence_id'], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
        }
        $expected = [];
        foreach (ActivationTransitionInterruptionEvidenceContract::TRANSITIONS as $transition) foreach (ActivationTransitionInterruptionEvidenceContract::CUTS as $cut) $expected[] = $transition.'|'.$cut;
        sort($coverage); sort($expected);
        if ($coverage !== $expected) throw new \RuntimeException('PBI307_INTERRUPTION_EVIDENCE_INCOMPLETE');
        usort($references, static fn (array $a, array $b): int => $a['id'] <=> $b['id']);
        return $references;
    }
}
