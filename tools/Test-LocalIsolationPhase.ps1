# Pre-enrollment and post-enrollment review before proceeding to the next phase.
param([Parameter(Mandatory)][string]$Directory)
$ErrorActionPreference='Stop'
. "$PSScriptRoot\LocalIsolationReadiness.ps1"
$base='C:\ProgramData\Imperium';$binding=Get-PmaInstallationBinding $base
$inventory=Read-PmaEvidence "$Directory\inventory.json"
Assert-PmaEqual $inventory.items (Get-PmaInventory $base).items 'PHASE_INVENTORY_CHANGED'
$null=& "$PSScriptRoot\Test-LocalIsolationInstalledPackage.ps1" -Base $base -Manifest "$base\ProtectedMissionExchange\package-manifest.json" -ManifestSha256 $binding.package_manifest_sha256
$packageFiles=Read-PmaEvidence "$base\ProtectedMissionExchange\package-manifest.json"
foreach($reference in @('target-inventory.json','mission-draft.json')){
 if(-not $packageFiles.Contains($reference) -or (Get-FileHash "$base\ProtectedMissionExchange\$reference").Hash -ine $packageFiles[$reference]){throw 'PHASE_PACKAGED_REFERENCE_CHANGED'}
}
$trust=$null;$now=[DateTime]::UtcNow.Ticks
foreach($role in @('Runtime','Caller')) {
 $plan=Read-PmaEvidence "$Directory\$role-plan.json"
 Assert-PmaEqual $plan.binding $binding 'PHASE_BINDING'
 Assert-PmaEqual $plan.role $role 'PHASE_ROLE'
 Assert-PmaEqual $plan.sid $binding[($role.ToLower()+'_sid')] 'PHASE_SID'
 if($plan.phase -notin @('pre','post','current')){throw 'PHASE_INVALID'}
 $measurement=Read-PmaEvidence "$Directory\$role-access.json"
 Assert-PmaMeasurement $measurement $plan (Get-FileHash "$Directory\$role-plan.json").Hash $binding $plan.phase $inventory $now
 if($plan.phase -ne 'pre'){
  $trust=Read-PmaEvidence "$base\ProtectedMissionExchange\public-trust.json"
  $trust.fingerprint=[Convert]::ToHexString([Security.Cryptography.SHA256]::HashData([Convert]::FromBase64String($trust.public_key))).ToLowerInvariant();$trust.revoked=$false
 }
 Assert-PmaStartup (Read-PmaEvidence "$Directory\$role-startup.json") $binding $plan.phase $role $trust $now
}
'EXACT_PHASE_OBSERVATIONS_VALIDATED_NOT_OWNER_CUSTODY_PROOF'
