<?php
declare(strict_types=1);
namespace App\ProtectedMission;

use App\Bootstrap\CanonicalJson;

/** Trusted Runtime implementation. OS access to root/code is the security boundary. */
final class AuthorityOwner
{
    public function __construct(private readonly string $root) {}

    /** Deployment-owner operation; deliberately not exposed by dispatch(). */
    public function enroll(array $publicTrust, string $confirmedFingerprint): array
    {
        $trust = PublicTrust::validate($publicTrust, $confirmedFingerprint);
        if (!is_dir($this->root) && !mkdir($this->root, 0700, true)) throw new \RuntimeException('PMA_STORAGE_FAILED');
        return $this->transaction(function (array &$state) use ($trust): array {
            if (isset($state['trust'])) throw new \RuntimeException('PMA_ALREADY_ENROLLED_RECOVERY_REQUIRED');
            $state['trust'] = $trust;
            $state['custody'] = ['enrolled_at'=>time(), 'fingerprint'=>$trust['fingerprint'], 'action'=>'explicit-owner-bootstrap'];
            return $state['custody'];
        }, true);
    }

    /** Narrow public protocol. Unknown fields fail closed; no root/verifier/clock adapters. */
    public function dispatch(array $request): array
    {
        if (array_keys($request) !== ['operation', 'arguments'] || !is_string($request['operation']) || !is_array($request['arguments'])) {
            throw new \RuntimeException('PMA_REQUEST_INVALID');
        }
        if ($request['operation'] !== 'trust' || $request['arguments'] !== []) throw new \RuntimeException('PMA_OPERATION_REFUSED');
        return $this->transaction(static fn(array &$state): array => $state['trust']);
    }

    /** One journal frame publishes the complete authority state under a common file lock.
     * Partial final frames are ignored. The next writer removes only that incomplete tail.
     * fflush/fsync precede successful return; volume/controller power-loss behavior is unproved.
     */
    private function transaction(callable $operation, bool $bootstrap = false): array
    {
        if (!$bootstrap && !is_file($this->root.'/authority.journal')) throw new \RuntimeException('PMA_TRUST_ABSENT');
        $handle = fopen($this->root.'/authority.journal', 'c+b');
        if ($handle === false || !flock($handle, LOCK_EX)) throw new \RuntimeException('PMA_LOCK_FAILED');
        try {
            $state = []; $valid = 0;
            while (($header = fgets($handle, 100)) !== false) {
                if (!preg_match('/^([0-9]{1,9}) ([a-f0-9]{64})\n$/D', $header, $match)) break;
                $length = (int)$match[1];
                if ($length > 16000000) throw new \RuntimeException('PMA_JOURNAL_LIMIT');
                $bytes = ''; while (strlen($bytes) < $length && !feof($handle)) $bytes .= fread($handle, $length - strlen($bytes));
                if (strlen($bytes) !== $length) break;
                if (!hash_equals($match[2], hash('sha256', $bytes))) throw new \RuntimeException('PMA_JOURNAL_CORRUPT');
                $state = json_decode($bytes, true, 512, JSON_THROW_ON_ERROR);
                $valid = ftell($handle);
            }
            if (!$bootstrap && !isset($state['trust'])) throw new \RuntimeException('PMA_TRUST_ABSENT');
            $before = CanonicalJson::encode($state);
            $result = $operation($state);
            $after = CanonicalJson::encode($state);
            if ($after !== $before) {
                if (strlen($after) > 16000000) throw new \RuntimeException('PMA_JOURNAL_LIMIT');
                ftruncate($handle, $valid); fseek($handle, $valid);
                $frame = strlen($after).' '.hash('sha256', $after)."\n".$after;
                $offset = 0;
                while ($offset < strlen($frame)) {
                    $written = fwrite($handle, substr($frame, $offset));
                    if ($written === false || $written === 0) throw new \RuntimeException('PMA_COMMIT_FAILED');
                    $offset += $written;
                }
                if (!fflush($handle) || !fsync($handle)) throw new \RuntimeException('PMA_COMMIT_FAILED');
            }
            return $result;
        } finally { flock($handle, LOCK_UN); fclose($handle); }
    }
}
