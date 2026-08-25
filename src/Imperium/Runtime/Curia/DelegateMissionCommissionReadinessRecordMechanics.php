<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionCommissionReadinessRecordMechanics
{
    private ImmutableRecordStore $records;
    private RecordReferenceValidator $validator;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $root,
        ?ImmutableRecordStore $records = null,
        ?RecordReferenceValidator $validator = null,
    ) {
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
        $this->validator = $validator ?? new RecordReferenceValidator($root);
    }

    public function read(string $absolutePath, string $absentError): array
    {
        return $this->validator->read($absolutePath, $absentError);
    }

    public function isIntact(array $record): bool
    {
        return $this->validator->isIntact($record);
    }

    public function saveCommission(string $id, array $record): array
    {
        return $this->put(
            'var/imperium/offices/curia/delegate-mission-bounded-cognition-commissions',
            $id,
            $record,
            'C268_DELEGATE_MISSION_COGNITION_COMMISSION_FAILED',
            'C269_DELEGATE_MISSION_COGNITION_COMMISSION_CONFLICT',
        );
    }

    public function saveReadiness(string $id, array $record): array
    {
        return $this->put(
            'var/imperium/offices/curia/delegate-mission-resource-invocation-readiness-assessments',
            $id,
            $record,
            'C278_DELEGATE_MISSION_READINESS_ASSESSMENT_FAILED',
            'C279_DELEGATE_MISSION_READINESS_CONFLICT',
        );
    }

    private function put(
        string $directory,
        string $id,
        array $record,
        string $failedError,
        string $conflictError,
    ): array {
        try {
            return $this->records->put($directory, $id, $record);
        } catch (\RuntimeException $exception) {
            throw match ($exception->getMessage()) {
                'PST111_IMMUTABLE_RECORD_CONFLICT' => new \RuntimeException($conflictError, previous: $exception),
                'PST114_IMMUTABLE_RECORD_COMMIT_FAILED' => new \RuntimeException($failedError, previous: $exception),
                default => $exception,
            };
        }
    }
}
