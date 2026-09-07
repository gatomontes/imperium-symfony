<?php
declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel\Formation;

/** Trusted credential/transport adapter. Unsupported enforceable ceilings must refuse
 * in inspect(), before any transmission. No production adapter is implied by a fake.
 */
interface BoundedFormationTransport
{
    /** Exact payload, destination/model, pricing and maxima. Pure, no external I/O.
     * Returns worst-case usage in SessionExposure::FIELDS order.
     */
    public function inspect(array $request, array $terms): array;

    /** Must enforce output, time and cost ceilings at the actual transport boundary.
     * Returns ['response' => exact bytes, 'usage' => trusted usage, 'provider_response_id' => string].
     * A throw after entering this method is an UNKNOWN outcome, never retryable.
     */
    public function invoke(array $claim, array $request, array $terms): array;
}
