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
        throw new \RuntimeException('CMF113_NEW_PLANNING_REQUIRES_CITADEL_AUTHORITY');

    }
}
