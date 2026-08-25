<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

use Symfony\AI\Platform\Message\MessageBag;

interface DeepSeekDelegatePlatformAdapter
{
    public const PROVIDER = 'deepseek';
    public const PLATFORM_SERVICE = 'ai.platform.generic.deepseek';
    public const RUNTIME_MODEL = 'deepseek-v4-flash';
    public const CREDENTIAL_REFERENCE = 'env:DEEPSEEK_API_KEY';
    public const OPERATION = 'deepseek.model.invoke';

    public function invoke(
        string $secret,
        string $runtimeModel,
        MessageBag $messages,
        array $configuration,
        string $idempotencyKey,
    ): string;
}
