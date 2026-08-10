<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class DeterministicExecutionRegistry
{
    /** @param iterable<DeterministicExecutor> $executors */
    public function __construct(private iterable $executors)
    {
    }

    public function resolve(string $toolId): DeterministicExecutor
    {
        $match = null;
        foreach ($this->executors as $executor) {
            if (!$executor->supports($toolId)) {
                continue;
            }
            if (null !== $match) {
                throw new \RuntimeException('DETERMINISTIC_TOOL_AMBIGUOUS: more than one executor claims '.$toolId.'.');
            }
            $match = $executor;
        }

        if (null === $match) {
            throw new \RuntimeException('DETERMINISTIC_TOOL_UNAVAILABLE: no mechanical executor claims '.$toolId.'.');
        }

        return $match;
    }
}
