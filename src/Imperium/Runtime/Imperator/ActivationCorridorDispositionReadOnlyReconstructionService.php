<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use App\Imperium\Runtime\Clavium\CredentialReferenceExposureObservationContract;
use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityContract;
use App\Imperium\Runtime\Clavium\ProcessLossCapabilityCustodyEvidenceContract;
use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\StrandedActivationArtifactDispositionContract;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ActivationCorridorDispositionReadOnlyReconstructionService
{
    private RecordReferenceValidator $records;
    private ActivationCorridorDispositionContractValidator $contracts;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root)
    {
        $this->records = new RecordReferenceValidator($root);
        $this->contracts = new ActivationCorridorDispositionContractValidator();
    }

    public function reconstruct(array $target, string $proposedDisposition, string $principalVersionId, array $evidence, \DateTimeImmutable $at): array
    {
        $this->contracts->assertTarget($target);
        if (!in_array($proposedDisposition, ActivationCorridorDispositionEligibilityContract::DISPOSITIONS, true)
            || !preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $principalVersionId)) {
            throw new \InvalidArgumentException('ACD300_RECONSTRUCTION_INPUT_INVALID');
        }

        try {
            $reconstruction = (new ImperatorPrincipalLifecycleReconstructionService($this->root))->reconstruct($principalVersionId, $at);
            $principal = $reconstruction['principal_version'];
        } catch (\RuntimeException $error) {
            return $this->result($target, $proposedDisposition, null, 'REFUSED', ['COMPETENT_ACTIVE_PRINCIPAL_ABSENT'], [], $at);
        }
        if (($principal['instance_id'] ?? null) !== $target['instance_id']
            || 'ACTIVE' !== ($reconstruction['effective_status'] ?? null)
            || true !== ($principal['authority_scope']['corridor_disposition_authority'] ?? null)
            || false !== ($principal['credential_reference_persisted'] ?? null)
            || false !== ($principal['credential_secret_persisted'] ?? null)
            || false !== ($principal['serialized_capability_persisted'] ?? null)) {
            return $this->result($target, $proposedDisposition, $principal, 'REFUSED', ['COMPETENT_ACTIVE_PRINCIPAL_INELIGIBLE'], [], $at);
        }

        $required = ['activation_decision', 'activation_authority_issuance', 'activation_lease', 'transition_interruption_evidence', 'stranded_artifact_dispositions', 'custody_assessment', 'process_loss_custody_evidence', 'credential_secret_exclusion_evidence'];
        $missing = array_values(array_filter($required, static fn (string $name): bool => !array_key_exists($name, $evidence)));
        if ([] !== $missing) return $this->result($target, $proposedDisposition, $principal, 'INCOMPLETE', array_map(static fn (string $name): string => 'EVIDENCE_ABSENT:'.$name, $missing), [], $at);

        try {
            $references = $this->validateEvidence($target, $principal, $evidence);
        } catch (\RuntimeException $error) {
            $classification = str_starts_with($error->getMessage(), 'ACD31_REFUSAL') ? 'REFUSED' : 'CONFLICTED';
            return $this->result($target, $proposedDisposition, $principal, $classification, [$error->getMessage()], [], $at);
        }

        return $this->result($target, $proposedDisposition, $principal, 'ELIGIBLE', ['COMPLETE_EXACT_EVIDENCE_BASIS'], $references, $at);
    }

    private function validateEvidence(array $target, array $principal, array $evidence): array
    {
        $decision = $this->intact($evidence['activation_decision'], ProviderBindingActivationIssuanceContract::REQUIRED_DECISION_FIELDS, ProviderBindingActivationIssuanceContract::DECISION_SCHEMA, 'decision_id');
        $issuance = $this->intact($evidence['activation_authority_issuance'], ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_FIELDS, ProviderBindingActivationIssuanceContract::ISSUANCE_SCHEMA, 'issuance_id');
        $activation = $this->intact($evidence['activation_lease'], SingleExecutionProviderBindingActivationContract::REQUIRED_FIELDS, SingleExecutionProviderBindingActivationContract::SCHEMA, 'activation_id');
        $custody = $this->intact($evidence['custody_assessment'], CrossProcessCapabilityCustodyFeasibilityContract::REQUIRED_FIELDS, CrossProcessCapabilityCustodyFeasibilityContract::SCHEMA, 'assessment_id');
        $processLoss = $this->intact($evidence['process_loss_custody_evidence'], ProcessLossCapabilityCustodyEvidenceContract::REQUIRED_FIELDS, ProcessLossCapabilityCustodyEvidenceContract::SCHEMA, 'evidence_id');
        $exclusion = $this->intact($evidence['credential_secret_exclusion_evidence'], CredentialReferenceExposureObservationContract::REQUIRED_FIELDS, CredentialReferenceExposureObservationContract::SCHEMA, 'observation_id');
        $records = [$decision, $issuance, $activation, $custody, $processLoss, $exclusion];
        foreach ($records as $record) if (($record['instance_id'] ?? null) !== $target['instance_id']) throw new \RuntimeException('ACD310_EVIDENCE_INSTANCE_CONFLICT');

        $interruptions = $evidence['transition_interruption_evidence'];
        if (!is_array($interruptions) || 6 !== count($interruptions)) throw new \RuntimeException('ACD310_INTERRUPTION_EVIDENCE_CONFLICT');
        $coverage = [];
        foreach ($interruptions as $record) {
            $record = $this->intact($record, ActivationTransitionInterruptionEvidenceContract::REQUIRED_FIELDS, ActivationTransitionInterruptionEvidenceContract::SCHEMA, 'evidence_id');
            if (($record['instance_id'] ?? null) !== $target['instance_id'] || 'CONVERGENT_RECOVERABLE' !== ($record['classification'] ?? null) || false !== ($record['external_action_performed'] ?? null)) throw new \RuntimeException('ACD310_INTERRUPTION_EVIDENCE_CONFLICT');
            $coverage[] = ($record['transition'] ?? '').'|'.($record['cut'] ?? '');
        }
        $expected = [];
        foreach (ActivationTransitionInterruptionEvidenceContract::TRANSITIONS as $transition) foreach (ActivationTransitionInterruptionEvidenceContract::CUTS as $cut) $expected[] = $transition.'|'.$cut;
        sort($coverage); sort($expected);
        if ($coverage !== $expected) throw new \RuntimeException('ACD310_INTERRUPTION_EVIDENCE_CONFLICT');

        $stranded = $evidence['stranded_artifact_dispositions'];
        if (!is_array($stranded) || [] === $stranded) throw new \RuntimeException('ACD310_STRANDED_EVIDENCE_CONFLICT');
        foreach ($stranded as $record) {
            $record = $this->intact($record, StrandedActivationArtifactDispositionContract::REQUIRED_FIELDS, StrandedActivationArtifactDispositionContract::SCHEMA, 'disposition_id');
            if (($record['instance_id'] ?? null) !== $target['instance_id'] || 'QUARANTINED_EXPIRED_UNUSED' !== ($record['disposition'] ?? null) || true === ($record['source_artifact_mutated'] ?? null) || true === ($record['successor_authority_created'] ?? null)) throw new \RuntimeException('ACD310_STRANDED_EVIDENCE_CONFLICT');
        }

        if ('AUTHORIZED' !== ($decision['disposition'] ?? null) || false !== ($decision['external_action_performed'] ?? null)
            || true !== ($issuance['authority_issued'] ?? null) || true === ($issuance['provider_binding_activated'] ?? null) || true === ($issuance['credential_capability_issued'] ?? null) || true === ($issuance['external_action_performed'] ?? null)
            || !in_array($activation['status'] ?? null, SingleExecutionProviderBindingActivationContract::STATUSES, true)
            || CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL !== ($custody['disposition'] ?? null)
            || true === ($custody['capability_issued'] ?? null) || true === ($custody['capability_reconstructed'] ?? null) || true === ($custody['external_action_performed'] ?? null)
            || 'POSSESSION_LOST' !== ($processLoss['classification'] ?? null) || true === ($processLoss['capability_reconstructed'] ?? null) || true === ($processLoss['credential_resolved'] ?? null) || true === ($processLoss['external_action_performed'] ?? null)
            || 'EXCLUDED' !== ($exclusion['classification'] ?? null) || true === ($exclusion['credential_reference_observed'] ?? null) || true === ($exclusion['credential_secret_observed'] ?? null)) {
            throw new \RuntimeException('ACD310_EVIDENCE_SEMANTIC_CONFLICT');
        }
        if (!$this->matchesReference($issuance['source_decision'] ?? null, $decision, 'decision_id')
            || !$this->matchesReference($custody['source_activation'] ?? null, $activation, 'activation_id')
            || !$this->matchesReference($processLoss['source_activation'] ?? null, $activation, 'activation_id')
            || !$this->matchesReference($target['terminal_custody_refusal'], $custody, 'assessment_id')) {
            throw new \RuntimeException('ACD31_REFUSAL_CUSTODY_OR_LINEAGE_MISMATCH');
        }

        $references = [
            'principal' => $this->reference($principal, 'principal_version_id'),
            'activation_decision' => $this->reference($decision, 'decision_id'),
            'activation_authority_issuance' => $this->reference($issuance, 'issuance_id'),
            'activation_lease' => $this->reference($activation, 'activation_id'),
            'custody_assessment' => $this->reference($custody, 'assessment_id'),
            'process_loss_custody_evidence' => $this->reference($processLoss, 'evidence_id'),
            'credential_secret_exclusion_evidence' => $this->reference($exclusion, 'observation_id'),
            'transition_interruption_evidence_count' => 6,
            'stranded_artifact_disposition_count' => count($stranded),
        ];
        return $references;
    }

    private function intact(mixed $record, array $fields, string $schema, string $idField): array
    {
        if (!is_array($record) || $fields !== array_keys($record) || $schema !== ($record['schema'] ?? null)
            || !is_string($record[$idField] ?? null) || true !== ($record['sealed'] ?? null) || !$this->records->isIntact($record)) {
            throw new \RuntimeException('ACD310_EVIDENCE_RECORD_CONFLICT');
        }
        return $record;
    }

    private function matchesReference(mixed $reference, array $record, string $idField): bool
    {
        return is_array($reference) && ($reference['id'] ?? null) === $record[$idField]
            && ($reference['digest'] ?? null) === $record['record_digest']
            && (!isset($reference['schema']) || $reference['schema'] === $record['schema']);
    }

    private function reference(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private function result(array $target, string $candidate, ?array $principal, string $classification, array $reasons, array $evidence, \DateTimeImmutable $at): array
    {
        return [
            'schema' => ActivationCorridorDispositionReconstructionResultContract::SCHEMA,
            'instance_id' => $target['instance_id'],
            'target' => ['id' => $target['target_id'], 'digest' => $target['record_digest'], 'schema' => $target['schema']],
            'proposed_disposition' => $candidate,
            'principal' => null === $principal ? null : $this->reference($principal, 'principal_version_id'),
            'classification' => $classification,
            'reasons' => $reasons,
            'evidence' => $evidence,
            'reconstructed_at' => $at->format(DATE_ATOM),
            'read_only' => true,
            'authority_created' => false,
            'authority_issued' => false,
            'authority_consumed' => false,
            'disposition_selected' => false,
            'disposition_sealed' => false,
            'source_artifact_mutated' => false,
            'successor_authority_created' => false,
            'continuing_custody_refusal' => ActivationCorridorDispositionEligibilityContract::CONTINUING_CUSTODY_REFUSAL,
            'external_action_performed' => false,
        ];
    }
}
