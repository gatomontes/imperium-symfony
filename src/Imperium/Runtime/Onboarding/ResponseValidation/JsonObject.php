<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Onboarding\ResponseValidation;

/** Keeps JSON objects distinct from arrays, including empty and numeric-key objects. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final readonly class JsonObject
{
    public function __construct(public array $members) {}
}
