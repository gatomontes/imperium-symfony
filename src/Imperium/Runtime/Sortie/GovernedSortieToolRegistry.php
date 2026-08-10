<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

final readonly class GovernedSortieToolRegistry
{
    /** @param iterable<SortieToolExecutor> $executors */
    public function __construct(private iterable $executors)
    {
    }

    public function resolve(string $toolId): SortieToolExecutor
    {
        $matches = [];
        foreach ($this->executors as $executor) {
            if ($executor->supports($toolId)) {
                $matches[] = $executor;
            }
        }

        if ([] === $matches) {
            throw new \RuntimeException('SORTIE_TOOL_UNAVAILABLE: no governed executor is registered for '.$toolId.'.');
        }
        if (1 !== count($matches)) {
            throw new \RuntimeException('SORTIE_TOOL_AMBIGUOUS: more than one governed executor claims '.$toolId.'.');
        }

        return $matches[0];
    }
}
