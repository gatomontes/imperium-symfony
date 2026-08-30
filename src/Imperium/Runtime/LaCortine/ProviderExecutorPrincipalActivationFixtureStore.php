<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationFixtureStore
{
    public const string DECISIONS =
        'var/imperium/evidence/provider-execution-effect-readiness/principal-activation-decisions';
    public const string ACTIVATIONS =
        'var/imperium/evidence/provider-execution-effect-readiness/principal-activations';

    private ImmutableRecordStore $records;
    private ProviderExecutorPrincipalActivationContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderExecutorPrincipalActivationContractValidator();
    }

    public function putDecision(
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertDecision(
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(
            self::DECISIONS,
            $decision['decision_id'],
            $decision,
        );
    }

    public function putActivation(
        array $activation,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertActivation(
            $activation,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(
            self::ACTIVATIONS,
            $activation['principal_activation_id'],
            $activation,
        );
    }
}
