<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

/** Deployment-owned, pure projection of the configuration actually used by
 * invoke/dispatch, including defaults. Never a projection of model_settings.
 * Prepared adapters must validate and project the exact retained operation bytes.
 * Ordinary adapters must use this same configuration when invoking.
 */
interface FormationEffectiveConfiguration
{
    public function effectiveConfiguration(array $request, array $terms, ?array $operation): array;
}
