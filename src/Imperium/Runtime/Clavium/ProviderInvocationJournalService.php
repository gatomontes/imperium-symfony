<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Clavium;

use App\Bootstrap\CanonicalJson;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class ProviderInvocationJournalService
{
    private string $claims;
    private string $journal;
    private string $lockPath;

    public function __construct(#[Autowire('%kernel.project_dir%')] string $root)
    {
        $this->claims = $root.'/var/imperium/runtime/provider-invocations';
        $this->journal = $root.'/var/imperium/runtime/provider-invocation-journal';
        $this->lockPath = $root.'/var/imperium/runtime/provider-invocation-journal.lock';
    }

    public function start(array $claim, \DateTimeImmutable $at): array
    {
        return $this->locked(function () use ($claim, $at): array {
            $authoritative = $this->authoritativeClaim($claim);
            $path = $this->path($authoritative['claim_id']);
            if (is_file($path)) {
                throw new \RuntimeException('CLV412_PROVIDER_INVOCATION_ALREADY_STARTED');
            }

            return $this->write($path, [
                'schema' => 'imperium.clavium-provider-invocation-journal/v1',
                'claim' => ['id' => $authoritative['claim_id'], 'digest' => $authoritative['record_digest']],
                'idempotency_key' => $authoritative['provider_request']['idempotency_key'],
                'external_io_started' => true,
                'provider_response_identity' => null,
                'started_at' => $at->format(DATE_ATOM),
                'resolved_at' => null,
                'status' => 'INVOCATION_IN_FLIGHT',
                'automatic_replay_permitted' => false,
                'sealed' => true,
            ]);
        });
    }

    public function sealResponse(array $claim, string $response, \DateTimeImmutable $at): array
    {
        return $this->transition($claim, 'INVOCATION_IN_FLIGHT', function (array $record) use ($response, $at): array {
            $record['provider_response_identity'] = 'sha256:'.hash('sha256', $response);
            $record['resolved_at'] = $at->format(DATE_ATOM);
            $record['status'] = 'PROVIDER_RESPONSE_IDENTITY_SEALED_PENDING_RESULT_PROCESSING';

            return $record;
        });
    }

    public function markUnknown(array $claim, \DateTimeImmutable $at): array
    {
        return $this->transition($claim, 'INVOCATION_IN_FLIGHT', function (array $record) use ($at): array {
            $record['resolved_at'] = $at->format(DATE_ATOM);
            $record['status'] = 'PROVIDER_OUTCOME_UNKNOWN_REPLAY_PROHIBITED';

            return $record;
        });
    }

    private function transition(array $claim, string $expected, callable $change): array
    {
        return $this->locked(function () use ($claim, $expected, $change): array {
            $authoritative = $this->authoritativeClaim($claim);
            $path = $this->path($authoritative['claim_id']);
            $record = $this->read($path, 'CLV413_PROVIDER_INVOCATION_JOURNAL_ABSENT');
            if (!$this->valid($record)
                || $expected !== ($record['status'] ?? null)
                || ($record['claim']['digest'] ?? null) !== $authoritative['record_digest']) {
                throw new \RuntimeException('CLV414_PROVIDER_INVOCATION_JOURNAL_TRANSITION_INVALID');
            }
            unset($record['record_digest']);

            return $this->write($path, $change($record));
        });
    }

    private function authoritativeClaim(array $claim): array
    {
        $id = $claim['claim_id'] ?? null;
        if (!is_string($id) || !preg_match('/^provider-invocation-[a-f0-9]{20}$/', $id)) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }
        $authoritative = $this->read($this->claims.'/'.$id.'.json', 'CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        if (!$this->valid($authoritative)
            || CanonicalJson::encode($claim) !== CanonicalJson::encode($authoritative)
            || 'INVOCATION_CLAIMED_PENDING_EXTERNAL_IO' !== ($authoritative['status'] ?? null)
            || true !== ($authoritative['lease_consumption']['consumed'] ?? null)
            || true !== ($authoritative['turn_authority_consumption']['consumed'] ?? null)
            || false !== ($authoritative['provider_request']['external_io_started'] ?? null)
            || false !== ($authoritative['recovery']['automatic_replay_permitted'] ?? null)) {
            throw new \RuntimeException('CLV410_PROVIDER_INVOCATION_CLAIM_INVALID');
        }

        return $authoritative;
    }

    private function locked(callable $operation): array
    {
        if (!is_dir($this->journal) && !mkdir($this->journal, 0770, true) && !is_dir($this->journal)) {
            throw new \RuntimeException('CLV415_PROVIDER_INVOCATION_JOURNAL_STORAGE_FAILED');
        }
        $lock = fopen($this->lockPath, 'c+');
        if (false === $lock || !flock($lock, LOCK_EX)) {
            if (is_resource($lock)) {
                fclose($lock);
            }
            throw new \RuntimeException('CLV411_PROVIDER_INVOCATION_JOURNAL_LOCK_FAILED');
        }
        try {
            return $operation();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function path(string $claimId): string
    {
        return $this->journal.'/'.$claimId.'.json';
    }

    private function write(string $path, array $record): array
    {
        $record['record_digest'] = hash('sha256', CanonicalJson::encode($record));
        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('CLV415_PROVIDER_INVOCATION_JOURNAL_STORAGE_FAILED');
        }

        return $record;
    }

    private function read(string $path, string $error): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException($error);
        }

        return json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    private function valid(array $record): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, hash('sha256', CanonicalJson::encode($record)));
    }
}
