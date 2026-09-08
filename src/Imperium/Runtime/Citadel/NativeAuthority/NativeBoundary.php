<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;

use App\Imperium\Runtime\Persistence\AtomicTransition;

/** One cooperating-process boundary, including legacy entry points and enrollment. */
final class NativeBoundary
{
    private static array $held = [];
    public static function enabled(string $root): bool { return is_dir($root.'/var/imperium/native-authority'); }
    public static function lock(string $root, callable $operation): mixed
    {
        $key = str_replace('\\', '/', realpath($root) ?: $root);
        if (PHP_OS_FAMILY === 'Windows') { $key = strtolower($key); }
        if (isset(self::$held[$key])) { return $operation(); }
        return (new AtomicTransition($root))->run('native-institutional-authority-v1', function () use ($key, $operation): mixed {
            self::$held[$key] = true;
            try { return $operation(); } finally { unset(self::$held[$key]); }
        });
    }
    public static function legacy(string $root, callable $operation): mixed
    {
        return self::lock($root, function () use ($root, $operation): mixed {
            if (self::enabled($root)) { throw new \RuntimeException('NAT002_LEGACY_WRITER_OR_READER_FENCED'); }
            return $operation();
        });
    }
}
