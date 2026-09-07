<?php
declare(strict_types=1);

namespace App\Tests\Imperium\Runtime\Support {
    /** Test-only filesystem fault: native rename succeeds, then the process path unwinds.
     * No production class, receipt, authority, or publication is replaced.
     */
    final class CitadelPublicationInterruption
    {
        public static ?string $root = null;
        public static ?string $publishedPath = null;
        public static bool $before = false;
        public static ?string $afterUnlockRoot = null;
        public static ?\Closure $afterUnlock = null;

        public static function unlocked($stream): void
        {
            $path = str_replace('\\', '/', stream_get_meta_data($stream)['uri']);
            if (self::$afterUnlockRoot !== null && self::$afterUnlock !== null
                && str_starts_with($path, str_replace('\\', '/', self::$afterUnlockRoot).'/var/imperium/runtime/transition-locks/')) {
                $callback = self::$afterUnlock;
                self::$afterUnlock = null; self::$afterUnlockRoot = null;
                $callback();
            }
        }

        public static function beforeRename(string $path): void
        {
            if (self::$before) { self::afterRename($path); }
        }

        public static function afterRename(string $path): void
        {
            $normalized = str_replace('\\', '/', $path);
            if (self::$root !== null
                && str_starts_with($normalized, str_replace('\\', '/', self::$root).'/var/imperium/citadel/children/')
                && str_contains($normalized, '/var/imperium/curia/handoffs/')
                && str_ends_with($normalized, '.json')) {
                self::$root = null;
                self::$publishedPath = $path;
                throw new \RuntimeException(self::$before ? 'SYNTHETIC_INTERRUPTION_BEFORE_CHILD_RENAME' : 'SYNTHETIC_INTERRUPTION_AFTER_REAL_CHILD_RENAME');
            }
        }
    }
}

namespace App\Imperium\Runtime\Persistence {
    function flock($stream, int $operation, ?int &$wouldBlock = null): bool
    {
        $result = \flock($stream, $operation, $wouldBlock);
        if ($result && $operation === LOCK_UN) {
            \App\Tests\Imperium\Runtime\Support\CitadelPublicationInterruption::unlocked($stream);
        }
        return $result;
    }

    function rename(string $from, string $to): bool
    {
        \App\Tests\Imperium\Runtime\Support\CitadelPublicationInterruption::beforeRename($to);
        $result = \rename($from, $to);
        if ($result) { \App\Tests\Imperium\Runtime\Support\CitadelPublicationInterruption::afterRename($to); }
        return $result;
    }
}
