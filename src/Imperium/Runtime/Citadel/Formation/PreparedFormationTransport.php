<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

/** Optional versioned custody path. Old retained v1 claims never acquire this authority. */
interface PreparedFormationTransport extends BoundedFormationTransport
{
    public function prepareOperation(array $request, array $terms): array;
}
