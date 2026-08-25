<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use App\Imperium\Runtime\Persistence\RecordReferenceValidator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionResultReturnRecordMechanics
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

    public function source(string $directory, array $reference, string $absentError, string $chainError): array
    {
        return $this->validator->resolve($directory, $reference, $absentError, $chainError);
    }

    public function saveDisposition(string $id, array $record): array
    {
        return $this->put(
            'var/imperium/offices/curia/delegate-mission-cognition-result-dispositions',
            $id,
            $record,
            'C308_DELEGATE_RESULT_DISPOSITION_WRITE_FAILED',
            'C309_DELEGATE_RESULT_DISPOSITION_CONFLICT',
        );
    }

    public function saveReturnAuthorization(string $id, array $record): array
    {
        return $this->put(
            'var/imperium/offices/curia/delegate-mission-return-authorizations',
            $id,
            $record,
            'C318_DELEGATE_RETURN_AUTHORIZATION_WRITE_FAILED',
            'C319_DELEGATE_RETURN_AUTHORIZATION_CONFLICT',
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
