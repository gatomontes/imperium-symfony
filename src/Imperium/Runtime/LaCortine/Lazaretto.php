<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class Lazaretto
{
    public function admit(
        RawExternalPayload $payload,
        BoundaryDispatch $dispatch,
        \DateTimeImmutable $admittedAt,
    ): AdmittedExternalArtifact {
        if ($payload->executionId !== $dispatch->executionId
            || $payload->commissionId !== $dispatch->commissionId
            || $payload->authorizationId !== $dispatch->authorizationId
        ) {
            throw new \RuntimeException('LAZARETTO_LINEAGE_MISMATCH: payload does not belong to the exact boundary execution and authority.');
        }

        foreach ($payload->toolIds as $toolId) {
            if (!in_array($toolId, $dispatch->allowedToolIds, true)) {
                throw new \RuntimeException('LAZARETTO_UNDECLARED_TOOL: payload reports use of a tool outside the exact dispatch.');
            }
        }
        foreach ($payload->capabilityIds as $capabilityId) {
            if (!in_array($capabilityId, $dispatch->allowedCapabilityIds, true)) {
                throw new \RuntimeException('LAZARETTO_UNDECLARED_CAPABILITY: payload reports use of a capability outside the exact dispatch.');
            }
        }

        if (OutboundExecutionMode::Sortie === $dispatch->mode) {
            $sortie = $dispatch->sortie;
            if (null === $sortie
                || $payload->sortieId !== $sortie->sortieId
                || $payload->manifestationId !== $sortie->manifestationId
                || $payload->authorizationId !== $sortie->authorizationId
            ) {
                throw new \RuntimeException('LAZARETTO_SORTIE_MISMATCH: payload does not bind to the exact sortie manifestation and authority.');
            }
        } elseif (null !== $payload->sortieId || null !== $payload->manifestationId) {
            throw new \RuntimeException('LAZARETTO_UNDECLARED_SORTIE: deterministic execution returned sortie provenance.');
        }

        $provenance = [
            'execution_id' => $payload->executionId,
            'commission_id' => $payload->commissionId,
            'authorization_id' => $payload->authorizationId,
            'sortie_id' => $payload->sortieId,
            'manifestation_id' => $payload->manifestationId,
            'source_ids' => $payload->sourceIds,
            'tool_ids' => $payload->toolIds,
            'capability_ids' => $payload->capabilityIds,
            'expected_return_contract' => $dispatch->expectedReturnContract,
            'observed_at' => $payload->observedAt->format(DATE_ATOM),
            'received_at' => $payload->receivedAt->format(DATE_ATOM),
            'transformation' => 'identity-v1',
        ];

        return new AdmittedExternalArtifact(
            'external-artifact.'.hash('sha256', $payload->payloadId.'|'.$payload->contentDigest.'|'.$admittedAt->format(DATE_ATOM)),
            $payload->payloadId,
            $payload->contentDigest,
            $payload->content,
            $provenance,
            $admittedAt,
        );
    }
}
