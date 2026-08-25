<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Audit;

use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionOperationalEvidenceAuditService
{
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function audit(string $terminalId): array
    {
        if (!preg_match('/^delegate-mission-terminal-return-[a-f0-9]{20}$/', $terminalId)) {
            throw new \InvalidArgumentException('AUD300_DELEGATE_TERMINAL_ID_INVALID');
        }
        $terminal = $this->record('var/imperium/offices/garrison/delegate-mission-terminal-returns', $terminalId, 'AUD301_DELEGATE_TERMINAL_ABSENT');
        $return = $this->source('var/imperium/offices/curia/delegate-mission-return-authorizations', $terminal['source_return_authorization'] ?? [], 'authorization_id', 'AUD302_DELEGATE_RETURN_AUTHORIZATION_INVALID');
        $disposition = $this->source('var/imperium/offices/curia/delegate-mission-cognition-result-dispositions', $return['source_disposition'] ?? [], 'disposition_id', 'AUD303_DELEGATE_RESULT_DISPOSITION_INVALID');
        $turn = $this->source('var/imperium/operational/delegate-mission-bounded-cognition-turns', $return['source_turn'] ?? [], 'turn_id', 'AUD304_DELEGATE_COGNITION_TURN_INVALID');
        $activation = $this->source('var/imperium/offices/clavium/delegate-mission-provider-invocation-activations', $turn['source_activation'] ?? [], 'activation_id', 'AUD305_DELEGATE_PROVIDER_ACTIVATION_INVALID');
        $resourceDecision = $this->source('var/imperium/imperator/delegate-mission-resource-invocation-decisions', $activation['source_authorization'] ?? [], 'decision_id', 'AUD306_DELEGATE_RESOURCE_DECISION_INVALID');
        $attestation = $this->source('var/imperium/offices/clavium/delegate-mission-model-access-attestations', $activation['source_access_attestation'] ?? [], 'attestation_id', 'AUD307_DELEGATE_ACCESS_ATTESTATION_INVALID');
        $modelBinding = $this->source('var/imperium/offices/conscription/delegate-mission-model-bindings', $activation['source_model_binding'] ?? [], 'binding_id', 'AUD308_DELEGATE_MODEL_BINDING_INVALID');
        $commission = $this->source('var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions', $turn['source_commission'] ?? [], 'commission_id', 'AUD309_DELEGATE_COMMISSION_INVALID');
        $runtime = $this->source('var/imperium/mission/delegate-mission-runtime-activations', $commission['source_activation'] ?? [], 'activation_id', 'AUD310_DELEGATE_RUNTIME_ACTIVATION_INVALID');
        $custodyTransition = $this->source('var/imperium/offices/garrison/delegate-mission-operational-custody-transitions', $runtime['source_custody_transition'] ?? [], 'transition_id', 'AUD311_DELEGATE_CUSTODY_TRANSITION_INVALID');
        $deployment = $this->source('var/imperium/offices/curia/delegate-mission-deployment-authorizations', $custodyTransition['source_deployment_authorization'] ?? [], 'authorization_id', 'AUD312_DELEGATE_DEPLOYMENT_INVALID');
        $operationalBinding = $this->record('var/imperium/mission/occupancy', (string) ($return['source_binding']['id'] ?? ''), 'AUD313_DELEGATE_OPERATIONAL_BINDING_INVALID');
        $custody = $this->record('var/imperium/offices/garrison/custody', (string) ($terminal['restored_custody']['id'] ?? ''), 'AUD314_DELEGATE_CUSTODY_INVALID');

        if ('DELEGATE_MISSION_RETURNED_UNBOUND_CUSTODY_RESTORED_RETIRED_TERMINAL' !== ($terminal['status'] ?? null)
            || 'DELEGATE_MISSION_RETURN_AUTHORIZED_PENDING_GARRISON_TERMINAL_TRANSITION' !== ($return['status'] ?? null)
            || 'DELEGATE_MISSION_RESULT_DISPOSED_PENDING_RETURN_AUTHORIZATION' !== ($disposition['status'] ?? null)
            || 'DELEGATE_MISSION_BOUNDED_COGNITION_TURN_COMPLETE_PENDING_CURIA_DISPOSITION' !== ($turn['status'] ?? null)
            || 'DELEGATE_MISSION_PROVIDER_INVOCATION_ACTIVATED_PENDING_ONE_BOUNDED_COGNITION_TURN' !== ($activation['status'] ?? null)
            || 'DELEGATE_MISSION_RESOURCE_AND_INVOCATION_AUTHORIZED_PENDING_SCOPED_ACTIVATION' !== ($resourceDecision['status'] ?? null)
            || 'DELEGATE_MISSION_MODEL_ACCESS_ATTESTED_PENDING_RESOURCE_AND_INVOCATION_DECISION' !== ($attestation['status'] ?? null)
            || 'DELEGATE_MISSION_MODEL_BINDING_SEALED_PENDING_ACCESS_ATTESTATION' !== ($modelBinding['status'] ?? null)
            || 'DELEGATE_MISSION_BOUNDED_COGNITION_COMMISSION_CONSTRUCTED_PENDING_RESOURCE_AND_INVOCATION_AUTHORIZATION' !== ($commission['status'] ?? null)
            || 'DELEGATE_MISSION_RUNTIME_ACTIVE_PENDING_MISSION_CONTROL_INTAKE' !== ($runtime['status'] ?? null)
            || 'DELEGATE_MISSION_DEPLOYED_CUSTODY_TRANSITIONED_PENDING_MISSION_ACTIVATION' !== ($custodyTransition['status'] ?? null)
            || 'DELEGATE_MISSION_DEPLOYMENT_AUTHORIZED_PENDING_GARRISON_CUSTODY_TRANSITION' !== ($deployment['status'] ?? null)
            || 'DELEGATE_MISSION_MANIFESTATION_RETURNED_UNBOUND_RETIRED' !== ($operationalBinding['status'] ?? null)
            || ($terminal['source_binding']['terminal_digest'] ?? null) !== $operationalBinding['record_digest']
            || true !== ($terminal['custody_restored'] ?? null) || true !== ($terminal['manifestation_retired'] ?? null)
            || false !== ($operationalBinding['seat_bound'] ?? null) || false !== ($terminal['seat_bound'] ?? null)
            || 'ADMITTED_HELD' !== ($custody['custody_state'] ?? null) || true !== ($custody['available'] ?? null)
            || ($terminal['restored_custody']['digest'] ?? null) !== $custody['record_digest']
            || ($activation['model']['runtime_binding'] ?? null) !== ($modelBinding['runtime_binding'] ?? null)
            || ($attestation['runtime_binding'] ?? null) !== ($modelBinding['runtime_binding'] ?? null)
            || true !== ($turn['maximum_turns_consumed'] ?? null) || true === ($turn['continuing_turn_authority'] ?? null)) {
            throw new \RuntimeException('AUD315_DELEGATE_TERMINAL_CHAIN_INVALID');
        }

        return [
            'status' => 'DELEGATE_MISSION_OPERATIONAL_EVIDENCE_VALID',
            'audit_scope' => 'TERMINAL_OPERATIONAL_EVIDENCE',
            'completeness_claim' => 'FOURTEEN_RECORD_OPERATIONAL_SUBCHAIN_ONLY',
            'excluded_lifecycle_steps' => 'NON_OPERATIONAL_PRE_DEPLOYMENT_GOVERNANCE',
            'terminal_id' => $terminalId,
            'instance_id' => $terminal['instance_id'],
            'manifestation_id' => $terminal['target']['manifestation_id'],
            'persona_custody_id' => $custody['custody_id'],
            'runtime_binding' => $modelBinding['runtime_binding'],
            'verified_records' => 14,
            'terminal_checkpoint' => $terminal['status'],
            'continuing_authority' => false,
        ];
    }

    private function source(string $directory, array $reference, string $idField, string $error): array
    {
        return $this->validator->resolve($this->root.'/'.$directory, $reference, $error, $error, $idField);
    }

    private function record(string $directory, string $id, string $error): array
    {
        if ('' === $id) {
            throw new \RuntimeException($error);
        }

        return $this->validator->requireIntact(
            $this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', $error),
            $error,
        );
    }
}
