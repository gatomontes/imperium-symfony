# Owner administrator creates one fresh phase bundle; never overwrites prior evidence.
param([Parameter(Mandatory)][ValidateSet('pre','post','current')][string]$Phase)
$ErrorActionPreference='Stop'
. "$PSScriptRoot\LocalIsolationReadiness.ps1"
$base='C:\ProgramData\Imperium'
$binding=Get-PmaInstallationBinding $base
$principal=[Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'OWNER_ELEVATED_TERMINAL_REQUIRED'}
$parent=if($Phase -eq 'pre'){'ProtectedMissionProbePlans'}else{'ProtectedMissionPostEnrollmentPlans'}
$dir=Join-Path "$base\$parent" ($Phase+'-'+[guid]::NewGuid().ToString('N'))
[void][IO.Directory]::CreateDirectory($dir)
foreach($name in @('inventory.json','Runtime-plan.json','Caller-plan.json','Runtime-access.json','Caller-access.json','Runtime-startup.json','Caller-startup.json','detached-manifest.json','previous-readiness.json')) {
 [IO.File]::WriteAllText("$dir\$name",'PMA_RESERVED_UNMEASURED',[Text.UTF8Encoding]::new($false))
}
# Inherit only the administrator-owned read-only plan directory policy.
$inventory=Get-PmaInventory $base
[IO.File]::WriteAllText("$dir\inventory.json",($inventory|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
foreach($role in @('Runtime','Caller')) {
 $plan=[ordered]@{schema='imperium.local-isolation-plan/v2';binding=$binding;phase=$Phase;role=$role;sid=$binding[($role.ToLower()+'_sid')];probes=(Get-PmaRequiredProbes $inventory $role)}
 [IO.File]::WriteAllText("$dir\$role-plan.json",($plan|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
}
[ordered]@{result='PROBE_PLANS_PREPARED_NOT_MEASURED';directory=$dir;phase=$Phase}|ConvertTo-Json
