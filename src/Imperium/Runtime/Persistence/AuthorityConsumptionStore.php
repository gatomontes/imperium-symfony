<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

final readonly class AuthorityConsumptionStore
{
    private const DIRECTORY = 'var/imperium/runtime/authority-consumptions';

    public function __construct(
        private ImmutableRecordStore $records,
        private AtomicTransition $atomic,
    ) {
    }

    public function consume(
        string $authorityId,
        string $sourceId,
        string $sourceDigest,
        string $consumer,
        \DateTimeImmutable $at,
    ): array {
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:-]{2,220}$/', $authorityId)
            || '' === trim($sourceId)
            || !preg_match('/^(?:sha256:)?[a-f0-9]{64}$/', $sourceDigest)
            || '' === trim($consumer)) {
            throw new \InvalidArgumentException('PST130_AUTHORITY_CONSUMPTION_INPUT_INVALID');
        }
        $id = 'authority-consumption-'.hash('sha256', $authorityId);

        return $this->atomic->run('authority:'.hash('sha256', $authorityId), function () use ($id, $authorityId, $sourceId, $sourceDigest, $consumer, $at): array {
            try {
                $existing = $this->records->read(self::DIRECTORY, $id);
                if ($authorityId !== ($existing['authority_id'] ?? null)
                    || ['id' => $sourceId, 'digest' => $sourceDigest] !== ($existing['source'] ?? null)
                    || $consumer !== ($existing['consumer'] ?? null)) {
                    throw new \RuntimeException('PST131_AUTHORITY_CONSUMPTION_CONFLICT');
                }

                return $existing;
            } catch (\RuntimeException $exception) {
                if ('PST112_IMMUTABLE_RECORD_ABSENT' !== $exception->getMessage()) {
                    throw $exception;
                }
            }

            return $this->records->put(self::DIRECTORY, $id, [
                'schema' => 'imperium.runtime-authority-consumption/v1',
                'consumption_id' => $id,
                'authority_id' => $authorityId,
                'source' => ['id' => $sourceId, 'digest' => $sourceDigest],
                'consumer' => $consumer,
                'consumed_at' => $at->format(DATE_ATOM),
                'consumed' => true,
                'continuing_authority' => false,
                'sealed' => true,
            ]);
        });
    }
}
