<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

interface DeterministicExecutor
{
    public function supports(string $toolId): bool;

    /**
     * Executes an exact pre-authorized mechanical operation.
     * Implementations must not invoke an LLM or create cognitive agency.
     */
    public function execute(BoundaryDispatch $dispatch, string $payload): RawExternalPayload;
}
