<?php
declare(strict_types=1);
namespace App\ProtectedMission;

/** Trusted state-root mapping only; never accepts request paths or environment roots. */
final class ScratchWorkspace
{
    public static function root(string $stateRoot): string { return rtrim($stateRoot, '/\\').'Scratch'; }

    public static function assertClean(string $stateRoot): void
    {
        $root=self::root($stateRoot);
        self::plain($root);
        if (!is_dir($root)) throw new \RuntimeException('PMA_SCRATCH_ROOT_REQUIRED');
        if ((new \FilesystemIterator($root))->valid()) throw new \RuntimeException('PMA_SCRATCH_ABANDONED_OR_BUSY');
    }

    /** The authority journal lock serializes callers. Cleanup precedes publication. */
    public static function run(string $stateRoot, callable $operation): array
    {
        self::assertClean($stateRoot);
        $root=self::root($stateRoot);
        $path=$root.'/work-'.bin2hex(random_bytes(16));
        if (!@mkdir($path,0700)) throw new \RuntimeException('PMA_SCRATCH_CREATE_FAILED');
        try { return $operation($path); }
        finally {
            try {
                self::plain($root);
                $paths=[];
                self::inventory($path,$paths);
                // Preflight the entire exact workspace before deleting any byte.
                foreach ($paths as [$entry,$directory]) {
                    self::plain($entry);
                    if (!($directory ? @rmdir($entry) : @unlink($entry))) throw new \RuntimeException('PMA_SCRATCH_REMOVE_FAILED');
                }
            } catch (\Throwable $error) {
                // Remaining workspace is the incident marker; never erase unknown state.
                throw new \RuntimeException('PMA_SCRATCH_CLEANUP_FAILED',0,$error);
            }
        }
    }

    private static function inventory(string $path,array &$paths,int $depth=0): void
    {
        self::plain($path);
        if ($depth>24 || count($paths)>4096) throw new \RuntimeException('PMA_SCRATCH_INVENTORY_LIMIT');
        $directory=is_dir($path);
        if ($directory) foreach (new \FilesystemIterator($path) as $item) self::inventory($item->getPathname(),$paths,$depth+1);
        elseif (!is_file($path)) throw new \RuntimeException('PMA_SCRATCH_NONREGULAR');
        $paths[]=[$path,$directory];
    }

    private static function plain(string $path): void
    {
        clearstatcache(true);
        $resolved=realpath($path);
        if ($resolved===false) throw new \RuntimeException('PMA_SCRATCH_ROOT_REQUIRED');
        $normalize=static fn(string $s):string=>rtrim(str_replace('\\','/',$s),'/');
        $expected=$normalize($path);$actual=$normalize($resolved);
        if (PHP_OS_FAMILY==='Windows') {$expected=strtolower($expected);$actual=strtolower($actual);}
        if ($actual!==$expected) throw new \RuntimeException('PMA_SCRATCH_PATH_SUBSTITUTED');
        for ($cursor=$path;;$cursor=dirname($cursor)) {
            if (is_link($cursor)) throw new \RuntimeException('PMA_SCRATCH_REPARSE');
            if (dirname($cursor)===$cursor) break;
        }
    }
}
