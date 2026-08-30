<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationProductionInterruptionProofService
{
    public const string CUT_BEFORE_COMBINED_COMMIT = 'before_combined_commit';
    public const string CUT_AFTER_COMBINED_COMMIT = 'after_combined_commit';

    private ProviderExecutorPrincipalActivationService $service;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->service = new ProviderExecutorPrincipalActivationService($root);
    }

    public function activate(
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
        ?string $cut,
    ): array {
        if (self::CUT_BEFORE_COMBINED_COMMIT === $cut) {
            throw new \RuntimeException(
                'PPB110_INTERRUPTED_BEFORE_COMBINED_ACTIVATION_COMMIT',
            );
        }

        $activation = $this->service->activate(
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        if (self::CUT_AFTER_COMBINED_COMMIT === $cut) {
            throw new \RuntimeException(
                'PPB111_INTERRUPTED_AFTER_COMBINED_ACTIVATION_COMMIT',
            );
        }
        if (null !== $cut) {
            throw new \InvalidArgumentException('PPB112_INTERRUPTION_CUT_INVALID');
        }

        return $activation;
    }
}
