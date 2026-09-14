<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Persistence;

use App\Imperium\Runtime\Citadel\Formation\{FormationJournal, FormationOwnerFrame};

/** Read-only preflight. Fixed filesystem topology and cooperating binaries only. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class ReservedInstitutionalStorage
{
    public static function preflight(string $root, string $relative, ?FormationOwnerFrame $owner): void
    {
        clearstatcache(true);
        // API grammar has already been checked by the primitive. No lock or write here.
        $existingRoot = $root;
        $missing = [];
        while (realpath($existingRoot) === false) {
            if (is_link($existingRoot) || dirname($existingRoot) === $existingRoot) {
                throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS');
            }
            array_unshift($missing, basename($existingRoot));
            $existingRoot = dirname($existingRoot);
        }
        $canonical = realpath($existingRoot).($missing === [] ? '' : '/'.implode('/', $missing));
        $canonical = rtrim(str_replace('\\', '/', $canonical), '/');
        $parts = [];
        foreach (explode('/', $relative) as $part) {
            if ($part === '' || $part === '.') { continue; }
            // Win32 also aliases a single trailing dot (spaces and .. fail API grammar).
            if (PHP_OS_FAMILY === 'Windows') { $part = rtrim($part, '.'); }
            if ($part === '') { throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS'); }
            $parts[] = $part;
        }
        $effective = implode('/', $parts);
        if (PHP_OS_FAMILY === 'Windows') { $effective = strtolower($effective); }
        $reserved = $effective === 'var/imperium/bootstrap-state.json'
            || preg_match('~^var/imperium/(?:operator-root/|native-authority/|citadel/formation/|offices/[^/]+/occupancy/)~D', $effective);
        if ($owner !== null) { $owner->assertOwner(new FormationJournal($root)); }
        if ($reserved && $owner === null) { throw new \RuntimeException('PPC401_RESERVED_STORAGE_OWNER_REQUIRED'); }
        // Refuse aliases rather than promoting an ordinary lexical path into custody.
        // Walk existing parents and the final target, including dangling links.
        $probe = $canonical;
        foreach ($parts as $part) {
            $probe .= '/'.$part;
            clearstatcache(true, $probe);
            if (is_link($probe)) { throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS'); }
            if (!file_exists($probe)) {
                // Windows may expose a dangling junction as neither file nor
                // symlink. Inspect its directory entry without lstat's absent-
                // path warning (strict callers can promote even @ warnings).
                $parent = dirname($probe);
                $entryPresent = false;
                if (is_dir($parent)) {
                    foreach (scandir($parent) as $entry) {
                        $sameEntry = PHP_OS_FAMILY === 'Windows' ? strcasecmp($entry, $part) === 0 : $entry === $part;
                        if ($sameEntry) { $entryPresent = true; break; }
                    }
                }
                // The nearest existing parent has been checked. Absent descendants
                // cannot contain an alias under the fixed-topology contract.
                if (!$entryPresent) { break; }
                // A cooperating ordinary writer may have just published this
                // entry. Reobserve before calling it an unresolved alias; the
                // original primitive lock still decides replay/CAS conflicts.
                clearstatcache(true, $probe);
                if (!file_exists($probe)) { throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS'); }
            }
            $resolved = realpath($probe);
            if ($resolved === false) { throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS'); }
            $resolved = str_replace('\\', '/', $resolved);
            $same = PHP_OS_FAMILY === 'Windows' ? strcasecmp($probe, $resolved) === 0 : $probe === $resolved;
            if (!$same) { throw new \RuntimeException('PPC402_STORAGE_TOPOLOGY_AMBIGUOUS'); }
        }
    }
}
