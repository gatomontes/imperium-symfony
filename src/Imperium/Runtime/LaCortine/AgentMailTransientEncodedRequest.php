<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class AgentMailTransientEncodedRequest
{
    public function __construct(private array $request, private array $evidence)
    {
    }

    public function request(): array
    {
        return $this->request;
    }

    public function evidence(): array
    {
        return $this->evidence;
    }
}
