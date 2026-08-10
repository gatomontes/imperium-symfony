<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final class IronGate
{
    public function dispatch(OutboundRequest $request, \DateTimeImmutable $now): BoundaryDispatch
    {
        $request->assertExecutableAt($now);
        $executionId = $request->requestId.'.attempt.'.bin2hex(random_bytes(8));

        if (OutboundExecutionMode::Deterministic === $request->mode) {
            return new BoundaryDispatch(
                $executionId,
                $request->requestId,
                $request->commissionId,
                $request->authorizationId,
                $request->mode,
                $request->toolIds,
                $request->capabilityIds,
                $request->expectedReturnContract,
                null,
            );
        }

        $sortieId = 'sortie.'.bin2hex(random_bytes(12));
        $manifestationId = $sortieId.'.manifestation.1';
        $manifest = new SortieManifest(
            $sortieId,
            $manifestationId,
            $request->commissionId,
            $request->authorizationId,
            $request->purpose,
            $request->payloadDigest,
            $request->destinations,
            $request->toolIds,
            $request->capabilityIds,
            $request->expectedReturnContract,
            $request->expiresAt,
        );

        return new BoundaryDispatch(
            $executionId,
            $request->requestId,
            $request->commissionId,
            $request->authorizationId,
            $request->mode,
            $request->toolIds,
            $request->capabilityIds,
            $request->expectedReturnContract,
            $manifest,
        );
    }
}
