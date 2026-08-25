<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use Symfony\AI\Platform\Message\MessageBag;

interface DelegateSymfonyPlatformAdapter
{
    public function invoke(
        string $secret,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
        string $idempotencyKey,
    ): string;
}
