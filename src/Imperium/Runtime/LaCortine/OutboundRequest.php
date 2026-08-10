<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class OutboundRequest
{
    /**
     * @param list<string> $destinations
     * @param list<string> $toolIds
     * @param list<string> $capabilityIds
     */
    public function __construct(
        public string $requestId,
        public string $authorizationId,
        public string $authorizationDigest,
        public string $commissionId,
        public string $operation,
        public string $purpose,
        public OutboundExecutionMode $mode,
        public array $destinations,
        public array $toolIds,
        public array $capabilityIds,
        public string $payloadDigest,
        public string $expectedReturnContract,
        public \DateTimeImmutable $expiresAt,
    ) {
        foreach ([$requestId, $authorizationId, $authorizationDigest, $commissionId, $operation, $purpose, $payloadDigest, $expectedReturnContract] as $value) {
            if ('' === trim($value)) {
                throw new \InvalidArgumentException('La Cortine outbound requests require exact non-empty authorization, commission, operation, payload, and return-contract identities.');
            }
        }
        if ([] === $destinations) {
            throw new \InvalidArgumentException('La Cortine outbound requests require at least one exact destination.');
        }
    }

    public function assertExecutableAt(\DateTimeImmutable $now): void
    {
        if ($now >= $this->expiresAt) {
            throw new \RuntimeException('LA_CORTINE_REQUEST_EXPIRED: outbound request is no longer executable.');
        }
    }
}
