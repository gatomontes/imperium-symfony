<?php

declare(strict_types=1);

namespace App\Imperium\Runtime\LaCortine;

use App\Imperium\Runtime\Sortie\SortieManifestCodec;

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

            [$exitCode, $stdout, $stderr] = $this->runChild([
                PHP_BINARY,
                $this->projectDir.DIRECTORY_SEPARATOR.'bin'.DIRECTORY_SEPARATOR.'console',
                'imperium:sortie:run',
                $manifestFile,
                $envelope->manifestDigest,
                $outputFile,
                '--env=sortie',
                '--no-debug',
            ]);

            if (0 !== $exitCode) {
                throw new \RuntimeException('SORTIE_PROCESS_REFUSED: '.trim($stderr.' '.$stdout));
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

    /**
     * @param list<string> $command
     * @return array{0:int,1:string,2:string}
     */
    private function runChild(array $command): array
    {
        if (!function_exists('proc_open')) {
            throw new \RuntimeException('SORTIE_PROCESS_UNAVAILABLE: proc_open is unavailable in this PHP runtime.');
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $pipes = [];
        $process = proc_open($command, $descriptors, $pipes, $this->projectDir, $this->sortieEnvironment());
        if (!is_resource($process)) {
            throw new \RuntimeException('SORTIE_PROCESS_START_FAILED: child runtime could not be created.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            is_int($exitCode) ? $exitCode : 1,
            false === $stdout ? '' : $stdout,
            false === $stderr ? '' : $stderr,
        ];
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

        // Windows needs its system root to initialize child processes reliably.
        $systemRoot = $_SERVER['SystemRoot'] ?? $_ENV['SystemRoot'] ?? getenv('SystemRoot');
        if (is_string($systemRoot) && '' !== $systemRoot) {
            $environment['SystemRoot'] = $systemRoot;
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
