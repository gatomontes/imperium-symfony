<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Clavium\CredentialReferenceExposureObservationContract;
use App\Imperium\Runtime\Clavium\CrossProcessCapabilityCustodyFeasibilityContract;
use App\Imperium\Runtime\Clavium\ProcessLossCapabilityCustodyEvidenceContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionEligibilityContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionReadOnlyReconstructionService;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionReconstructionResultContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionTargetContract;
use App\Imperium\Runtime\Imperator\ActivationTransitionInterruptionEvidenceContract;
use App\Imperium\Runtime\Imperator\FutureInstanceImperatorPrincipalConstitutionService;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use App\Imperium\Runtime\Imperator\ProviderBindingActivationIssuanceContract;
use App\Imperium\Runtime\LaCortine\SingleExecutionProviderBindingActivationContract;
use App\Imperium\Runtime\LaCortine\StrandedActivationArtifactDispositionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationBatch3Test extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-corridor-reconstruction-batch3-'.bin2hex(random_bytes(6));
        mkdir($this->root, 0770, true);
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) return;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        rmdir($this->root);
    }

    public function testMissingOrIneligiblePrincipalAlwaysRefuses(): void
    {
        [$target] = $this->basis(false);
        $service = new ActivationCorridorDispositionReadOnlyReconstructionService($this->root);
        $absent = $service->reconstruct($target, 'RETIRE_CORRIDOR', 'absent-principal-version', [], $this->at());
        self::assertSame('REFUSED', $absent['classification']);
        self::assertContains('COMPETENT_ACTIVE_PRINCIPAL_ABSENT', $absent['reasons']);

        $principal = $this->principal(false);
        $this->writePrincipal($principal);
        $ineligible = $service->reconstruct($target, 'RETIRE_CORRIDOR', $principal['principal_version_id'], [], $this->at());
        self::assertSame('REFUSED', $ineligible['classification']);
        self::assertContains('COMPETENT_ACTIVE_PRINCIPAL_INELIGIBLE', $ineligible['reasons']);
    }

    public function testReadOnlyReconstructionClassifiesIncompleteConflictedAndEligible(): void
    {
        [$target, $evidence] = $this->basis(true);
        $principal = $this->principal(true);
        $this->writePrincipal($principal);
        $service = new ActivationCorridorDispositionReadOnlyReconstructionService($this->root);

        $incomplete = $evidence;
        unset($incomplete['credential_secret_exclusion_evidence']);
        self::assertSame('INCOMPLETE', $service->reconstruct($target, 'RETIRE_CORRIDOR', $principal['principal_version_id'], $incomplete, $this->at())['classification']);

        $conflicted = $evidence;
        $conflicted['activation_decision']['rationale'] = 'Altered without resealing.';
        self::assertSame('CONFLICTED', $service->reconstruct($target, 'RETIRE_CORRIDOR', $principal['principal_version_id'], $conflicted, $this->at())['classification']);

        $eligible = $service->reconstruct($target, 'RETIRE_CORRIDOR', $principal['principal_version_id'], $evidence, $this->at());
        self::assertSame(ActivationCorridorDispositionReconstructionResultContract::REQUIRED_FIELDS, array_keys($eligible));
        self::assertSame('ELIGIBLE', $eligible['classification']);
        foreach (['read_only' => true, 'authority_created' => false, 'authority_issued' => false, 'authority_consumed' => false, 'disposition_selected' => false, 'disposition_sealed' => false, 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'external_action_performed' => false] as $field => $value) self::assertSame($value, $eligible[$field]);
        self::assertSame(ActivationCorridorDispositionEligibilityContract::CONTINUING_CUSTODY_REFUSAL, $eligible['continuing_custody_refusal']);
        foreach (ActivationCorridorDispositionReconstructionResultContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
    }

    public function testDocumentationAuthorizesOfflineDispositionInterruptionEvidenceOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $document = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-binding-activation-corridor-disposition-read-only-reconstruction.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-batch-3-complete.md'));
        foreach (['BATCH_3_READ_ONLY_RECONSTRUCTION_AND_REFUSAL_CLASSIFICATION_COMPLETE', 'ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED', 'writes no record', 'does not prove that production can create', 'No caller authority is issued or consumed', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $proof) self::assertNotFalse(stripos($document, $proof), $proof);
        foreach (['Only Batch 4 is authorized', 'offline replay, contention and interruption evidence', 'BEFORE_AUTHORITY_CONSUMPTION', 'AFTER_CONSUMPTION_BEFORE_DISPOSITION_COMMIT', 'AFTER_DISPOSITION_COMMIT', 'may not issue or consume live caller authority', 'seal a live disposition', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function basis(bool $complete): array
    {
        $activation = $this->record(SingleExecutionProviderBindingActivationContract::REQUIRED_FIELDS, SingleExecutionProviderBindingActivationContract::SCHEMA, ['activation_id' => 'activation-test', 'instance_id' => 'imperium-test', 'status' => 'ACTIVATED_UNCONSUMED', 'sealed' => true]);
        $custody = $this->record(CrossProcessCapabilityCustodyFeasibilityContract::REQUIRED_FIELDS, CrossProcessCapabilityCustodyFeasibilityContract::SCHEMA, ['assessment_id' => 'custody-assessment-test', 'instance_id' => 'imperium-test', 'source_activation' => self::referenceTo($activation, 'activation_id'), 'disposition' => CrossProcessCapabilityCustodyFeasibilityContract::REFUSAL, 'capability_issued' => false, 'capability_reconstructed' => false, 'external_action_performed' => false, 'sealed' => true]);
        $target = $this->record(ActivationCorridorDispositionTargetContract::REQUIRED_FIELDS, ActivationCorridorDispositionTargetContract::SCHEMA, ['target_id' => 'activation-corridor-target-test', 'instance_id' => 'imperium-test', 'corridor_id' => 'provider-binding-activation-corridor', 'corridor_generation' => 1, 'terminal_custody_refusal' => self::referenceTo($custody, 'assessment_id'), 'source_campaign' => ['id' => 'campaign-test', 'digest' => str_repeat('1', 64), 'schema' => 'imperium.campaign/v1'], 'scope' => ['provider_binding_activation_corridor' => 'provider-binding-activation', 'activation_artifact_set_digest' => str_repeat('2', 64), 'historical_evidence_set_digest' => str_repeat('3', 64)], 'identified_at' => '2026-08-30T11:10:00+00:00', 'authority_created' => false, 'binding_activated' => false, 'sealed' => true]);
        if (!$complete) return [$target, []];

        $decision = $this->record(ProviderBindingActivationIssuanceContract::REQUIRED_DECISION_FIELDS, ProviderBindingActivationIssuanceContract::DECISION_SCHEMA, ['decision_id' => 'activation-decision-test', 'instance_id' => 'imperium-test', 'disposition' => 'AUTHORIZED', 'external_action_performed' => false, 'sealed' => true]);
        $issuance = $this->record(ProviderBindingActivationIssuanceContract::REQUIRED_ISSUANCE_FIELDS, ProviderBindingActivationIssuanceContract::ISSUANCE_SCHEMA, ['issuance_id' => 'activation-issuance-test', 'instance_id' => 'imperium-test', 'source_decision' => ['id' => $decision['decision_id'], 'digest' => $decision['record_digest']], 'authority_issued' => true, 'provider_binding_activated' => false, 'credential_capability_issued' => false, 'external_action_performed' => false, 'sealed' => true]);
        $processLoss = $this->record(ProcessLossCapabilityCustodyEvidenceContract::REQUIRED_FIELDS, ProcessLossCapabilityCustodyEvidenceContract::SCHEMA, ['evidence_id' => 'process-loss-test', 'instance_id' => 'imperium-test', 'source_activation' => self::referenceTo($activation, 'activation_id'), 'classification' => 'POSSESSION_LOST', 'capability_reconstructed' => false, 'credential_resolved' => false, 'external_action_performed' => false, 'sealed' => true]);
        $exclusion = $this->record(CredentialReferenceExposureObservationContract::REQUIRED_FIELDS, CredentialReferenceExposureObservationContract::SCHEMA, ['observation_id' => 'credential-exclusion-test', 'instance_id' => 'imperium-test', 'classification' => 'EXCLUDED', 'credential_reference_observed' => false, 'credential_secret_observed' => false, 'sealed' => true]);
        $interruptions = [];
        foreach (ActivationTransitionInterruptionEvidenceContract::TRANSITIONS as $transition) foreach (ActivationTransitionInterruptionEvidenceContract::CUTS as $cut) $interruptions[] = $this->record(ActivationTransitionInterruptionEvidenceContract::REQUIRED_FIELDS, ActivationTransitionInterruptionEvidenceContract::SCHEMA, ['evidence_id' => 'interruption-'.substr(hash('sha256', $transition.'|'.$cut), 0, 16), 'instance_id' => 'imperium-test', 'transition' => $transition, 'cut' => $cut, 'classification' => 'CONVERGENT_RECOVERABLE', 'external_action_performed' => false, 'sealed' => true]);
        $stranded = $this->record(StrandedActivationArtifactDispositionContract::REQUIRED_FIELDS, StrandedActivationArtifactDispositionContract::SCHEMA, ['disposition_id' => 'stranded-disposition-test', 'instance_id' => 'imperium-test', 'disposition' => 'QUARANTINED_EXPIRED_UNUSED', 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'sealed' => true]);
        return [$target, ['activation_decision' => $decision, 'activation_authority_issuance' => $issuance, 'activation_lease' => $activation, 'transition_interruption_evidence' => $interruptions, 'stranded_artifact_dispositions' => [$stranded], 'custody_assessment' => $custody, 'process_loss_custody_evidence' => $processLoss, 'credential_secret_exclusion_evidence' => $exclusion]];
    }

    private function principal(bool $corridorAuthority): array
    {
        return $this->record(ImperatorRuntimePrincipalVersionContract::REQUIRED_FIELDS, ImperatorRuntimePrincipalVersionContract::SCHEMA, ['principal_version_id' => 'imperator-principal-version-test', 'principal_id' => 'imperator-principal-test', 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-binding-test', 'principal_generation' => 1, 'authority_scope' => ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false, 'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => $corridorAuthority], 'lifecycle' => ['constituted_at' => '2026-08-30T10:00:00+00:00', 'effective_at' => '2026-08-30T10:01:00+00:00', 'expires_at' => '2026-08-31T10:01:00+00:00', 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null], 'status' => 'ACTIVE', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false, 'serialized_capability_persisted' => false, 'sealed' => true]);
    }

    private function writePrincipal(array $principal): void
    {
        $directory = $this->root.'/'.FutureInstanceImperatorPrincipalConstitutionService::PRINCIPAL_VERSIONS;
        mkdir($directory, 0770, true);
        file_put_contents($directory.'/'.$principal['principal_version_id'].'.json', json_encode($principal, JSON_THROW_ON_ERROR));
    }

    private function record(array $fields, string $schema, array $values): array
    {
        $record = array_fill_keys($fields, null);
        unset($record['record_digest']);
        $record['schema'] = $schema;
        foreach ($values as $field => $value) $record[$field] = $value;
        return self::seal($record);
    }

    private static function referenceTo(array $record, string $idField): array
    {
        return ['id' => $record[$idField], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }

    private function at(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2026-08-30T12:00:00+00:00');
    }
}
