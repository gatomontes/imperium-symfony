<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderAssuranceEvidenceFixtureStore
{
    public const string SOURCES =
        'var/imperium/evidence/provider-execution-effect-readiness/assurance-sources';
    public const string PROFILES =
        'var/imperium/evidence/provider-execution-effect-readiness/assurance-profiles';
    public const string ADMISSIONS =
        'var/imperium/evidence/provider-execution-effect-readiness/assurance-admissions';

    private ImmutableRecordStore $records;
    private ProviderAssuranceEvidenceContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderAssuranceEvidenceContractValidator();
    }

    public function putSource(array $source): array
    {
        $this->validator->assertSource($source);

        return $this->records->put(self::SOURCES, $source['source_id'], $source);
    }

    public function putProfile(array $profile, array $sources): array
    {
        $this->validator->assertProfile($profile, $sources);

        return $this->records->put(self::PROFILES, $profile['profile_id'], $profile);
    }

    public function putAdmission(array $admission, array $profile, array $sources): array
    {
        $this->validator->assertAdmission($admission, $profile, $sources);

        return $this->records->put(self::ADMISSIONS, $admission['admission_id'], $admission);
    }
}
