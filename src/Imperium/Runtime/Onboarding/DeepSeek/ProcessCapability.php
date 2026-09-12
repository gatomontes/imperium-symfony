<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Onboarding\DeepSeek;

/** Identity only. Constructing this object grants nothing; only Runtime's private WeakMap recognizes it. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ProcessCapability
{
    private function __clone() {}
    public function __serialize(): array { throw new \RuntimeException('O3_CAPABILITY_NOT_SERIALIZABLE'); }
    public function __unserialize(array $data): void { throw new \RuntimeException('O3_CAPABILITY_NOT_RESTORABLE'); }
}
