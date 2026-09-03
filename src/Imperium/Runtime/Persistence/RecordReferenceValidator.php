<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Persistence;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\ProviderTransition\NativeBindingReader;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class RecordReferenceValidator
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $root, private ?NativeBindingReader $legacyBinding = null)
    {
    }

    public function read(string $absolutePath, string $absentError): array
    {
        $this->requirePath($absolutePath);
        if (!is_file($absolutePath)) {
            throw new \RuntimeException($absentError);
        }

        $record = json_decode((string) file_get_contents($absolutePath), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($record)) {
            throw new \RuntimeException('PST130_RECORD_INVALID');
        }

        $this->legacyBinding?->assertLegacyRecord($record);
        return $record;
    }

    public function isIntact(array $record, string $digestPrefix = ''): bool
    {
        $digest = $record['record_digest'] ?? null;
        unset($record['record_digest']);

        return is_string($digest) && hash_equals($digest, $digestPrefix.hash('sha256', CanonicalJson::encode($record)));
    }

    public function requireIntact(array $record, string $error): array
    {
        if (!$this->isIntact($record)) {
            throw new \RuntimeException($error);
        }

        return $record;
    }

    public function resolve(
        string $absoluteDirectory,
        array $reference,
        string $absentError,
        string $mismatchError,
        ?string $identityField = null,
    ): array {
        $id = $reference['id'] ?? null;
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{2,220}$/', $id)) {
            throw new \RuntimeException($mismatchError);
        }
        $record = $this->read($absoluteDirectory.'/'.$id.'.json', $absentError);
        if (!$this->isIntact($record)
            || ($reference['digest'] ?? null) !== ($record['record_digest'] ?? null)
            || (null !== $identityField && $id !== ($record[$identityField] ?? null))) {
            throw new \RuntimeException($mismatchError);
        }

        return $record;
    }

    private function requirePath(string $absolutePath): void
    {
        if (!str_starts_with($absolutePath, $this->root.'/var/imperium/') || str_contains($absolutePath, '..')) {
            throw new \InvalidArgumentException('PST131_RECORD_PATH_INVALID');
        }
    }
}
