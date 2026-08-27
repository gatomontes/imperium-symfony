<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\BootstrapState;
use App\Bootstrap\CanonicalJson;
use App\Bootstrap\StateStore;

final readonly class CurianDeliberation
{
    public function __construct(
        private StateStore $bootstrap,
        private ProceedingStore $proceedings,
        private SeneschalCognitionGateway $seneschal,
        private CurianCognitionAuthorityService $cognitionAuthorities,
    ) {
    }

    public function respond(string $proceedingId, string $response, ?string $responseId = null): array
    {
        $response = trim($response);
        if ('' === $response) {
            throw new \InvalidArgumentException('Imperator response cannot be empty.');
        }
        $proceeding = $this->proceedings->find($proceedingId);
        if (null === $proceeding) {
            throw new \RuntimeException('C21_PROCEEDING_NOT_FOUND: no such Curian proceeding exists.');
        }
        $bootstrap = $this->bootstrap->read();
        if (!is_array($bootstrap)
            || BootstrapState::CuriaReady->value !== ($bootstrap['state'] ?? null)
            || ($bootstrap['binding']['instance_id'] ?? null) !== ($proceeding['instance_id'] ?? null)
            || ($bootstrap['binding']['manifest_id'] ?? null) !== ($proceeding['manifest_id'] ?? null)
        ) {
            throw new \RuntimeException('C22_PROCEEDING_BINDING_INVALID: proceeding no longer matches the ready Imperium instance.');
        }

        $responseId ??= substr(hash('sha256', CanonicalJson::encode([
            'proceeding_id' => $proceedingId,
            'actor' => 'imperator-development-root',
            'response' => $response,
        ])), 0, 32);
        if (!preg_match('/^[a-zA-Z0-9._-]{8,80}$/', $responseId)) {
            throw new \InvalidArgumentException('Response identity must contain 8–80 safe identifier characters.');
        }
        $existing = $this->proceedings->findTurn($proceedingId, $responseId);
        if (null !== $existing) {
            return $existing;
        }

        $ready = $this->lastOutput($bootstrap, 'T10');
        $seneschal = $ready['runtime']['occupants']['seneschal'] ?? null;
        $currentOccupant = is_array($seneschal) ? [
            'seat' => 'curia.seneschal',
            'manifestation_id' => $seneschal['manifestation_id'] ?? null,
            'occupancy_generation' => $seneschal['occupancy_generation'] ?? null,
        ] : [];
        if ('active' !== ($seneschal['status'] ?? null)
            || CanonicalJson::encode($currentOccupant) !== CanonicalJson::encode($proceeding['seneschal']['occupant'] ?? null)) {
            throw new \RuntimeException('C23_SENESCHAL_OCCUPANCY_CHANGED: proceeding Seneschal is no longer current.');
        }
        $priorTurns = $this->proceedings->turns($proceedingId);
        $context = [
            'instance_id' => $proceeding['instance_id'],
            'proceeding_id' => $proceedingId,
            'next_sequence' => count($priorTurns) + 1,
            'response_id' => $responseId,
        ];
        $authority = $this->cognitionAuthorities->openDeliberation($proceeding, $priorTurns, $response, $context, $currentOccupant);
        $decision = $this->seneschal->advance($authority['authority_id'], $proceeding, $priorTurns, $response, $context);
        $turn = [
            'schema' => 'imperium.curian-turn/v1',
            'proceeding_id' => $proceedingId,
            'response_id' => $responseId,
            'imperator_response' => [
                'actor' => ['kind' => 'imperator', 'id' => 'imperator-development-root'],
                'authority_basis' => 'development-local-cli',
                'content' => $response,
            ],
            'secretary' => ['disposition' => 'RESPONSE_RECORDED'],
            'chamberlain' => ['disposition' => 'PROCEEDING_RESTORED'],
            'seneschal' => [
                'disposition' => $decision['disposition'],
                'decision' => $decision['decision'],
                'question' => $decision['question'],
                'mission_plan' => $decision['mission_plan'],
            ],
            'resource_demands' => $decision['resource_demands'],
            'authorization_required' => $decision['authorization_required'],
            'authorization_note' => 'A demand is not an authorization; only a subsequent valid Imperator act may grant authority.',
            'source_cognition_authority' => ['id' => $authority['authority_id'], 'digest' => $authority['record_digest']],
        ];

        return $this->proceedings->appendTurn($proceedingId, $responseId, count($priorTurns) + 1, $turn);
    }
    private function lastOutput(array $record, string $transition): array
    {
        for ($index = count($record['events'] ?? []) - 1; $index >= 0; --$index) {
            $event = $record['events'][$index];
            if ($transition === ($event['transition'] ?? null) && 'SUCCESS' === ($event['result'] ?? null)) {
                return is_array($event['output'] ?? null) ? $event['output'] : [];
            }
        }
        throw new \RuntimeException('C24_READINESS_RECEIPT_MISSING: T10 receipt is absent.');
    }
}
