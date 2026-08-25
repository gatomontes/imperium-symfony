<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use Symfony\AI\Platform\Message\MessageBag;

interface DelegateProviderInvoker
{
    public function invoke(
        array $claim,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
    ): string;
}
