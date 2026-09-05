param([Parameter(Mandatory)][string]$Plan,[Parameter(Mandatory)][string]$Output)
$ErrorActionPreference='Stop'
. "$PSScriptRoot\LocalIsolationReadiness.ps1"
. "$PSScriptRoot\ProtectedMission.ps1"
if(Test-Path -LiteralPath $Output){throw 'OUTPUT_EXISTS'}
$planValue=Read-PmaEvidence $Plan
$base='C:\ProgramData\Imperium'
Assert-PmaEqual $planValue.binding.base $base 'INSTALLED_PLAN_REQUIRED'
$token=Get-PmaToken;Assert-PmaToken $token $planValue.sid
$env:PHPRC="$base\ProtectedMissionPHP\php.ini"
$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
Set-Location "$base\ProtectedMissionCode"
$checker=Invoke-PmaProcess -Php 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe' -Script '-NoProfile' -Arguments @('-NonInteractive','-File',"$base\ProtectedMissionCode\tools\Assert-ProtectedMissionInstallation.ps1",'-CodePath',"$base\ProtectedMissionCode")
$cli=Invoke-PmaProcess -Php "$base\ProtectedMissionPHP\php.exe" -Script "$base\ProtectedMissionCode\bin\protected-mission.php" -Arguments @('trust')
$record=[ordered]@{schema='imperium.local-isolation-startup/v2';binding=$planValue.binding;phase=$planValue.phase;role=$planValue.role;captured_ticks=[DateTime]::UtcNow.Ticks;checker=@{ExitCode=$checker.ExitCode;Output=$checker.Output;Error=$checker.Error};cli=@{ExitCode=$cli.ExitCode;Output=$cli.Output;Error=$cli.Error}}
foreach($key in $token.Keys){$record[$key]=$token[$key]}
[IO.File]::WriteAllText($Output,($record|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
'STARTUP_OBSERVATIONS_RECORDED_NATIVE_RESULTS_NOT_REINTERPRETED'
