<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

/** Opaque one-use authority. Public fields are bindings, not forgeable custody. */
final readonly class MissionCapability
{
    public function __construct(
        public string $missionId,
        public string $dossierIdentity,
        public string $action,
        public string $actor,
        public string $target,
        public int $notBefore,
        public int $expiresAt,
        public string $nonce,
        public string $authorizationProvenance,
        public string $signature,
    ) {}

    public function __serialize(): never { throw new \LogicException('MIS201_CAPABILITY_SERIALIZATION_PROHIBITED'); }
    public function __unserialize(array $data): never { throw new \LogicException('MIS201_CAPABILITY_UNSERIALIZATION_PROHIBITED'); }
    public function __clone(): void { throw new \LogicException('MIS201_CAPABILITY_CLONE_PROHIBITED'); }
}

