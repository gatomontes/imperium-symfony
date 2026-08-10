<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Imperium\Runtime\LaCortine\RawExternalPayload;
use App\Imperium\Runtime\LaCortine\SortieLifecycle;

final class OneShotSortieRunner
{
    public function __construct(
        private readonly SortieCognitionGateway $gateway,
        private readonly SortieLifecycle $lifecycle,
    ) {
    }

    public function run(SortieManifestEnvelope $envelope, \DateTimeImmutable $now): RawExternalPayload
    {
        $manifest = $envelope->manifest;
        $this->lifecycle->register($manifest);
        $this->lifecycle->deploy($manifest, $now);

        try {
            $result = $this->gateway->execute($manifest);

            foreach ($result->toolIds as $toolId) {
                if (!in_array($toolId, $manifest->toolIds, true)) {
                    throw new \RuntimeException('SORTIE_UNDECLARED_TOOL: cognition reported a tool outside its manifest.');
                }
            }
            foreach ($result->capabilityIds as $capabilityId) {
                if (!in_array($capabilityId, $manifest->capabilityIds, true)) {
                    throw new \RuntimeException('SORTIE_UNDECLARED_CAPABILITY: cognition reported a capability outside its manifest.');
                }
            }

            $this->lifecycle->markReturned($manifest);
            $receivedAt = new \DateTimeImmutable();
            $payloadId = 'sortie-payload.'.hash('sha256', $manifest->sortieId.'|'.$envelope->manifestDigest.'|'.$result->content.'|'.$receivedAt->format('U.u'));

            return new RawExternalPayload(
                $payloadId,
                'sortie-execution.'.$manifest->sortieId,
                $manifest->commissionId,
                $manifest->authorizationId,
                $manifest->sortieId,
                $manifest->manifestationId,
                $result->content,
                hash('sha256', $result->content),
                $result->sourceIds,
                $result->toolIds,
                $result->capabilityIds,
                $result->observedAt,
                $receivedAt,
            );
        } finally {
            if ('retired' !== $this->lifecycle->state($manifest)) {
                $this->lifecycle->retire($manifest);
            }
        }
    }
}
