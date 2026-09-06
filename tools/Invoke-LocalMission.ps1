param([Parameter(Mandatory)][ValidateSet('Prepare','Accept','Step','Status')][string]$Action,
      [string]$Exchange='C:\ProgramData\Imperium\ProtectedMissionExchange')
$ErrorActionPreference='Stop'
. (Join-Path $PSScriptRoot 'LocalMission.ps1')
$base='C:\ProgramData\Imperium'
if($PSScriptRoot -cne "$base\ProtectedMissionCode\tools"){throw 'USE_REVIEWED_INSTALLED_LAUNCHER'}
if($Exchange -cne "$base\ProtectedMissionExchange"){throw 'USE_PROTECTED_EXCHANGE'}
$context=@{Php="$base\ProtectedMissionPHP\php.exe";Script="$base\ProtectedMissionCode\bin\protected-mission.php";Prefix=@();Exchange=$Exchange}
$env:PHPRC="$base\ProtectedMissionPHP\php.ini"
$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
Set-Location "$base\ProtectedMissionCode"
Invoke-PmaLocalAction $context $Action
