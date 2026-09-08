<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** No safe live adapter exists in the current generic string-returning provider API. */
#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(BoundedFormationTransport::class)]
final class UnavailableFormationTransport implements BoundedFormationTransport
{
    public function inspect(array $request, array $terms): array
    {
        throw new \RuntimeException('CMF034_ENFORCEABLE_TRANSPORT_AND_PRICING_REQUIRED');
    }

    public function invoke(array $claim, array $request, array $terms): array
    {
        throw new \RuntimeException('CMF034_ENFORCEABLE_TRANSPORT_AND_PRICING_REQUIRED');
    }
}
