<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Senate;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionTrustQuestionDispatchService
{
    private DelegateMissionQuestionDispatchEngine $engine;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->engine = new DelegateMissionQuestionDispatchEngine($root);
    }

    public function dispatch(string $decisionId, string $bailiffBindingId, \DateTimeImmutable $dispatchedAt): array
    {
        return $this->engine->dispatch('trust', $decisionId, $bailiffBindingId, $dispatchedAt);
    }
}
