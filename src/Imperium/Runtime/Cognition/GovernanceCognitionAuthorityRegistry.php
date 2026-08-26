<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Cognition;

final readonly class GovernanceCognitionAuthorityRegistry
{
    /** @var list<GovernanceCognitionAuthorityResolver> */
    private array $resolvers;

    public function __construct(iterable $resolvers = [])
    {
        $this->resolvers = is_array($resolvers) ? array_values($resolvers) : iterator_to_array($resolvers, false);
    }

    public function resolve(string $cluster, string $authorityType, string $authorityId): array
    {
        $matches = array_values(array_filter(
            $this->resolvers,
            static fn (GovernanceCognitionAuthorityResolver $resolver): bool => $resolver->supports($cluster, $authorityType),
        ));
        if (1 !== count($matches)) {
            throw new \RuntimeException('GCA100_GOVERNANCE_AUTHORITY_RESOLVER_UNAVAILABLE');
        }

        return $matches[0]->resolve($cluster, $authorityType, $authorityId);
    }
}
