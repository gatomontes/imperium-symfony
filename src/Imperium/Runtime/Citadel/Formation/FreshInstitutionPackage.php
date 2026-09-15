<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

use App\Bootstrap\CanonicalJson;
use App\Imperium\Runtime\Bootstrap\OperatorRootPersonnelInstallationService;
use App\Imperium\Runtime\Citadel\NativeAuthority\NativeBoundary;

/** New-route custody; historical NativeInstallationPackage semantics are untouched. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class FreshInstitutionPackage
{
    public const INSTALLATION = 'imperium.fresh-native-installation/v1';
    public const OCCUPANCY = 'imperium.fresh-native-occupancy/v1';
    public const PACKAGE = 'imperium.fresh-native-package/v1';
    public const MAX_FILE = 2097152;

    public static function layout(string $root, array $reservation): array
    {
        $ref = FormationFreshEstablishment::reference($reservation);
        $records = (new OperatorRootPersonnelInstallationService($root))->establishmentRecords($reservation['terms']['package']);
        $files = []; $witnesses = [];
        foreach ($records as $original) {
            $occupancy = $original['binding'];
            $occupancy['source_installation_digest'] = $original['record_digest'];
            $occupancy['record_digest'] = FormationJournal::digest($occupancy);
            $installation = self::wrap(self::INSTALLATION, $original['installation_id'], $ref, $original);
            $binding = self::wrap(self::OCCUPANCY, $occupancy['binding_id'], $ref, $occupancy);
            $files['var/imperium/operator-root/installations/'.$installation['id'].'.json'] = $installation;
            $files['var/imperium/offices/'.$original['office'].'/occupancy/'.$binding['id'].'.json'] = $binding;
            $witnesses[$original['seat']] = ['installation' => $original, 'occupancy' => $occupancy,
                'installation_wrapper' => $installation, 'occupancy_wrapper' => $binding];
        }
        ksort($files, SORT_STRING);
        $digest = FormationJournal::digest($reservation['terms']['package']);
        $completion = ['schema' => self::PACKAGE, 'id' => 'fresh-package-'.$digest, 'reservation_ref' => $ref,
            'files' => array_map(static fn(array $r): string => $r['record_digest'], $files)];
        $completion['record_digest'] = FormationJournal::digest($completion);
        $total = strlen(CanonicalJson::encode($completion)) + 1;
        foreach ($files as $file) {
            $size = strlen(CanonicalJson::encode($file)) + 1;
            self::need($size <= self::MAX_FILE, 'NATIVE_FILE_BOUND'); $total += $size;
        }
        self::need($total + self::MAX_FILE <= 16777216, 'NATIVE_BYTE_BOUND');
        return ['files' => $files, 'witnesses' => $witnesses, 'completion' => $completion,
            'completion_path' => 'var/imperium/operator-root/packages/'.$digest.'.json'];
    }

    public static function publish(string $root, FormationOwnerFrame $owner, FormationFreshEstablishment $protocol, ?\Closure $checkpoint = null): array
    {
        $owner->assertOwner(new FormationJournal($root));
        return NativeBoundary::inOwner($root, $owner, static function () use ($root, $owner, $protocol, $checkpoint): array {
            $reservation = $protocol->authorizedPendingInOwner($owner, $root);
            $layout = self::layout($root, $reservation);
            self::scan($root, $layout, false);
            $complete = $root.'/'.$layout['completion_path'];
            $intent = $complete.'.pending';
            if (file_exists($complete)) {
                self::verify($root, $reservation);
                return $layout['completion'];
            }
            self::exactWrite($intent, $layout['completion'], $checkpoint, 'intent');
            foreach ($layout['files'] as $path => $record) {
                self::publishFile($root.'/'.$path, $record, $checkpoint, $path);
            }
            self::scan($root, $layout, false);
            foreach ($layout['files'] as $path => $record) { self::exactRead($root.'/'.$path, $record); }
            self::exactRead($intent, $layout['completion']);
            ($checkpoint)?->__invoke('before-package-completion');
            self::need(!file_exists($complete) && rename($intent, $complete), 'PACKAGE_RENAME');
            ($checkpoint)?->__invoke('after-package-completion');
            self::verify($root, $reservation);
            return $layout['completion'];
        });
    }

    public static function verify(string $root, array $reservation): array
    {
        $layout = self::layout($root, $reservation);
        self::scan($root, $layout, true);
        self::exactRead($root.'/'.$layout['completion_path'], $layout['completion']);
        foreach ($layout['files'] as $path => $record) { self::exactRead($root.'/'.$path, $record); }
        return $layout;
    }

    /** Bounded exact file allowlist; symlinks, foreign files and stale temporaries refuse. */
    public static function scan(string $root, ?array $layout, bool $complete): void
    {
        foreach (['var', 'var/imperium'] as $ancestor) {
            self::need(!is_link($root.'/'.$ancestor) && (!file_exists($root.'/'.$ancestor) || is_dir($root.'/'.$ancestor)), 'NATIVE_PATH');
        }
        foreach (['var/imperium/native-authority', 'var/imperium/bootstrap-state.json'] as $path) {
            self::need(!file_exists($root.'/'.$path) && !is_link($root.'/'.$path), 'SUCCESSOR');
        }
        $allowed = $layout['files'] ?? [];
        if ($layout !== null) {
            $allowed[$layout['completion_path']] = $layout['completion'];
            if (!$complete) {
                $allowed[$layout['completion_path'].'.pending'] = $layout['completion'];
                foreach ($layout['files'] as $path => $record) { $allowed[$path.'.pending'] = $record; }
            }
        }
        $count = 0; $bytes = 0;
        foreach (['var/imperium/operator-root', 'var/imperium/offices'] as $directory) {
            $base = $root.'/'.$directory;
            if (!file_exists($base) && !is_link($base)) { continue; }
            self::need(is_dir($base) && !is_link($base), 'NATIVE_PATH');
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $file) {
                self::need(++$count <= 256 && $iterator->getDepth() <= 8 && !$file->isLink(), 'NATIVE_SCAN_BOUND');
                if ($file->isDir()) { continue; }
                $relative = $directory.'/'.str_replace('\\', '/', $iterator->getSubPathName());
                self::need(isset($allowed[$relative]) && $file->isFile(), 'UNKNOWN_NATIVE_FILE');
                $bytes += $file->getSize(); self::need($bytes <= 16777216, 'NATIVE_BYTE_BOUND');
                self::exactRead($root.'/'.$relative, $allowed[$relative]);
            }
        }
    }

    private static function wrap(string $schema, string $id, array $ref, array $original): array
    {
        $record = ['schema' => $schema, 'id' => $id, 'reservation_ref' => $ref, 'original' => $original];
        $record['record_digest'] = FormationJournal::digest($record); return $record;
    }
    private static function publishFile(string $path, array $record, ?\Closure $checkpoint, string $label): void
    {
        if (file_exists($path)) { self::exactRead($path, $record); self::need(!file_exists($path.'.pending'), 'DUPLICATE_NATIVE_PARTIAL'); return; }
        self::exactWrite($path.'.pending', $record, $checkpoint, $label);
        ($checkpoint)?->__invoke('before-placement:'.$label);
        self::need(!file_exists($path) && rename($path.'.pending', $path), 'PLACEMENT_RENAME');
        ($checkpoint)?->__invoke('after-placement:'.$label);
    }
    private static function exactWrite(string $path, array $record, ?\Closure $checkpoint, string $label): void
    {
        if (file_exists($path) || is_link($path)) { self::exactRead($path, $record); return; }
        $directory = dirname($path);
        self::need(is_dir($directory) || (mkdir($directory, 0700, true) || is_dir($directory)), 'NATIVE_DIRECTORY');
        ($checkpoint)?->__invoke('before-write:'.$label);
        $stream = @fopen($path, 'xb'); self::need($stream !== false, 'NATIVE_WRITE');
        try {
            $bytes = CanonicalJson::encode($record)."\n";
            self::need(strlen($bytes) <= self::MAX_FILE && fwrite($stream, $bytes) === strlen($bytes) && fflush($stream) && fsync($stream), 'NATIVE_WRITE');
        } finally { fclose($stream); }
        ($checkpoint)?->__invoke('after-write:'.$label);
    }
    private static function exactRead(string $path, array $expected): void
    {
        self::need(is_file($path) && !is_link($path) && filesize($path) <= self::MAX_FILE, 'NATIVE_ORIGINAL');
        $bytes = file_get_contents($path);
        self::need($bytes === CanonicalJson::encode($expected)."\n", 'NATIVE_ORIGINAL');
    }
    private static function need(bool $ok, string $code): void { if (!$ok) { throw new \RuntimeException('PPC7_'.$code); } }
}
