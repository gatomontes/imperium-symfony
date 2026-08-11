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

        $priorTurns = $this->proceedings->turns($proceedingId);
        $decision = $this->seneschal->advance($proceeding, $priorTurns, $response, [
            'instance_id' => $proceeding['instance_id'],
            'proceeding_id' => $proceedingId,
            'next_sequence' => count($priorTurns) + 1,
        ]);
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
            ],
            'resource_demands' => $decision['resource_demands'],
            'authorization_required' => $decision['authorization_required'],
            'authorization_note' => 'A demand is not an authorization; only a subsequent valid Imperator act may grant authority.',
        ];

        return $this->proceedings->appendTurn($proceedingId, $responseId, count($priorTurns) + 1, $turn);
    }
}
