<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Persistence\ImmutableRecordStore;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class DelegateMissionResultReturnRecordMechanics
{
    private ImmutableRecordStore $records;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] private string $root,
        ?ImmutableRecordStore $records = null,
    ) {
        $this->records = $records ?? new ImmutableRecordStore($root, new AtomicTransition($root));
    }

    public function read(string $absolutePath, string $absentError): array
    {
        if (!str_starts_with($absolutePath, $this->root.'/var/imperium/') || str_contains($absolutePath, '..')) {
            throw new \InvalidArgumentException('C290_DELEGATE_RESULT_RETURN_RECORD_PATH_INVALID');
        }
        if (!is_file($absolutePath)) {
            throw new \RuntimeException($absentError);
        }

        return json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
    }

    public function isIntact(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }

    public function source(string $directory, array $reference, string $absentError, string $chainError): array
    {
        $record = $this->read($directory.'/'.($reference['id'] ?? '').'.json', $absentError);
        if (($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)) {
            throw new \RuntimeException($chainError);
        }

        return $record;
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
