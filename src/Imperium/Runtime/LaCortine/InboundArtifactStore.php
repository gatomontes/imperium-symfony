<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

final readonly class InboundArtifactStore
{
    public function __construct(private string $projectDir)
    {
    }

    public function persistOnce(string $providerMessageId, AdmittedInboundArtifact $artifact): bool
    {
        $directory = $this->projectDir.DIRECTORY_SEPARATOR.'var'.DIRECTORY_SEPARATOR.'lacortine'.DIRECTORY_SEPARATOR.'inbound';
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('INBOUND_STORE_UNAVAILABLE: Lazaretto inbound store could not be created.');
        }

        $path = $directory.DIRECTORY_SEPARATOR.hash('sha256', $providerMessageId).'.json';
        $handle = @fopen($path, 'x');
        if (false === $handle) {
            if (is_file($path)) {
                return false;
            }
            throw new \RuntimeException('INBOUND_STORE_UNAVAILABLE: admitted artifact could not reserve an idempotency record.');
        }

        try {
            $record = json_encode([
                'provider_message_id' => $providerMessageId,
                'artifact_id' => $artifact->artifactId,
                'raw_payload_id' => $artifact->rawPayloadId,
                'raw_payload_digest' => $artifact->rawPayloadDigest,
                'content' => $artifact->content,
                'provenance' => $artifact->provenance,
                'admitted_at' => $artifact->admittedAt->format(DATE_ATOM),
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

            if (false === fwrite($handle, $record)) {
                throw new \RuntimeException('INBOUND_STORE_WRITE_FAILED: admitted artifact could not be persisted.');
            }
        } catch (\Throwable $e) {
            @unlink($path);
            throw $e;
        } finally {
            fclose($handle);
        }

        return true;
    }
}
