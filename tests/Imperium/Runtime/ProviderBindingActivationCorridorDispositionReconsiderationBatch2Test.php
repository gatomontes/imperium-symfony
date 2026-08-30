<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionCallerAuthorityContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionContractValidator;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionEligibilityContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionEvidenceDossierContract;
use App\Imperium\Runtime\Imperator\ActivationCorridorDispositionTargetContract;
use App\Imperium\Runtime\Imperator\ImperatorRuntimePrincipalVersionContract;
use PHPUnit\Framework\TestCase;

final class ProviderBindingActivationCorridorDispositionReconsiderationBatch2Test extends TestCase
{
    public function testExactCallerAuthorityBasisValidatesWithoutIssuingOrStoringAuthority(): void
    {
        $validator = new ActivationCorridorDispositionContractValidator();
        $principal = $this->principal();
        $target = $this->target();
        $dossier = $this->dossier($principal, $target);
        $eligibility = $this->eligibility($principal, $target, $dossier);
        $authority = $this->authority($principal, $target, $dossier, $eligibility);

        $validator->assertAuthorityBasis($authority, $principal, $target, $dossier, $eligibility, new \DateTimeImmutable('2026-08-30T11:05:00+00:00'));
        self::assertSame('DECIDE_EXACT_ACTIVATION_CORRIDOR_DISPOSITION', ActivationCorridorDispositionCallerAuthorityContract::PERMITTED_TRANSITION);
        foreach (ActivationCorridorDispositionCallerAuthorityContract::NON_AUTHORITIES as $permission) self::assertFalse($permission);
    }

    public function testScopeExpiryEvidenceAndCandidateDivergenceFailClosed(): void
    {
        $validator = new ActivationCorridorDispositionContractValidator();
        $principal = $this->principal();
        $target = $this->target();
        $dossier = $this->dossier($principal, $target);
        $eligibility = $this->eligibility($principal, $target, $dossier);
        $authority = $this->authority($principal, $target, $dossier, $eligibility);

        $invalidPrincipal = $principal;
        $invalidPrincipal['authority_scope']['corridor_disposition_authority'] = false;
        $invalidPrincipal = self::seal($invalidPrincipal);
        $invalidDossier = $dossier;
        $invalidDossier['completeness'] = 'INCOMPLETE';
        $invalidDossier = self::seal($invalidDossier);
        $invalidAuthority = $authority;
        $invalidAuthority['proposed_disposition'] = 'QUARANTINED_PENDING_REMEDIATION';
        $invalidAuthority = self::seal($invalidAuthority);

        foreach ([
            [$authority, $invalidPrincipal, $target, $dossier, $eligibility, '2026-08-30T11:05:00+00:00'],
            [$authority, $principal, $target, $invalidDossier, $eligibility, '2026-08-30T11:05:00+00:00'],
            [$invalidAuthority, $principal, $target, $dossier, $eligibility, '2026-08-30T11:05:00+00:00'],
            [$authority, $principal, $target, $dossier, $eligibility, '2026-08-30T11:11:00+00:00'],
        ] as $index => [$candidateAuthority, $candidatePrincipal, $candidateTarget, $candidateDossier, $candidateEligibility, $at]) {
            try {
                $validator->assertAuthorityBasis($candidateAuthority, $candidatePrincipal, $candidateTarget, $candidateDossier, $candidateEligibility, new \DateTimeImmutable($at));
                self::fail('Invalid authority basis accepted at '.$index);
            } catch (\RuntimeException $exception) {
                self::assertStringStartsWith('ACD2', $exception->getMessage());
            }
        }
    }

