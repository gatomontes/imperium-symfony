<?php

declare(strict_types=1);

namespace App\Tests\Imperium\Runtime;

use App\Imperium\Runtime\Governance\ContinuousGovernanceContext;
use App\Imperium\Runtime\Governance\GovernanceEventEnvelopeService;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use PHPUnit\Framework\TestCase;

final class GovernanceEventEnvelopeServiceTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir().'/imperium-governance-event-'.bin2hex(random_bytes(5));
    }

    protected function tearDown(): void
    {
        $this->remove($this->root);
    }

    public function testRequestProducesOneExactAuthorityEmptyNativeFoliumEnvelope(): void
    {
        $request = $this->request();
        $service = new GovernanceEventEnvelopeService($this->root);
        $event = $service->recordGovernanceCognitionRequest($request['request_id']);

        self::assertSame($event, $service->recordGovernanceCognitionRequest($request['request_id']));
        self::assertSame('AUTHORIZATION_REQUEST', $event['event_kind']);
        self::assertSame('REQUEST_NOT_AUTHORIZATION', $event['semantic_class']);
        self::assertSame(['schema' => $request['schema'], 'id' => $request['request_id'], 'digest' => $request['record_digest']], $event['native_folium']);
        self::assertSame($request['source_governance_authority'], $event['native_authority']);
        self::assertFalse($event['action_attempted']);
        self::assertFalse($event['effect_completed']);
        self::assertFalse($event['telemetry_event']);
        foreach (['authority_granted', 'authority_consumed', 'credential_authority', 'tool_authority', 'network_authority', 'perimeter_authority', 'external_action_authority', 'execution_authority', 'continuation_authority', 'revocation_authority', 'containment_authority', 'incident_authority'] as $field) {
            self::assertFalse($event[$field], $field.' must remain false.');
        }
    }

    public function testTamperedEventCannotParticipateInExactReplay(): void
    {
        $request = $this->request();
        $service = new GovernanceEventEnvelopeService($this->root);
        $event = $service->recordGovernanceCognitionRequest($request['request_id']);
        $path = $this->root.'/var/imperium/runtime/governance-events/'.$event['event_id'].'.json';
        $stored = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        $stored['execution_authority'] = true;
        file_put_contents($path, json_encode($stored, JSON_THROW_ON_ERROR));

        $this->expectExceptionMessage('CAG201_GOVERNANCE_EVENT_CONFLICT');
        $service->recordGovernanceCognitionRequest($request['request_id']);
    }

    private function request(): array
    {
        $source = ['id' => 'native-authority-test', 'digest' => str_repeat('b', 64)];
        $context = ContinuousGovernanceContext::advisoryCognition([
            'instance_id' => 'imperium-test', 'seat' => 'foundry.artificer', 'purpose' => 'specify-persona',
            'input_digest' => str_repeat('a', 64), 'source' => $source,
        ]);
        $requestId = 'governance-cognition-request-'.str_repeat('c', 20);
        $records = new ImmutableRecordStore($this->root, new AtomicTransition($this->root));

        return $records->put('var/imperium/runtime/governance-cognition-requests', $requestId, [
            'schema' => 'imperium.governance-cognition-request/v1', 'request_id' => $requestId,
            'instance_id' => 'imperium-test', 'source_governance_authority' => $source,
            'target' => ['seat' => 'foundry.artificer', 'purpose' => 'specify-persona'],
            'input_digest' => str_repeat('a', 64), 'continuous_governance' => $context,
            'requested_at' => '2026-08-27T18:00:00+00:00',
            'status' => 'GOVERNANCE_COGNITION_REQUESTED_PENDING_IMPERATOR_PROVIDER_RESOURCE_DECISION',
            'sealed' => true,
        ]);
    }

    private function remove(string $path): void
    {
        if (!is_dir($path)) { return; }
        foreach (array_diff(scandir($path) ?: [], ['.', '..']) as $entry) {
            $child = $path.'/'.$entry;
            is_dir($child) ? $this->remove($child) : unlink($child);
        }
        rmdir($path);
    }
}
