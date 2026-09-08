<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;

use App\Imperium\Runtime\Citadel\Authority\AuthorityInput as A;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/** Trusted local custody; hashes detect corruption, not administrator rollback. */
final readonly class NativeJournal
{
    public function __construct(#[Autowire('%kernel.project_dir%')] public string $root, private ?\Closure $checkpoint = null) {}
    public function inspect(callable $read): mixed { return NativeBoundary::lock($this->root, fn () => $read($this->latest())); }
    public function change(callable $change): mixed
    {
        return NativeBoundary::lock($this->root, function () use ($change): mixed {
            $prior = $this->latest(); $state = $prior['state'];
            $result = $change($state);
            if (A::digest($state) === A::digest($prior['state'])) { return $result; }
            ($this->checkpoint)?->__invoke('before-commit');
            $frame = A::seal(['schema' => 'imperium.native-authority-frame/v1', 'generation' => $prior['generation'] + 1,
                'previous_digest' => $prior['record_digest'], 'state' => $state]);
            $bytes = json_encode($frame, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n";
            A::require(strlen($bytes) <= 1048576 && $frame['generation'] <= 1024, 'NAT003_REGISTRY_CAPACITY');
            $dir = $this->root.'/var/imperium/native-authority';
            if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) { throw new \RuntimeException('NAT004_STORAGE_UNAVAILABLE'); }
            $path = sprintf('%s/%012d.json', $dir, $frame['generation']);
            $temp = $path.'.pending'; $h = @fopen($temp, 'xb');
            A::require(is_resource($h), 'NAT005_UNKNOWN_OUTCOME_FENCED');
            try {
                $offset = 0;
                while ($offset < strlen($bytes)) {
                    $n = fwrite($h, substr($bytes, $offset)); A::require(is_int($n) && $n > 0, 'NAT005_UNKNOWN_OUTCOME_FENCED'); $offset += $n;
                }
                A::require(fflush($h) && fsync($h), 'NAT005_UNKNOWN_OUTCOME_FENCED');
            } finally { fclose($h); }
            ($this->checkpoint)?->__invoke('pending-durable');
            A::require(!file_exists($path) && rename($temp, $path), 'NAT005_UNKNOWN_OUTCOME_FENCED');
            ($this->checkpoint)?->__invoke('after-commit');
            return $result;
        });
    }
    private function latest(): array
    {
        $dir = $this->root.'/var/imperium/native-authority';
        A::require(!glob($dir.'/*.pending'), 'NAT005_UNKNOWN_OUTCOME_FENCED');
        $prior = ['generation' => 0, 'record_digest' => null, 'state' => []];
        $files = glob($dir.'/*.json') ?: []; sort($files, SORT_STRING);
        A::require(count($files) <= 1024 && (!is_dir($dir) || $files !== []), 'NAT006_REGISTRY_CHAIN_INVALID');
        foreach ($files as $path) {
            $f = A::read($path); A::intact($f);
            A::keys($f, ['schema', 'generation', 'previous_digest', 'state', 'record_digest']);
            A::require($f['schema'] === 'imperium.native-authority-frame/v1' && $f['generation'] === $prior['generation'] + 1
                && $f['previous_digest'] === $prior['record_digest'] && basename($path) === sprintf('%012d.json', $f['generation'])
                && is_array($f['state']), 'NAT006_REGISTRY_CHAIN_INVALID');
            $prior = $f;
        }
        return $prior;
    }
}
