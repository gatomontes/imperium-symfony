<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderAssuranceEvidenceAggregateReconstructor
{
    public const array CLASSIFICATIONS = [
        'ELIGIBLE_OFFLINE_EVIDENCE',
        'INCOMPLETE',
        'CONFLICTED',
        'REFUSED',
    ];

    private ImmutableRecordStore $records;
    private ProviderAssuranceEvidenceContractValidator $validator;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->records = new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = new ProviderAssuranceEvidenceContractValidator();
    }

    public function reconstruct(
        string $sourceId,
        string $profileId,
        string $admissionId,
    ): array {
        if (!$this->identifier($sourceId)
            || !$this->identifier($profileId)
            || !$this->identifier($admissionId)) {
            return $this->result('REFUSED', [], ['IDENTIFIER_INVALID']);
        }

        try {
            $source = $this->records->read(
                ProviderAssuranceEvidenceFixtureStore::SOURCES,
                $sourceId,
            );
            $profile = $this->records->read(
                ProviderAssuranceEvidenceFixtureStore::PROFILES,
                $profileId,
            );
            $admission = $this->records->read(
                ProviderAssuranceEvidenceFixtureStore::ADMISSIONS,
                $admissionId,
            );
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' === $exception->getMessage()) {
                return $this->result('INCOMPLETE', [], ['FIXTURE_ABSENT']);
            }

            return $this->result('CONFLICTED', [], [$exception->getMessage()]);
        }

        try {
            $this->validator->assertSource($source);
            $this->validator->assertProfile($profile, [$source]);
            $this->validator->assertAdmission($admission, $profile, [$source]);
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        return $this->result(
            'ELIGIBLE_OFFLINE_EVIDENCE',
            [
                'source' => $this->reference($source, 'source_id'),
                'profile' => $this->reference($profile, 'profile_id'),
                'admission' => $this->reference($admission, 'admission_id'),
            ],
            [],
        );
    }

    private function result(string $classification, array $chain, array $reasons): array
    {
        return [
            'classification' => $classification,
            'chain' => $chain,
            'reasons' => $reasons,
            'read_only' => true,
            'fixture_created' => false,
            'fixture_repaired' => false,
            'provider_truth_promoted' => false,
            'execution_authority_created' => false,
            'retry_authority_created' => false,
        ];
    }

    private function reference(array $record, string $idField): array
    {
        return [
            'id' => $record[$idField],
            'digest' => $record['record_digest'],
            'schema' => $record['schema'],
        ];
    }

    private function identifier(string $value): bool
    {
        return (bool) preg_match('/^[a-z0-9][a-z0-9._:\/-]{2,220}$/', $value);
    }
}
