<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Actual local PHP process incarnation: runtime PID plus a non-persistent nonce. */
final class NativeEffectProcessIncarnation
{
    private readonly int $initialPid;
    private readonly string $nonce;

    public function __construct()
    {
        $pid = getmypid();
        if (false === $pid || $pid < 1) {
            throw new \RuntimeException('CNE500_PROCESS_ID_UNAVAILABLE');
        }
        $this->initialPid = $pid;
        $this->nonce = random_bytes(32);
    }

    public function runtimeProcessId(): int
    {
        $this->assertCurrent();
        return $this->initialPid;
    }

    public function binding(string $material): string
    {
        $this->assertCurrent();
        return hash_hmac('sha256', $this->initialPid."\0".$material, $this->nonce);
    }

    public function recognizes(string $material, string $binding): bool
    {
        try {
            return hash_equals($this->binding($material), $binding);
        } catch (\RuntimeException) {
            return false;
        }
    }

    public function __serialize(): never
    {
        throw new \LogicException('CNE501_PROCESS_INCARNATION_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE501_PROCESS_INCARNATION_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE501_PROCESS_INCARNATION_CLONE_PROHIBITED');
    }

    private function assertCurrent(): void
    {
        $current = getmypid();
        if (false === $current || $current !== $this->initialPid) {
            throw new \RuntimeException('CNE502_PROCESS_INCARNATION_MISMATCH');
        }
    }
}
