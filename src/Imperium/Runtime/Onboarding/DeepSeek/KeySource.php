<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;

/** Fixed infrastructure; generation is public and must change on rotation. Never ingress. */
interface KeySource
{
    public function generation(): string;
    public function withKey(callable $delivery): void;
}
