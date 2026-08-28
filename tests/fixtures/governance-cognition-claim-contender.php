<?php

declare(strict_types=1);

use App\Imperium\Runtime\Clavium\GovernanceCognitionInvocationClaimService;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityRegistry;
use App\Imperium\Runtime\Cognition\GovernanceCognitionAuthorityResolver;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $root, $leaseId, $authorityPath, $gate] = $argv;
$authority = json_decode((string) file_get_contents($authorityPath), true, 512, JSON_THROW_ON_ERROR);
$resolver = new class($authority) implements GovernanceCognitionAuthorityResolver {
    public function __construct(private readonly array $authority)
    {
    }

    public function supports(string $cluster, string $authorityType): bool
    {
        return $cluster === $this->authority['cluster'] && $authorityType === $this->authority['authority_type'];
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        return $this->authority;
    }
};
while (!is_file($gate)) {
    usleep(1000);
}
$claim = (new GovernanceCognitionInvocationClaimService($root, new GovernanceCognitionAuthorityRegistry([$resolver])))->claim(
    $leaseId,
    $authority['authority_id'],
    new \DateTimeImmutable('2026-08-26T18:03:00+00:00'),
);
echo $claim['claim_id'];
