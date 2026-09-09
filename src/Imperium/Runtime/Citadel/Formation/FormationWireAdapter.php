<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

/** Deployment-selected infrastructure, never supplied by a cognitive caller.
 * prepare must be pure and refuse unsupported remote bounds before custody.
 * dispatch must use exactly the retained bytes/destination, without redirects.
 * Adapter validation of usage/provenance is a trust boundary, not a caller flag.
 */
interface FormationWireAdapter
{
    public function prepare(array $request, array $terms): array;
    public function dispatch(array $operation, mixed $authentication): array;
}
