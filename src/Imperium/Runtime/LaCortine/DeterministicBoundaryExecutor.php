<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class DeterministicBoundaryExecutor
{
    public function __construct(
        private readonly IronGate $ironGate,
        private readonly CredentialBroker $credentialBroker,
        private readonly Lazaretto $lazaretto,
    ) {
    }

    public function execute(
        OutboundRequest $request,
        string $payload,
        CredentialCapability $credential,
        DeterministicTransport $transport,
        \DateTimeImmutable $now,
    ): AdmittedExternalArtifact {
        if (OutboundExecutionMode::Deterministic !== $request->mode) {
            throw new \RuntimeException('DETERMINISTIC_EXECUTOR_MODE_MISMATCH: sorties cannot use deterministic boundary execution.');
        }
        if (!hash_equals($request->payloadDigest, hash('sha256', $payload))) {
            throw new \RuntimeException('DETERMINISTIC_PAYLOAD_MISMATCH: payload bytes do not match the authorized digest.');
        }
        if (1 !== count($request->destinations)) {
            throw new \RuntimeException('DETERMINISTIC_DESTINATION_AMBIGUOUS: one execution must bind exactly one destination.');
        }
        if (!in_array($credential->capabilityId, $request->capabilityIds, true)
            || $credential->commissionId !== $request->commissionId
            || $credential->operation !== $request->operation
        ) {
            throw new \RuntimeException('DETERMINISTIC_CREDENTIAL_SCOPE_MISMATCH: credential capability is outside the authorized request.');
        }
        if ($now >= $credential->expiresAt) {
            throw new \RuntimeException('DETERMINISTIC_CREDENTIAL_EXPIRED: credential capability is no longer valid.');
        }
        if (!$transport->supports($request->operation)) {
            throw new \RuntimeException('DETERMINISTIC_TRANSPORT_UNSUPPORTED: transport does not implement the exact authorized operation.');
        }

        $dispatch = $this->ironGate->dispatch($request, $now);
        $destination = $request->destinations[0];
        $result = $this->credentialBroker->consume(
            $credential,
            static fn (mixed $authentication): TransportResult => $transport->execute(
                $request->operation,
                $destination,
                $payload,
                $authentication,
            ),
        );

        if (!$result instanceof TransportResult) {
            throw new \RuntimeException('DETERMINISTIC_TRANSPORT_INVALID_RESULT: transport did not return a governed result.');
        }

        $receivedAt = new \DateTimeImmutable();
        $raw = new RawExternalPayload(
            'raw-external-payload.'.bin2hex(random_bytes(12)),
            $dispatch->executionId,
            $request->commissionId,
            $request->authorizationId,
            null,
            null,
            $result->content,
            hash('sha256', $result->content),
            $result->sourceIds,
            $request->toolIds,
            [$credential->capabilityId],
            $result->observedAt,
            $receivedAt,
        );

        return $this->lazaretto->admit($raw, $dispatch, $receivedAt);
    }
}
