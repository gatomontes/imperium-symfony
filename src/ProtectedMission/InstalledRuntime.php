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
        if (PHP_OS_FAMILY !== 'Windows') throw new \RuntimeException('PMA_WINDOWS_DEPLOYMENT_REQUIRED');
        if (!is_file($root.'/installation.json')) throw new \RuntimeException('PMA_DEPLOYMENT_NOT_INSTALLED');
        $installation = json_decode((string)file_get_contents($root.'/installation.json'), true, 32, JSON_THROW_ON_ERROR);
        // An installation attestation is an owner-controlled deployment record, not proof of ACLs.
        if (($installation['separate_runtime_account_required'] ?? null) !== true
            || ($installation['code_path'] ?? null) !== realpath(dirname(__DIR__, 2))) {
            throw new \RuntimeException('PMA_INSTALLATION_INVALID');
        }
        $process=proc_open(['C:/Windows/System32/WindowsPowerShell/v1.0/powershell.exe','-NoProfile','-NonInteractive','-File',
            dirname(__DIR__,2).'/tools/Assert-ProtectedMissionInstallation.ps1','-CodePath',realpath(dirname(__DIR__,2))],
            [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        if (!is_resource($process)) throw new \RuntimeException('PMA_INSTALLATION_CHECK_FAILED');
        fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[2]);
        if (proc_close($process)!==0 || trim($out)!=='PMA_INSTALLATION_ACL_AND_IDENTITY_VERIFIED') throw new \RuntimeException('PMA_INSTALLATION_CHECK_FAILED');
        return new AuthorityOwner($root);
    }
}
