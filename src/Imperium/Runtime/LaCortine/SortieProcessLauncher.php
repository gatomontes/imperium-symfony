<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Sortie\SortieManifestCodec;
use Symfony\Component\Process\Process;

final class SortieProcessLauncher
{
    public function __construct(
        private readonly SortieManifestCodec $manifestCodec,
        private readonly RawExternalPayloadCodec $payloadCodec,
        private readonly string $projectDir,
    ) {
    }

    public function launch(BoundaryDispatch $dispatch): RawExternalPayload
    {
        if (OutboundExecutionMode::Sortie !== $dispatch->mode || null === $dispatch->sortie) {
            throw new \RuntimeException('SORTIE_LAUNCH_MODE_MISMATCH: only an Iron Gate sortie dispatch may create external cognition.');
        }

        $envelope = $this->manifestCodec->seal($dispatch->sortie);
        $encoded = $this->manifestCodec->encode($envelope);
        $dir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'imperium-sortie-'.bin2hex(random_bytes(12));
        if (!mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new \RuntimeException('SORTIE_WORKSPACE_FAILED: ephemeral workspace could not be created.');
        }

        $manifestFile = $dir.DIRECTORY_SEPARATOR.'manifest.json';
        $outputFile = $dir.DIRECTORY_SEPARATOR.'payload.json';

        try {
            if (false === file_put_contents($manifestFile, $encoded, LOCK_EX)) {
                throw new \RuntimeException('SORTIE_MANIFEST_WRITE_FAILED: sealed manifest could not be staged.');
            }

            $process = new Process([
                PHP_BINARY,
                $this->projectDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console',
                'imperium:sortie:run',
                $manifestFile,
                $envelope->manifestDigest,
                $outputFile,
                '--env=sortie',
                '--no-debug',
            ], $this->projectDir, $this->sortieEnvironment());
            $process->setTimeout(120);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException('SORTIE_PROCESS_REFUSED: '.trim($process->getErrorOutput().' '.$process->getOutput()));
            }
            if (!is_file($outputFile) || !is_readable($outputFile)) {
                throw new \RuntimeException('SORTIE_RETURN_MISSING: child process returned no raw payload artifact.');
            }

            $bytes = file_get_contents($outputFile);
            if (false === $bytes) {
                throw new \RuntimeException('SORTIE_RETURN_UNREADABLE: raw payload artifact could not be read.');
            }

            $payload = $this->payloadCodec->decode($bytes);
            if ($payload->executionId !== $dispatch->executionId) {
                throw new \RuntimeException('SORTIE_RETURN_EXECUTION_MISMATCH: child payload does not belong to the exact Iron Gate execution.');
            }

            return $payload;
        } finally {
            $this->destroy($manifestFile);
            $this->destroy($outputFile);
            @rmdir($dir);
        }
    }

    /** @return array<string, string> */
    private function sortieEnvironment(): array
    {
        $environment = [
            'APP_ENV' => 'sortie',
            'APP_DEBUG' => '0',
        ];

        // Provider authentication is explicitly allow-listed; the child does not inherit
        // the parent process environment wholesale.
        $deepSeekKey = $_SERVER['DEEPSEEK_API_KEY'] ?? $_ENV['DEEPSEEK_API_KEY'] ?? getenv('DEEPSEEK_API_KEY');
        if (is_string($deepSeekKey) && '' !== $deepSeekKey) {
            $environment['DEEPSEEK_API_KEY'] = $deepSeekKey;
        }

        return $environment;
    }

    private function destroy(string $path): void
    {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
