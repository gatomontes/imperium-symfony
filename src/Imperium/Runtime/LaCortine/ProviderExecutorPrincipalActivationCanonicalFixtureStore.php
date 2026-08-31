<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderExecutorPrincipalActivationCanonicalFixtureStore
{
    public const string RESOLUTION_ADMISSIONS =
        'var/imperium/evidence/provider-principal-binding-activation-resumption/resolution-admissions';
    public const string ACTIVATION_INPUTS =
        'var/imperium/evidence/provider-principal-binding-activation-resumption/activation-inputs';

    private ImmutableRecordStore $records;
    private ProviderExecutorPrincipalActivationCanonicalContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderExecutorPrincipalActivationCanonicalContractValidator();
    }

    public function putResolutionAdmission(
        array $admission,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertResolutionAdmission(
            $admission,
            $production,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(
            self::RESOLUTION_ADMISSIONS,
            $admission['resolution_admission_id'],
            $admission,
        );
    }

    public function putActivationInput(
        array $input,
        array $admission,
        array $production,
        array $decision,
        array $attestation,
        array $assurance,
        array $boundary,
        \DateTimeImmutable $at,
    ): array {
        $this->validator->assertActivationInput(
            $input,
            $admission,
            $production,
            $decision,
            $attestation,
            $assurance,
            $boundary,
            $at,
        );

        return $this->records->put(
            self::ACTIVATION_INPUTS,
            $input['input_id'],
            $input,
        );
    }
}
