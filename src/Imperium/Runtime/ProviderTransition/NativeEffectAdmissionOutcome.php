<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\ProviderTransition;

/** Separates the durable admission from its optional, non-serializable custody object. */
final readonly class NativeEffectAdmissionOutcome implements \ArrayAccess, \JsonSerializable
{
    public function __construct(
        public array $admission,
        public ?NativeEffectContinuationCapability $continuation,
        public bool $newlyPublished,
    ) {}

    public function offsetExists(mixed $offset): bool { return isset($this->admission[$offset]); }
    public function offsetGet(mixed $offset): mixed { return $this->admission[$offset] ?? null; }
    public function offsetSet(mixed $offset, mixed $value): never { throw new \LogicException('CNE308_ADMISSION_OUTCOME_IMMUTABLE'); }
    public function offsetUnset(mixed $offset): never { throw new \LogicException('CNE308_ADMISSION_OUTCOME_IMMUTABLE'); }
    public function jsonSerialize(): array { return $this->admission; }

    public function __serialize(): never
    {
        throw new \LogicException('CNE504_ADMISSION_OUTCOME_SERIALIZATION_PROHIBITED');
    }

    public function __unserialize(array $data): never
    {
        throw new \LogicException('CNE504_ADMISSION_OUTCOME_UNSERIALIZATION_PROHIBITED');
    }

    public function __clone(): void
    {
        throw new \LogicException('CNE504_ADMISSION_OUTCOME_CLONE_PROHIBITED');
    }
}
