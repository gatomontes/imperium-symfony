<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Governance;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class GovernanceEventEnvelopeService
{
    private const string REQUESTS = 'var/imperium/runtime/governance-cognition-requests';
    private const string EVENTS = 'var/imperium/runtime/governance-events';

    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        ?ImmutableRecordStore $records = null,
        ?AtomicTransition $atomic = null,
    ) {
        $atomic ??= new AtomicTransition($root);
        $this->records = $records ?? new ImmutableRecordStore($root, $atomic);
    }

    public function recordGovernanceCognitionRequest(string $requestId): array
    {
        if (!preg_match('/^governance-cognition-request-[a-f0-9]{20}$/', $requestId)) {
            throw new \InvalidArgumentException('CAG200_GOVERNANCE_EVENT_SOURCE_INVALID');
        }
        try {
            $request = $this->records->read(self::REQUESTS, $requestId);
        } catch (\RuntimeException) {
            throw new \RuntimeException('CAG200_GOVERNANCE_EVENT_SOURCE_INVALID');
        }
        $classification = $request['continuous_governance'] ?? null;
        if ('imperium.governance-cognition-request/v1' !== ($request['schema'] ?? null)
            || $requestId !== ($request['request_id'] ?? null)
            || 'GOVERNANCE_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION' !== ($request['status'] ?? null)
            || !ContinuousGovernanceContext::isExactAdvisoryCognition($classification, [
                'instance_id' => $request['instance_id'] ?? null,
                'seat' => $request['target']['seat'] ?? null,
                'purpose' => $request['target']['purpose'] ?? null,
                'input_digest' => $request['input_digest'] ?? null,
                'source' => $request['source_governance_authority'] ?? null,
            ])) {
            throw new \RuntimeException('CAG200_GOVERNANCE_EVENT_SOURCE_INVALID');
        }

        $eventId = 'governance-event-'.substr(hash('sha256', CanonicalJson::encode([
            'AUTHORIZATION_REQUEST', $requestId, $request['record_digest'],
        ])), 0, 20);
        $record = [
            'schema' => 'imperium.continuous-governance-event-envelope/v1',
            'event_id' => $eventId,
            'instance_id' => $request['instance_id'],
            'event_kind' => 'AUTHORIZATION_REQUEST',
            'semantic_class' => 'REQUEST_NOT_AUTHORIZATION',
            'native_folium' => [
                'schema' => $request['schema'],
                'id' => $requestId,
                'digest' => $request['record_digest'],
            ],
            'native_authority' => $request['source_governance_authority'],
            'occurred_at' => $request['requested_at'],
            'recorded_at' => $request['requested_at'],
            'consequence' => [
                'schema' => $classification['schema'],
                'governance_tier' => $classification['governance_tier'],
                'consequence_class' => $classification['consequence_class'],
            ],
            'runtime_principal_references' => $classification['runtime_principal_references'],
            'action_attempted' => false,
            'effect_completed' => false,
            'telemetry_event' => false,
            'native_judgment_replaced' => false,
            'authority_granted' => false,
            'authority_consumed' => false,
            'credential_authority' => false,
            'tool_authority' => false,
            'network_authority' => false,
            'perimeter_authority' => false,
            'external_action_authority' => false,
            'execution_authority' => false,
            'continuation_authority' => false,
            'revocation_authority' => false,
            'containment_authority' => false,
            'incident_authority' => false,
            'sealed' => true,
        ];

        try {
            return $this->records->put(self::EVENTS, $eventId, $record);
        } catch (\RuntimeException $exception) {
            throw new \RuntimeException('CAG201_GOVERNANCE_EVENT_CONFLICT', 0, $exception);
        }
    }
}
