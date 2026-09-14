<?php
declare(strict_types=1);
namespace App\Imperium\Runtime\Citadel\Formation;

/** Completion custody for newly published exact packages; never upgrades old files. */
#[\Symfony\Component\DependencyInjection\Attribute\Exclude]
final class NativeInstallationPackage
{
    public static function begin(string $root, string $digest, array $records): ?string
    {
        $path = $root.'/packages/'.$digest.'.json';
        if (file_exists($path.'.pending')) { throw new \RuntimeException('B210_OPERATOR_ROOT_PARTIAL_INSTALLATION'); }
        if (is_file($path)) { return null; }
        // An old installation has no known package completion fact. Exact replay
        // retains its meaning but must not manufacture that missing provenance.
        foreach ($records as $record) {
            if (is_file($root.'/installations/'.$record['installation_id'].'.json')) { return null; }
        }
        $package = ['schema' => 'imperium.native-installation-package-completion/v1',
            'source_package_digest' => $digest, 'installations' => []];
        foreach ($records as $record) {
            $package['installations'][$record['installation_id']] = FormationJournal::digest($record);
        }
        $package['record_digest'] = FormationJournal::digest($package);
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            throw new \RuntimeException('B204_OPERATOR_ROOT_INSTALLATION_FAILED');
        }
        $handle = @fopen($path.'.pending', 'xb');
        if ($handle === false) { throw new \RuntimeException('B210_OPERATOR_ROOT_PARTIAL_INSTALLATION'); }
        try {
            $bytes = json_encode($package, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
            if (fwrite($handle, $bytes) !== strlen($bytes) || !fflush($handle) || !fsync($handle)) {
                throw new \RuntimeException('B204_OPERATOR_ROOT_INSTALLATION_FAILED');
            }
        } finally { fclose($handle); }
        return $path;
    }

    public static function complete(?string $path): void
    {
        if ($path !== null && (!is_file($path.'.pending') || file_exists($path) || !rename($path.'.pending', $path))) {
            throw new \RuntimeException('B210_OPERATOR_ROOT_PARTIAL_INSTALLATION');
        }
    }

    public static function verify(string $root, array $installation): void
    {
        $validator = new \App\Imperium\Runtime\Persistence\RecordReferenceValidator($root);
        $base = $root.'/var/imperium/operator-root';
        $digest = $installation['source_package_digest'] ?? '';
        if (!is_string($digest) || !preg_match('/^[a-f0-9]{64}$/D', $digest)
            || file_exists($base.'/packages/'.$digest.'.json.pending')) {
            throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED');
        }
        $p = $validator->requireIntact($validator->read($base.'/packages/'.$digest.'.json', 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'), 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED');
        if (!FormationJournal::keys($p, ['schema', 'source_package_digest', 'installations', 'record_digest'])
            || $p['schema'] !== 'imperium.native-installation-package-completion/v1' || $p['source_package_digest'] !== $digest
            || !is_array($p['installations']) || ($p['installations'][$installation['installation_id']] ?? null) !== $installation['record_digest']) {
            throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED');
        }
        foreach ($p['installations'] as $id => $expected) {
            if (!preg_match('/^operator-root-installation-[a-f0-9]{20}$/D', $id)) { throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'); }
            $r = $validator->requireIntact($validator->read($base.'/installations/'.$id.'.json', 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'), 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED');
            if ($r['record_digest'] !== $expected || ($r['source_package_digest'] ?? null) !== $digest
                || !preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $r['office'] ?? '')) { throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'); }
            $officer = ($r['personnel_type'] ?? null) === 'OFFICER';
            $binding = $r[$officer ? 'binding' : 'roster'];
            $bindingId = $binding[$officer ? 'binding_id' : 'roster_id'];
            if (!preg_match('/^[a-z0-9][a-z0-9.-]*$/D', $bindingId)) { throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'); }
            $path = $officer ? $root.'/var/imperium/offices/'.$r['office'].'/occupancy' : $base.'/operatives/'.$r['office'];
            $actual = $validator->requireIntact($validator->read($path.'/'.$bindingId.'.json', 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'), 'PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED');
            $binding['source_installation_digest'] = $expected;
            $binding['record_digest'] = FormationJournal::digest($binding);
            if (FormationJournal::digest($actual) !== FormationJournal::digest($binding)) { throw new \RuntimeException('PPC304_COMPLETE_NATIVE_PACKAGE_REQUIRED'); }
        }
    }
}
