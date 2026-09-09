<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

#[\Symfony\Component\DependencyInjection\Attribute\AsAlias(FormationWireAdapter::class)]
final class UnavailableFormationWireAdapter implements FormationWireAdapter
{
    public function prepare(array $request, array $terms): array { throw new \RuntimeException('FC001_LIVE_BOUNDS_AND_ADAPTER_UNAPPROVED'); }
    public function dispatch(array $operation, mixed $authentication): array { throw new \RuntimeException('FC001_LIVE_BOUNDS_AND_ADAPTER_UNAPPROVED'); }
}
