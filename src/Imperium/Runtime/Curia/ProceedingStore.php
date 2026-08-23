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

    public function turns(string $proceedingId): array
    {
        $paths = glob($this->directory.'/'.$proceedingId.'.turn.*.json') ?: [];
        sort($paths, SORT_STRING);

        return array_map(
            static fn (string $path): array => json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR),
            $paths,
        );
    }

    public function findTurn(string $proceedingId, string $responseId): ?array
    {
        foreach ($this->turns($proceedingId) as $turn) {
            if ($responseId === ($turn['response_id'] ?? null)) {
                return $turn;
            }
        }

        return null;
    }

    public function turn(string $proceedingId, int $sequence): ?array
    {
        foreach ($this->turns($proceedingId) as $turn) {
            if ($sequence === ($turn['sequence'] ?? null)) {
                return $turn;
            }
        }

        return null;
    }

    public function acts(string $proceedingId): array
    {
        $paths = glob($this->directory.'/'.$proceedingId.'.act.*.json') ?: [];
        sort($paths, SORT_STRING);

        return array_map(
            static fn (string $path): array => json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR),
            $paths,
        );
    }

    public function commissions(string $proceedingId): array
    {
        $paths = glob($this->directory.'/'.$proceedingId.'.commission.*.json') ?: [];
        sort($paths, SORT_STRING);

        return array_map(
            static fn (string $path): array => json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR),
            $paths,
        );
    }

    public function persistCommission(string $proceedingId, string $commissionId, array $commission): array
    {
        if (!preg_match('/^[a-zA-Z0-9._-]{8,80}$/', $commissionId)) {
            throw new \InvalidArgumentException('Commission identity must contain 8–80 safe identifier characters.');
        }
        $handle = fopen($this->directory.'/'.$proceedingId.'.lock', 'c+');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            throw new \RuntimeException('Curian proceeding lock cannot be acquired.');
        }
        try {
            $path = $this->directory.'/'.$proceedingId.'.commission.'.$commissionId.'.json';
            $commission['commission_id'] = $commissionId;
            $commission['record_digest'] = hash('sha256', CanonicalJson::encode($commission));
            if (is_file($path)) {
                $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (CanonicalJson::encode($existing) !== CanonicalJson::encode($commission)) {
                    throw new \RuntimeException('C40_COMMISSION_REPLAY_CONFLICT: commission identity is already bound differently.');
                }

                return $existing;
            }
            $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
            $json = json_encode($commission, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
            if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
                @unlink($temporary);
                throw new \RuntimeException('Curian commission cannot be committed atomically.');
            }

            return $commission;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function persistAct(string $proceedingId, string $actId, array $act): array
    {
        if (!preg_match('/^[a-zA-Z0-9._-]{8,80}$/', $actId)) {
            throw new \InvalidArgumentException('Act identity must contain 8–80 safe identifier characters.');
        }
        $handle = fopen($this->directory.'/'.$proceedingId.'.lock', 'c+');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            throw new \RuntimeException('Curian proceeding lock cannot be acquired.');
        }
        try {
            $path = $this->directory.'/'.$proceedingId.'.act.'.$actId.'.json';
            $act['act_id'] = $actId;
            $act['record_digest'] = hash('sha256', CanonicalJson::encode($act));
            if (is_file($path)) {
                $existing = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
                if (CanonicalJson::encode($existing) !== CanonicalJson::encode($act)) {
                    throw new \RuntimeException('C30_ACT_REPLAY_CONFLICT: Imperator act identity is already bound differently.');
                }

                return $existing;
            }
            $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
            $json = json_encode($act, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
            if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
                @unlink($temporary);
                throw new \RuntimeException('Imperator act cannot be committed atomically.');
            }

            return $act;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function appendTurn(string $proceedingId, string $responseId, int $expectedSequence, array $turn): array
    {
        $lockPath = $this->directory.'/'.$proceedingId.'.lock';
        $handle = fopen($lockPath, 'c+');
        if (false === $handle || !flock($handle, LOCK_EX)) {
            throw new \RuntimeException('Curian proceeding lock cannot be acquired.');
        }
        try {
            $existing = $this->findTurn($proceedingId, $responseId);
            if (null !== $existing) {
                return $existing;
            }
            $sequence = count($this->turns($proceedingId)) + 1;
            if ($sequence !== $expectedSequence) {
                throw new \RuntimeException('C20_PROCEEDING_CHANGED: another turn was committed during deliberation.');
            }
            $turn['sequence'] = $sequence;
            $turn['record_digest'] = hash('sha256', CanonicalJson::encode($turn));
            $path = sprintf('%s/%s.turn.%06d.json', $this->directory, $proceedingId, $sequence);
            $temporary = $path.'.tmp.'.bin2hex(random_bytes(6));
            $json = json_encode($turn, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
            if (false === file_put_contents($temporary, $json, LOCK_EX) || !rename($temporary, $path)) {
                @unlink($temporary);
                throw new \RuntimeException('Curian turn cannot be committed atomically.');
            }

            return $turn;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
