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

        $sourceRead = $this->read(
            ProviderAssuranceEvidenceFixtureStore::SOURCES,
            $sourceId,
        );
        if (null !== $sourceRead['classification']) {
            return $this->result($sourceRead['classification'], [], $sourceRead['reasons']);
        }
        $source = $sourceRead['record'];

        try {
            $this->validator->assertSource($source);
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $profileRead = $this->read(
            ProviderAssuranceEvidenceFixtureStore::PROFILES,
            $profileId,
        );
        if (null !== $profileRead['classification']) {
            return $this->result($profileRead['classification'], [], $profileRead['reasons']);
        }
        $profile = $profileRead['record'];

        try {
            $this->validator->assertProfile($profile, [$source]);
        } catch (\RuntimeException $exception) {
            return $this->result('REFUSED', [], [$exception->getMessage()]);
        }

        $admissionRead = $this->read(
            ProviderAssuranceEvidenceFixtureStore::ADMISSIONS,
            $admissionId,
        );
        if (null !== $admissionRead['classification']) {
            return $this->result($admissionRead['classification'], [], $admissionRead['reasons']);
        }
        $admission = $admissionRead['record'];

        try {
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

    private function read(string $directory, string $id): array
    {
        try {
            return [
                'record' => $this->records->read($directory, $id),
                'classification' => null,
                'reasons' => [],
            ];
        } catch (\RuntimeException $exception) {
            if ('PST112_IMMUTABLE_RECORD_ABSENT' === $exception->getMessage()) {
                return [
                    'record' => null,
                    'classification' => 'INCOMPLETE',
                    'reasons' => ['FIXTURE_ABSENT'],
                ];
            }

            return [
                'record' => null,
                'classification' => 'CONFLICTED',
                'reasons' => [$exception->getMessage()],
            ];
        }
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
