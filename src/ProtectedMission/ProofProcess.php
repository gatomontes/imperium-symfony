<?php
declare(strict_types=1);
namespace App\ProtectedMission;

/** Disposable proof transport. Never retries a child or exposes its private streams. */
final class ProofProcess
{
    /** Loop over short writes; a closed pipe is evidence, not a PHP notice. */
    public static function writeInput($stream, string $input): bool
    {
        $offset = 0;
        while ($offset < strlen($input)) {
            // Suppress only this write's OS warning, which is represented by false/zero.
            $written = @fwrite($stream, substr($input, $offset, 4096));
            if ($written === false || $written === 0) return false;
            $offset += $written;
        }
        return true;
    }

    public static function run(array $command, string $input = ''): array
    {
        $process = proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
        if (!is_resource($process)) throw new \RuntimeException('PROOF_PROCESS_START_FAILED');
        try {
            $delivered = self::writeInput($pipes[0], $input);
            fclose($pipes[0]);
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $exit = proc_close($process);
            return ['exit_code'=>$exit, 'input_delivery'=>$delivered ? 'complete' : 'incomplete',
                'output'=>$output, 'error'=>$error];
        } finally {
            foreach ($pipes as $pipe) if (is_resource($pipe)) fclose($pipe);
            if (is_resource($process)) proc_close($process);
        }
    }

    /** Expected early refusal is explicit and never grants success to a normal call. */
    public static function check(array $result, string $operation, ?string $expectedRefusal = null): array
    {
        if (!preg_match('/^[a-z-]+$/D', $operation)) throw new \RuntimeException('PROOF_OPERATION_LABEL_INVALID');
        $accepted = $expectedRefusal === null
            ? $result['exit_code'] === 0 && $result['input_delivery'] === 'complete' && $result['error'] === '' && is_string($result['output'])
            : $result['exit_code'] === 2 && $result['output'] === '' && $result['error'] === $expectedRefusal."\n";
        $diagnostic = ['operation'=>$operation, 'exit_code'=>$result['exit_code'],
            'input_delivery'=>$result['input_delivery'], 'outcome'=>$accepted ? ($expectedRefusal === null ? 'success' : 'expected_refusal') : 'refused'];
        if (!$accepted) throw new \RuntimeException('PROOF_PROCESS_REFUSED '.json_encode($diagnostic, JSON_THROW_ON_ERROR));
        return $diagnostic;
    }
}
