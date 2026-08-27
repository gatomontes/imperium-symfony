<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class InternalGovernanceReconstructionService
{
    private RecordReferenceValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, ?RecordReferenceValidator $validator = null)
    {
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function reconstruct(string $claimId): array
    {
        if (!preg_match('/^governance-cognition-invocation-claim-[a-f0-9]{20}$/', $claimId)) {
            throw new \InvalidArgumentException('CAG300_INTERNAL_RECONSTRUCTION_CLAIM_INVALID');
        }
        $claim = $this->record('var/imperium/runtime/governance-cognition-invocation-claims', $claimId, 'claim_id');
        $lease = $this->source('var/imperium/offices/clavium/governance-cognition-leases', $claim['source_lease'] ?? [], 'lease_id');
        $request = $this->source('var/imperium/runtime/governance-cognition-requests', $claim['source_cognition_request'] ?? [], 'request_id');
        $decision = $this->source('var/imperium/imperator/governance-provider-resource-decisions', $lease['source_provider_resource_decision'] ?? [], 'decision_id');
        $eventId = 'governance-event-'.substr(hash('sha256', CanonicalJson::encode([
            'AUTHORIZATION_REQUEST', $request['request_id'], $request['record_digest'],
        ])), 0, 20);
        $event = $this->record('var/imperium/runtime/governance-events', $eventId, 'event_id');
        $journal = $this->record('var/imperium/runtime/provider-invocation-journal', $claimId, null);
        $response = $this->record('var/imperium/runtime/provider-response-envelopes', $claimId, 'envelope_id');

        $responseIdentity = $response['provider_response_identity'] ?? null;
        $classification = $request['continuous_governance'] ?? null;
        if (!ContinuousGovernanceContext::isExactAdvisoryCognition($classification, [
                'instance_id' => $request['instance_id'] ?? null,
                'seat' => $request['target']['seat'] ?? null,
                'purpose' => $request['target']['purpose'] ?? null,
                'input_digest' => $request['input_digest'] ?? null,
                'source' => $request['source_governance_authority'] ?? null,
            ])
            || 'imperium.continuous-governance-event-envelope/v1' !== ($event['schema'] ?? null)
            || 'AUTHORIZATION_REQUEST' !== ($event['event_kind'] ?? null)
            || 'REQUEST_NOT_AUTHORIZATION' !== ($event['semantic_class'] ?? null)
            || ['schema' => $request['schema'], 'id' => $request['request_id'], 'digest' => $request['record_digest']] !== ($event['native_folium'] ?? null)
            || ($event['native_authority'] ?? null) !== ($request['source_governance_authority'] ?? null)
            || ($event['consequence'] ?? null) !== ['schema' => $classification['schema'], 'governance_tier' => $classification['governance_tier'], 'consequence_class' => $classification['consequence_class']]
            || ($event['runtime_principal_references'] ?? null) !== $classification['runtime_principal_references']
            || true === ($event['action_attempted'] ?? null) || true === ($event['effect_completed'] ?? null)
            || true === ($event['telemetry_event'] ?? null) || true === ($event['authority_granted'] ?? null)
            || 'imperium.imperator-governance-provider-resource-decision/v1' !== ($decision['schema'] ?? null)
            || 'AUTHORIZED' !== ($decision['disposition'] ?? null)
            || ($decision['source_cognition_request'] ?? null) !== ['id' => $request['request_id'], 'digest' => $request['record_digest']]
            || ($lease['source_cognition_request'] ?? null) !== $decision['source_cognition_request']
            || ($claim['source_cognition_request'] ?? null) !== $decision['source_cognition_request']
            || ($claim['source_governance_authority'] ?? null) !== ($request['source_governance_authority'] ?? null)
            || true !== ($claim['lease_consumption']['consumed'] ?? null)
            || true !== ($claim['governance_authority_consumption']['consumed'] ?? null)
            || ($claim['lease_consumption']['lease_id'] ?? null) !== $lease['lease_id']
            || 'imperium.clavium-provider-invocation-journal/v1' !== ($journal['schema'] ?? null)
            || ($journal['claim'] ?? null) !== ['id' => $claimId, 'digest' => $claim['record_digest']]
            || ($journal['idempotency_key'] ?? null) !== ($claim['provider_request']['idempotency_identity'] ?? null)
            || 'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING' !== ($journal['status'] ?? null)
            || !is_string($responseIdentity) || $responseIdentity !== ($journal['provider_response_identity'] ?? null)
            || !is_string($response['response'] ?? null) || $responseIdentity !== 'sha256:'.hash('sha256', $response['response'])
            || ($response['claim'] ?? null) !== ['id' => $claimId, 'digest' => $claim['record_digest']]
            || 'PROVIDER_RESPONSE_ENVELOPE_SEALED_PENDING_TURN_PERSISTENCE' !== ($response['status'] ?? null)
            || !$this->sameLineage($request, $decision, $lease, $claim)) {
            throw new \RuntimeException('CAG301_INTERNAL_RECONSTRUCTION_INVALID');
        }

        return [
            'schema' => 'imperium.continuous-governance-internal-reconstruction/v1',
            'status' => 'INTERNAL_GOVERNANCE_PROVIDER_ITERATION_RECONSTRUCTED',
            'completeness_claim' => 'SEVEN_ARTIFACT_INTERNAL_PROVIDER_SUBCHAIN_ONLY',
            'root_claim' => ['id' => $claimId, 'digest' => $claim['record_digest']],
            'instance_id' => $claim['instance_id'],
            'included_evidence' => [
                'governance_event' => $event,
                'cognition_request' => $request,
                'provider_resource_decision' => $decision,
                'credential_lease' => $lease,
                'invocation_claim' => $claim,
                'provider_journal' => $journal,
                'provider_response_envelope' => $response,
            ],
            'verified_artifact_count' => 7,
            'excluded_evidence' => [
                'CLUSTER_NATIVE_AUTHORITY_RECORD',
                'INSTITUTIONAL_DECISION_INTEGRITY_BUNDLE',
                'DELEGATE_PRE_DEPLOYMENT_GOVERNANCE',
                'DEPLOYMENT_AND_PERSONA_CUSTODY',
                'TERMINAL_RETURN_AND_RETIREMENT',
                'NATIVE_POST_PROVIDER_RESULT',
                'EXTERNAL_EFFECT',
                'TELEMETRY',
                'REVOCATION_AND_CONTAINMENT',
                'INCIDENT_EVIDENCE',
            ],
            'read_only' => true,
            'provider_reinvoked' => false,
            'missing_evidence_manufactured' => false,
            'authority_granted' => false,
            'authority_consumed' => false,
            'continuation_authority' => false,
        ];
    }

    private function sameLineage(array ...$records): bool
    {
        foreach (['instance_id', 'cluster', 'target', 'input_digest'] as $field) {
            $values = array_map(static fn (array $record): mixed => $record[$field] ?? null, $records);
            if (count(array_unique(array_map(static fn (mixed $value): string => CanonicalJson::encode($value), $values))) !== 1) {
                return false;
            }
        }
        return ($records[1]['provider'] ?? null) === ($records[2]['provider'] ?? null)
            && ($records[2]['provider'] ?? null) === ($records[3]['provider'] ?? null)
            && ($records[1]['model'] ?? null) === ($records[2]['model'] ?? null)
            && ($records[2]['model'] ?? null) === ($records[3]['model'] ?? null);
    }

    private function source(string $directory, array $reference, string $identityField): array
    {
        return $this->validator->resolve($this->root.'/'.$directory, $reference, 'CAG301_INTERNAL_RECONSTRUCTION_INVALID', 'CAG301_INTERNAL_RECONSTRUCTION_INVALID', $identityField);
    }

    private function record(string $directory, string $id, ?string $identityField): array
    {
        $record = $this->validator->requireIntact(
            $this->validator->read($this->root.'/'.$directory.'/'.$id.'.json', 'CAG301_INTERNAL_RECONSTRUCTION_INVALID'),
            'CAG301_INTERNAL_RECONSTRUCTION_INVALID',
        );
        if (null !== $identityField && $id !== ($record[$identityField] ?? null)) {
            throw new \RuntimeException('CAG301_INTERNAL_RECONSTRUCTION_INVALID');
        }
        return $record;
    }
}
