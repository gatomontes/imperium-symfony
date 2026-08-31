<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Imperator;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class PrincipalActivationDecisionAuthorityProvenanceProductionInterruptionProofService
{
    public const string CUT_BEFORE_COMBINED_COMMIT = 'before_combined_commit';
    public const string CUT_AFTER_COMBINED_COMMIT = 'after_combined_commit';

    private PrincipalActivationDecisionAuthorityProvenanceProductionService $service;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->service = new PrincipalActivationDecisionAuthorityProvenanceProductionService($root);
    }

    public function produce(
        array $aggregate,
        array $sourcePrincipal,
        array $scopeSuccessor,
        array $activationDisposition,
        array $successorPrincipal,
        array $envelope,
        array $issuanceAuthorization,
        \DateTimeImmutable $at,
        ?string $cut,
    ): array {
        if (self::CUT_BEFORE_COMBINED_COMMIT === $cut) {
            throw new \RuntimeException('PAD5C10_INTERRUPTED_BEFORE_COMBINED_COMMIT');
        }

        $production = $this->service->produce(
            $aggregate,
            $sourcePrincipal,
            $scopeSuccessor,
            $activationDisposition,
            $successorPrincipal,
            $envelope,
            $issuanceAuthorization,
            $at,
        );

        if (self::CUT_AFTER_COMBINED_COMMIT === $cut) {
            throw new \RuntimeException('PAD5C11_INTERRUPTED_AFTER_COMBINED_COMMIT');
        }
        if (null !== $cut) {
            throw new \InvalidArgumentException('PAD5C12_INTERRUPTION_CUT_INVALID');
        }

        return $production;
    }
}
