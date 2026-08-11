<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\Curia;

use App\Bootstrap\CanonicalJson;

final readonly class ProceedingStore
{
    private string $directory;

    public function __construct(string $projectDir)
    {
        $this->directory = $projectDir.'/var/imperium/curia/proceedings';
    }

    public function persist(array $proceeding): array
    {
        $id = $proceeding['proceeding_id'] ?? null;
        if (!is_string($id) || '' === $id) {
            throw new \InvalidArgumentException('Curian proceeding identity is required.');
        }
        if (!is_dir($this->directory) && !mkdir($this->directory, 0770, true) && !is_dir($this->directory)) {
            throw new \RuntimeException('Curian proceeding directory cannot be created.');
        }

        $path = $this->directory.'/'.$id.'.json';
        if (is_file($path)) {
            $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
            if (CanonicalJson::encode($existing) !== CanonicalJson::encode($proceeding)) {
                throw new \RuntimeException('Curian proceeding replay conflicts with its durable record.');
            }

            return $existing;
        }

        $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
        $json = json_encode($proceeding, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
            @unlink($temporary);
            throw new \RuntimeException('Curian proceeding cannot be committed atomically.');
        }

        return $proceeding;
    }

    public function find(string $proceedingId): ?array
    {
        $path = $this->directory.'/'.$proceedingId.'.json';
        if (!is_file($path)) {
            return null;
        }

        $record = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        return is_array($record) ? $record : null;
    }
}
