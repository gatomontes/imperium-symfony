<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

interface DeterministicTransport
{
    public function supports(string $operation): bool;

    public function execute(
        string $operation,
        string $destination,
        string $payload,
        mixed $authentication,
    ): TransportResult;
}
