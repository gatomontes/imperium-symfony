param([Parameter(Mandatory)][ValidateSet('Prepare','Accept','Step','Status')][string]$Action,
      [string]$Exchange='C:\ProgramData\Imperium\ProtectedMissionExchange')
$ErrorActionPreference='Stop'
. (Join-Path $PSScriptRoot 'LocalMission.ps1')
$base='C:\ProgramData\Imperium'
if($PSScriptRoot -cne "$base\ProtectedMissionCode\tools"){throw 'USE_REVIEWED_INSTALLED_LAUNCHER'}
$context=@{Php="$base\ProtectedMissionPHP\php.exe";Script="$base\ProtectedMissionCode\bin\protected-mission.php";Prefix=@();Exchange=$Exchange}
$env:PHPRC="$base\ProtectedMissionPHP\php.ini"
$env:PHP_INI_SCAN_DIR="$base\ProtectedMissionPHP\empty-ini"
$env:PATH="$base\ProtectedMissionPHP;C:\Windows\System32;C:\Windows"
Set-Location "$base\ProtectedMissionCode"
$gate=Get-Content -LiteralPath (Join-Path $Exchange 'owner-readiness.json') -Raw|ConvertFrom-Json
if($gate.status -cne 'OWNER_REVIEWED_ACTUAL_DEPLOYMENT_READY' -or -not $gate.runtime_measurement_sha256 -or -not $gate.caller_measurement_sha256){throw 'ACTUAL_OWNER_READINESS_REQUIRED'}
# Startup enforces the actual token; the gate is an owner attestation, not proof by itself.
$null=Invoke-PmaChecked $context @('trust')
switch($Action){
 'Prepare' {Start-PmaPrepared $context (Join-Path $Exchange 'plan.json')}
 'Accept' {Accept-PmaSigned $context (Join-Path $Exchange 'response.json')}
 'Step' {Step-PmaMission $context}
 'Status' {
    $path=Join-Path $Exchange 'authorization.json'
    if(Test-Path $path){Get-PmaProgress $context (Get-Content $path -Raw|ConvertFrom-Json).authorization_id}
    else{$cid=(Get-Content (Join-Path $Exchange 'challenge.json') -Raw|ConvertFrom-Json).challenge_id;Invoke-PmaChecked $context @('challenge-status',$cid)}
 }
}