    public function testDocumentationAuthorizesReadOnlyReconstructionOnly(): void
    {
        $root = dirname(__DIR__, 3);
        $contract = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/provider-binding-activation-corridor-disposition-caller-authority-validation.md'));
        $handoff = preg_replace('/\s+/', ' ', (string) file_get_contents($root.'/docs/handoffs/provider-binding-activation-corridor-disposition-reconsideration-batch-2-complete.md'));
        foreach (['BATCH_2_CALLER_AUTHORITY_CONTRACT_AND_FAIL_CLOSED_VALIDATORS_COMPLETE', 'no store, registry, producer, issuer, consumer', 'corridor_disposition_authority', 'does not prove that an eligible instance principal exists', 'No principal or binding is activated', 'REFUSED_CROSS_PROCESS_CUSTODY_UNPROVABLE', 'external I/O', 'Iron Gate', 'Lazaretto', 'Provider Execution Assurance remains paused'] as $proof) self::assertNotFalse(stripos($contract, $proof), $proof);
        foreach (['Only Batch 3 is authorized', 'read-only corridor reconstruction', 'ELIGIBLE', 'INCOMPLETE', 'CONFLICTED', 'REFUSED', 'Missing active-principal evidence must remain refusal', 'may not produce or activate a principal', 'issue or consume caller authority', 'seal a corridor disposition', 'external I/O', 'Iron Gate', 'Lazaretto'] as $boundary) self::assertNotFalse(stripos($handoff, $boundary), $boundary);
    }

    private function principal(): array
    {
        return self::seal(['schema' => ImperatorRuntimePrincipalVersionContract::SCHEMA, 'principal_version_id' => 'imperator-principal-version-corridor-1', 'principal_id' => 'imperator-principal-corridor', 'instance_id' => 'imperium-test', 'binding_id' => 'imperator-binding-corridor', 'principal_generation' => 1, 'constitution_route' => 'EXISTING_INSTANCE_REMEDIATION', 'source_constitution_authority' => $this->reference('constitution-authority-test', '1', 'imperium.operator-root.imperator-principal-constitution-authority/v1'), 'source_operator_root' => $this->reference('operator-root-test', '2', 'imperium.operator-root/v1'), 'identity' => ['operator_id' => 'operator-test', 'operator_identity_digest' => str_repeat('3', 64), 'imperator_subject_id' => 'imperator-subject-test', 'imperator_subject_digest' => str_repeat('4', 64)], 'authority_scope' => ['provider_binding_activation_authority' => true, 'outbound_email_authority' => false, 'credential_authority' => false, 'provider_execution_authority' => false, 'corridor_disposition_authority' => true], 'lifecycle' => ['constituted_at' => '2026-08-30T10:00:00+00:00', 'effective_at' => '2026-08-30T10:01:00+00:00', 'expires_at' => '2026-08-31T10:01:00+00:00', 'prior_version' => null, 'superseding_version' => null, 'current_disposition' => null], 'status' => 'ACTIVE', 'credential_reference_persisted' => false, 'credential_secret_persisted' => false, 'serialized_capability_persisted' => false, 'sealed' => true]);
    }

    private function target(): array
    {
        return self::seal(['schema' => ActivationCorridorDispositionTargetContract::SCHEMA, 'target_id' => 'activation-corridor-target-test', 'instance_id' => 'imperium-test', 'corridor_id' => 'provider-binding-activation-corridor', 'corridor_generation' => 1, 'terminal_custody_refusal' => $this->reference('custody-refusal-test', '5', 'imperium.clavium.cross-process-capability-custody-feasibility/v1'), 'source_campaign' => $this->reference('activation-corridor-campaign-test', '6', 'imperium.campaign/v1'), 'scope' => ['provider_binding_activation_corridor' => 'provider-binding-activation', 'activation_artifact_set_digest' => str_repeat('7', 64), 'historical_evidence_set_digest' => str_repeat('8', 64)], 'identified_at' => '2026-08-30T10:30:00+00:00', 'authority_created' => false, 'binding_activated' => false, 'sealed' => true]);
    }

