<?php
declare(strict_types=1);
namespace App\ProtectedMission;

/** A Windows PowerShell child must never discover PowerShell 7 modules. */
final class WindowsPowerShell
{
    public const EXECUTABLE = 'C:/Windows/System32/WindowsPowerShell/v1.0/powershell.exe';
    public const MODULES = 'C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\Modules';

    public static function environment(): array
    {
        $environment = getenv();
        foreach (array_keys($environment) as $key) {
            if (strcasecmp($key, 'PSModulePath') === 0) unset($environment[$key]);
        }
        $environment['PSModulePath'] = self::MODULES;
        return $environment;
    }
}
