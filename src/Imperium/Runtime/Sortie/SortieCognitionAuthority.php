<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Sortie;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\LaCortine\SortieManifest;

final readonly class SortieCognitionAuthority
{
    public string $digest;

    private function __construct(
        public string $executionId,
        public string $sortieId,
        public string $manifestationId,
        public string $commissionId,
        public string $authorizationId,
        public string $objective,
        public string $contextDigest,
        public array $destinations,
        public array $toolIds,
        public array $capabilityIds,
        public string $expectedReturnContract,
        public \DateTimeImmutable $expiresAt,
    ) {
        $this->digest = hash('sha256', CanonicalJson::encode($this->payload()));
    }

    public static function fromManifest(SortieManifest $manifest): self
    {
        return new self(
            $manifest->executionId,
            $manifest->sortieId,
            $manifest->manifestationId,
            $manifest->commissionId,
            $manifest->authorizationId,
            $manifest->objective,
            $manifest->contextDigest,
            array_values($manifest->destinations),
            array_values($manifest->toolIds),
            array_values($manifest->capabilityIds),
            $manifest->expectedReturnContract,
            $manifest->expiresAt,
        );
    }

    public function payload(): array
    {
        return [
            'type' => 'la-cortine.sortie-cognition/v1',
            'execution_id' => $this->executionId,
            'sortie_id' => $this->sortieId,
            'manifestation_id' => $this->manifestationId,
            'commission_id' => $this->commissionId,
            'authorization_id' => $this->authorizationId,
            'objective' => $this->objective,
            'context_digest' => $this->contextDigest,
            'destinations' => $this->destinations,
            'tool_ids' => $this->toolIds,
            'capability_ids' => $this->capabilityIds,
            'expected_return_contract' => $this->expectedReturnContract,
            'expires_at' => $this->expiresAt->format(DATE_ATOM),
        ];
    }
}
