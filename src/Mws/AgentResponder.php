<?php

namespace App\Mws;

/**
 * Provider adapter boundary. A role label never grants tools or effects.
 */
interface AgentResponder
{
    public function reply(string $role, string $instruction): string;
}