    private function dossier(array $principal, array $target): array
    {
        $cuts = [];
        foreach (range(1, 6) as $cut) $cuts[] = $this->reference('interruption-evidence-'.$cut, dechex($cut + 8), 'imperium.imperator.activation-transition-interruption-evidence/v1');
        return self::seal(['schema' => ActivationCorridorDispositionEvidenceDossierContract::SCHEMA, 'dossier_id' => 'activation-corridor-dossier-test', 'instance_id' => 'imperium-test', 'target' => self::referenceTo($target, 'target_id'), 'active_principal' => self::referenceTo($principal, 'principal_version_id'), 'activation_decision' => $this->reference('activation-decision-test', 'f', 'imperium.imperator.provider-binding-activation-decision/v1'), 'activation_authority' => $this->reference('activation-authority-test', 'a', 'imperium.imperator.provider-binding-activation-authority/v1'), 'activation_lease' => $this->reference('activation-lease-test', 'b', 'imperium.la-cortine.single-execution-provider-binding-activation/v1'), 'transition_interruption_evidence' => $cuts, 'stranded_artifact_dispositions' => [$this->reference('stranded-disposition-test', 'c', 'imperium.la-cortine.stranded-activation-artifact-disposition/v1')], 'process_loss_custody_evidence' => $this->reference('process-loss-test', 'd', 'imperium.clavium.process-loss-capability-custody-evidence/v1'), 'credential_secret_exclusion_evidence' => $this->reference('secret-exclusion-test', 'e', 'imperium.clavium.credential-reference-exposure-observation/v1'), 'terminal_custody_refusal' => $this->reference('custody-refusal-test', '5', 'imperium.clavium.cross-process-capability-custody-feasibility/v1'), 'completeness' => 'COMPLETE', 'conflicts' => [], 'assembled_at' => '2026-08-30T10:40:00+00:00', 'read_only' => true, 'authority_created' => false, 'disposition_sealed' => false, 'source_artifact_mutated' => false, 'sealed' => true]);
    }

    private function eligibility(array $principal, array $target, array $dossier): array
    {
        return self::seal(['schema' => ActivationCorridorDispositionEligibilityContract::SCHEMA, 'eligibility_id' => 'activation-corridor-eligibility-test', 'instance_id' => 'imperium-test', 'target' => self::referenceTo($target, 'target_id'), 'evidence_dossier' => self::referenceTo($dossier, 'dossier_id'), 'principal' => self::referenceTo($principal, 'principal_version_id'), 'proposed_disposition' => 'RETIRE_CORRIDOR', 'predicates' => array_fill_keys(ActivationCorridorDispositionEligibilityContract::REQUIRED_PREDICATE_FIELDS, true), 'consequences' => ['corridor_operationally_usable' => false, 'retirement_irreversible' => true, 'replacement_corridor_requires_new_authority' => true, 'historical_evidence_readable' => true, 'outstanding_artifacts_create_no_authority' => true, 'terminal_custody_refusal_authoritative' => true], 'classification' => 'ELIGIBLE', 'reasons' => ['All authority-empty eligibility predicates are represented by exact offline fixtures.'], 'assessed_at' => '2026-08-30T10:50:00+00:00', 'authority_created' => false, 'disposition_sealed' => false, 'source_artifact_mutated' => false, 'successor_authority_created' => false, 'continuing_custody_refusal' => ActivationCorridorDispositionEligibilityContract::CONTINUING_CUSTODY_REFUSAL, 'sealed' => true]);
    }

    private function authority(array $principal, array $target, array $dossier, array $eligibility): array
    {
        return self::seal(['schema' => ActivationCorridorDispositionCallerAuthorityContract::SCHEMA, 'authority_id' => 'activation-corridor-caller-authority-test', 'instance_id' => 'imperium-test', 'principal' => self::referenceTo($principal, 'principal_version_id'), 'target' => self::referenceTo($target, 'target_id'), 'evidence_dossier' => self::referenceTo($dossier, 'dossier_id'), 'eligibility' => self::referenceTo($eligibility, 'eligibility_id'), 'permitted_transition' => ActivationCorridorDispositionCallerAuthorityContract::PERMITTED_TRANSITION, 'proposed_disposition' => 'RETIRE_CORRIDOR', 'authority_single_use' => true, 'authority_exercisable' => true, 'issued_at' => '2026-08-30T11:00:00+00:00', 'expires_at' => '2026-08-30T11:10:00+00:00', 'consumed' => false, 'continuing_authority' => false, 'issuance_winner_required' => true, 'consumption_winner_required' => true, 'sealed' => true]);
    }

    private function reference(string $id, string $digit, string $schema): array
    {
        return ['id' => $id, 'digest' => str_repeat($digit, 64), 'schema' => $schema];
    }

    private static function referenceTo(array $record, string $id): array
    {
        return ['id' => $record[$id], 'digest' => $record['record_digest'], 'schema' => $record['schema']];
    }

    private static function seal(array $record): array
    {
        unset($record['record_digest']);
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        return $record;
    }
}
