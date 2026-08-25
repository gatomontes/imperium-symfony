<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Citadel;

final readonly class DeepSeekDelegateModelConfiguration
{
    private const RUNTIME_MODEL = 'deepseek-v4-flash';
    private const ALLOWED_KEYS = ['temperature'];

    public function normalize(string $runtimeModel, mixed $configuration): array
    {
        if (self::RUNTIME_MODEL !== $runtimeModel || !is_array($configuration)) {
            throw new \RuntimeException('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        }
        $unknown = array_diff(array_keys($configuration), self::ALLOWED_KEYS);
        if ([] !== $unknown) {
            throw new \RuntimeException('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        }
        $temperature = $configuration['temperature'] ?? 0.2;
        if ((!is_int($temperature) && !is_float($temperature))
            || !is_finite((float) $temperature)
            || $temperature < 0.0
            || $temperature > 2.0) {
            throw new \RuntimeException('CT312_DELEGATE_MODEL_CONFIGURATION_INVALID');
        }

        return ['temperature' => (float) $temperature];
    }
}
