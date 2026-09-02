<?php

declare(strict_types=1);

namespace App\ReproofV2;

/** Append-only package publication. No deletion, overwrite, resume or execution retry. */
final class PackageStore
{
    public function reserve(string $parent, string $proofId): string
    {
        $root = realpath($parent);
        if (false === $root || !is_dir($root) || is_link($parent)
            || !preg_match('/^reproof-v2-[a-z0-9-]{3,80}$/D', $proofId)) {
            throw new \RuntimeException('REPROOF_DESTINATION_INVALID');
        }
        $directory = $root.DIRECTORY_SEPARATOR.$proofId;
        if (!@mkdir($directory, 0700)) { throw new \RuntimeException('REPROOF_RESERVATION_EXISTS_OR_FAILED'); }
        $this->write($directory.'/reservation.json', ['proof_id' => $proofId, 'state' => 'RESERVED']);
        return $directory;
    }

    public function publish(string $directory, array $package): void
    {
        try { $reservation = json_decode($this->readBytes($directory.'/reservation.json'), true, flags: JSON_THROW_ON_ERROR); }
        catch (\Throwable) { throw new \RuntimeException('REPROOF_RESERVATION_MISMATCH'); }
        if ($reservation !== ['proof_id' => $package['receipt']['proof_id'], 'state' => 'RESERVED']
            || $package['candidate']['proof_id'] !== $reservation['proof_id']) {
            throw new \RuntimeException('REPROOF_RESERVATION_MISMATCH');
        }
        $this->write($directory.'/receipt.json', $package['receipt']);
        $this->write($directory.'/candidate.json', $package['candidate']);
        $this->write($directory.'/finalized.json', ['proof_id' => $reservation['proof_id'],
            'receipt_digest' => hash_file('sha256', $directory.'/receipt.json'),
            'candidate_digest' => hash_file('sha256', $directory.'/candidate.json')]);
    }

    public function readFinalized(string $directory): array
    {
        try {
            $final = $this->readBytes($directory.'/finalized.json');
            $manifest = json_decode($final, true, flags: JSON_THROW_ON_ERROR);
            if (!is_array($manifest) || array_keys($manifest) !== ['proof_id', 'receipt_digest', 'candidate_digest']) {
                throw new \RuntimeException();
            }
            $package = [];
            foreach (['receipt', 'candidate'] as $name) {
                $bytes = $this->readBytes($directory.'/'.$name.'.json');
                if (hash('sha256', $bytes) !== $manifest[$name.'_digest']) { throw new \RuntimeException(); }
                $package[$name] = json_decode($bytes, true, flags: JSON_THROW_ON_ERROR);
                if (($package[$name]['proof_id'] ?? null) !== $manifest['proof_id']) { throw new \RuntimeException(); }
            }
            return $package;
        } catch (\Throwable) { throw new \RuntimeException('REPROOF_PACKAGE_INCOMPLETE'); }
    }

    private function write(string $path, array $record): void
    {
        $bytes = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
        $stream = @fopen($path, 'xb');
        if (false === $stream) { throw new \RuntimeException('REPROOF_WRITE_REFUSED'); }
        try {
            $offset = 0;
            while ($offset < strlen($bytes)) {
                $written = fwrite($stream, substr($bytes, $offset));
                if (false === $written || 0 === $written) { throw new \RuntimeException('REPROOF_WRITE_INCOMPLETE'); }
                $offset += $written;
            }
            if (!fflush($stream) || !fsync($stream)) { throw new \RuntimeException('REPROOF_FLUSH_FAILED'); }
        } finally { fclose($stream); }
    }

    private function readBytes(string $path): string
    {
        $size = @filesize($path);
        if (false === $size || $size > 8000000 || is_link($path)) { throw new \RuntimeException('REPROOF_PACKAGE_INCOMPLETE'); }
        $bytes = @file_get_contents($path, false, null, 0, 8000001);
        if (false === $bytes || strlen($bytes) > 8000000) { throw new \RuntimeException('REPROOF_PACKAGE_INCOMPLETE'); }
        return $bytes;
    }
}
