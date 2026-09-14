<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\NativeAuthority;

use App\Imperium\Runtime\Persistence\AtomicTransition;
use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationOwnerFrame};

/** One cooperating-process boundary, including legacy entry points and enrollment. */
final class NativeBoundary
{
    private static array $held = [];
    public static function enabled(string $root): bool { return is_dir($root.'/var/imperium/native-authority'); }
    public static function lock(string $root, callable $operation): mixed
    {
        $key = str_replace('\\', '/', realpath($root) ?: $root);
        if (PHP_OS_FAMILY === 'Windows') { $key = strtolower($key); }
        if (isset(self::$held[$key])) { return $operation(self::$held[$key]); }
        if (self::$held !== []) { throw new \RuntimeException('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED'); }
        return FormationOwnerFrame::run($root, fn (FormationOwnerFrame $owner) => self::inOwner($root, $owner, $operation));
    }
    /** Explicit seam for owners which already hold Formation (notably V0). */
    public static function inOwner(string $root, FormationOwnerFrame $owner, callable $operation): mixed
    {
        $owner->assertOwner(new FormationJournal($root));
        $key = str_replace('\\', '/', realpath($root) ?: $root);
        if (PHP_OS_FAMILY === 'Windows') { $key = strtolower($key); }
        if (isset(self::$held[$key])) { return $operation($owner); }
        if (self::$held !== []) { throw new \RuntimeException('PPC302_LIVE_SAME_ROOT_OWNER_REQUIRED'); }
        return (new AtomicTransition($root))->run('native-institutional-authority-v1', function () use ($key, $owner, $operation): mixed {
            self::$held[$key] = $owner;
            try { return $operation($owner); } finally { unset(self::$held[$key]); }
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
