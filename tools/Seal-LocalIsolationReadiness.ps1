param([Parameter(Mandatory)][string]$Pre,[Parameter(Mandatory)][string]$Post,[Parameter(Mandatory)][string]$Current,
      [Parameter(Mandatory)][string]$PublicFingerprint,[Parameter(Mandatory)][string]$Transport,[Parameter(Mandatory)][string]$Custody)
$ErrorActionPreference='Stop'
. "$PSScriptRoot\LocalIsolationReadiness.ps1"
$base='C:\ProgramData\Imperium'
$principal=[Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'OWNER_ELEVATED_TERMINAL_REQUIRED'}
$refs=[ordered]@{}
foreach($pair in @(@('pre',$Pre),@('post',$Post),@('current',$Current))) {
 $dir=$pair[1];$hashes=[ordered]@{}
 foreach($file in @('inventory.json','Runtime-plan.json','Caller-plan.json','Runtime-access.json','Caller-access.json','Runtime-startup.json','Caller-startup.json')) {
  $null=Read-PmaEvidence "$dir\$file";$hashes[$file]=(Get-FileHash "$dir\$file").Hash
 }
 $path="$dir\detached-manifest.json"
 if([IO.File]::ReadAllText($path) -ceq 'PMA_RESERVED_UNMEASURED') {
  [IO.File]::WriteAllText($path,($hashes|ConvertTo-Json),[Text.UTF8Encoding]::new($false))
 }else {Assert-PmaEqual (Read-PmaEvidence $path) $hashes 'PREVIOUS_EVIDENCE_CHANGED'}
 $refs[$pair[0]]=@{directory=$dir;sha256=(Get-FileHash $path).Hash}
}
$ready=[ordered]@{schema=$PmaReadinessSchema;binding=(Get-PmaInstallationBinding $base);captured_ticks=[DateTime]::UtcNow.Ticks;phases=$refs;public_fingerprint=$PublicFingerprint;transport=$Transport;custody=$Custody}
$result=Test-PmaReadiness $ready $base
if($result.result -cne 'LOCAL_ISOLATION_READINESS_VALID'){$result|ConvertTo-Json;throw 'READINESS_NOT_SEALED'}
$path="$base\ProtectedMissionExchange\owner-readiness.json"
$archive="$Current\previous-readiness.json"
if([IO.File]::ReadAllText($archive) -cne 'PMA_RESERVED_UNMEASURED'){throw 'CURRENT_BUNDLE_ALREADY_USED'}
[IO.File]::WriteAllBytes($archive,[IO.File]::ReadAllBytes($path))
[IO.File]::WriteAllText($path,($ready|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
$result|ConvertTo-Json -Depth 20
