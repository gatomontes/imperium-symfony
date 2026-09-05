<?php
declare(strict_types=1);
namespace App\ProtectedMission;

/** Paths are installation constants, never request fields or environment overrides. */
final class InstalledRuntime
{
    public static function root(): string
    {
        return PHP_OS_FAMILY === 'Windows' ? 'C:/ProgramData/Imperium/ProtectedMission' : '/var/lib/imperium-protected-mission';
    }

    public static function owner(): AuthorityOwner
    {
        $root = self::root();
        if (!is_file($root.'/installation.json')) throw new \RuntimeException('PMA_DEPLOYMENT_NOT_INSTALLED');
        $installation = json_decode((string)file_get_contents($root.'/installation.json'), true, 32, JSON_THROW_ON_ERROR);
        // An installation attestation is an owner-controlled deployment record, not proof of ACLs.
        if (($installation['separate_runtime_account_required'] ?? null) !== true
            || ($installation['code_path'] ?? null) !== realpath(dirname(__DIR__, 2))) {
            throw new \RuntimeException('PMA_INSTALLATION_INVALID');
        }
        return new AuthorityOwner($root);
    }
}
