<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Mission;

final readonly class AcceptedMission
{
    public function __construct(
        public MissionDossier $dossier,
        private array $capabilities,
        private MissionCapabilityConsumer $consumer,
    ) {}

    public function capability(string $action, string $actor, string $target): MissionCapability
    {
        foreach ($this->capabilities as $capability) {
            if ($capability->action === $action && $capability->actor === $actor && $capability->target === $target) {
                return $capability;
            }
        }
        throw new \RuntimeException('MIS210_CAPABILITY_NOT_GRANTED');
    }

    public function consumer(): MissionCapabilityConsumer { return $this->consumer; }
}

