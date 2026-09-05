# Reviewed finite access policy and deterministic evidence validation. No authority writes.
Set-StrictMode -Version Latest
$PmaReadinessSchema='imperium.local-isolation-readiness/v2'
function ConvertTo-PmaStable($Value) {
 if($Value -is [Collections.IDictionary]) {
  $out=[ordered]@{};foreach($key in @($Value.Keys|Sort-Object -CaseSensitive)){$out[$key]=ConvertTo-PmaStable $Value[$key]};return $out
 }
 if($Value -is [array]) {return ,@($Value|ForEach-Object{ConvertTo-PmaStable $_})}
 return $Value
}
function Assert-PmaEqual($Actual,$Expected,[string]$Reason) {
 if((ConvertTo-PmaStable $Actual|ConvertTo-Json -Depth 100 -Compress) -cne (ConvertTo-PmaStable $Expected|ConvertTo-Json -Depth 100 -Compress)){throw $Reason}
}
function Read-PmaEvidence([string]$Path) {
 if(-not(Test-Path -LiteralPath $Path -PathType Leaf)){throw 'READINESS_FILE_ABSENT'}
 if((Get-Item -LiteralPath $Path -Force).Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'READINESS_REPARSE'}
 return Get-Content -LiteralPath $Path -Raw|ConvertFrom-Json -AsHashtable -DateKind String
}
function Get-PmaToken {
 $id=[Security.Principal.WindowsIdentity]::GetCurrent()
 $groups=@($id.Groups|ForEach-Object{$_.Value}|Sort-Object)
 return [ordered]@{sid=$id.User.Value;groups=$groups;administrator_token=([Security.Principal.WindowsPrincipal]::new($id)).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator);administrator_group_present=('S-1-5-32-544' -in $groups)}
}
function Assert-PmaToken($Token,[string]$Sid) {
 if($Token.sid -cne $Sid -or $Token.administrator_token -isnot [bool] -or $Token.administrator_group_present -isnot [bool] -or $Token.administrator_token -or $Token.administrator_group_present -or 'S-1-5-32-544' -in $Token.groups -or @($Token.groups).Count -eq 0){throw 'READINESS_IDENTITY_REFUSED'}
 if(@($Token.groups|Sort-Object -Unique).Count -ne @($Token.groups).Count){throw 'READINESS_GROUPS_INVALID'}
}
function Get-PmaSurfaceClass([string]$Path,[bool]$Directory,[string]$Base) {
 $rel=$Path.Substring($Base.Length).TrimStart('\')
 if($rel -match '^ProtectedMission(Code|PHP|Shell|Target|ProbePlans|PostEnrollmentPlans)(\\|$)'){return 'immutable'}
 if($rel -eq 'ProtectedMissionExchange'){return 'exchange-root'}
 if($rel -like 'ProtectedMissionExchange\*') {
  if($Directory){throw 'READINESS_UNEXPECTED_EXCHANGE_DIRECTORY'}
  $name=[IO.Path]::GetFileName($Path)
  if($name -in @('package-manifest.json','target-inventory.json','mission-draft.json','public-trust.json','owner-readiness.json')){return 'reference'}
  if($name -match '^(plan|response|challenge|payload|render|authorization|chain|capabilities|completed|target-before|target-after|receipt-verification|installed-hashes-after)\.(json|txt)$' -or $name -match '^(prepare|submit|derive|issue|admit|inspect|complete)-attempt\.json$' -or $name -match '^(admit|inspect|complete)-completed-timing\.json$' -or $name -match '^(status|incident)-[a-f0-9]{32}\.json$'){return 'runtime-output'}
  throw 'READINESS_UNCLASSIFIED_EXCHANGE_FILE'
 }
 if($rel -eq 'ProtectedMission'){return 'state-root'}
 if($rel -in @('ProtectedMission\installation.json','ProtectedMission\metadata-canary')){return 'reference'}
 if($rel -in @('ProtectedMission\state-canary','ProtectedMission\authority.journal')){return 'runtime-state'}
 throw 'READINESS_UNCLASSIFIED_SURFACE'
}
function Get-PmaInventory([string]$Base='C:\ProgramData\Imperium') {
 $roots=@('ProtectedMissionCode','ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget','ProtectedMission','ProtectedMissionExchange','ProtectedMissionProbePlans','ProtectedMissionPostEnrollmentPlans')
 $items=@();foreach($name in $roots){$root=Join-Path $Base $name;$items+=@(Get-Item -LiteralPath $root -Force)+@(Get-ChildItem -LiteralPath $root -Recurse -Force)}
 $system='C:\Windows\System32\WindowsPowerShell\v1.0'
 $items+=@(Get-Item $system -Force)+@(Get-ChildItem $system -Recurse -Force)
 $parents=@($Base,(Split-Path $Base),[IO.Path]::GetPathRoot($Base),'C:\Windows','C:\Windows\System32','C:\Windows\System32\WindowsPowerShell')|Select-Object -Unique
 $items+=@($parents|ForEach-Object{Get-Item -LiteralPath $_ -Force})
 $rows=@($items|Sort-Object FullName -Unique|ForEach-Object{
  if($_.Attributes -band [IO.FileAttributes]::ReparsePoint){throw 'READINESS_REPARSE'}
  $acl=Get-Acl -LiteralPath $_.FullName
  $class=if($_.FullName -in $parents){'parent'}elseif($_.FullName.StartsWith($system,[StringComparison]::OrdinalIgnoreCase)){'immutable'}else{Get-PmaSurfaceClass $_.FullName $_.PSIsContainer $Base}
  [ordered]@{path=$_.FullName;directory=[bool]$_.PSIsContainer;class=$class;created_ticks=$_.CreationTimeUtc.Ticks;attributes=[int]$_.Attributes;owner=$acl.GetOwner([Security.Principal.SecurityIdentifier]).Value;group=$acl.GetGroup([Security.Principal.SecurityIdentifier]).Value;sddl=$acl.Sddl}
 })
 return [ordered]@{captured_ticks=[DateTime]::UtcNow.Ticks;items=$rows}
}
function Get-PmaRequiredProbes($Inventory,[ValidateSet('Runtime','Caller')][string]$Role) {
 $rows=[Collections.Generic.List[object]]::new()
 foreach($item in $Inventory.items) {
  $rights=[ordered]@{'read-or-list'=1;'write-data'=2;'append-data'=4;'delete'=65536;'write-dac'=262144;'write-owner'=524288}
  if($item.directory){$rights['delete-child']=64}
  if($item.class -eq 'parent'){$rights=[ordered]@{'delete-child'=64;'write-dac'=262144;'write-owner'=524288}}
  foreach($right in $rights.Keys) {
   # A legitimate Runtime owns its own journal and generated outputs.
   if($Role -eq 'Runtime' -and $item.class -in @('runtime-state','runtime-output') -and $right -in @('write-dac','write-owner')){continue}
   $success=($item.class -eq 'immutable' -and $right -eq 'read-or-list')
   if($Role -eq 'Runtime') {
    if($item.class -eq 'reference' -and $right -eq 'read-or-list'){$success=$true}
    if($item.class -in @('exchange-root','state-root') -and $right -in @('read-or-list','write-data')){$success=$true}
    if($item.class -in @('runtime-state','runtime-output') -and $right -in @('read-or-list','write-data','append-data','delete')){$success=$true}
   }
   $rows.Add([ordered]@{path=$item.path;right=$right;mask=$rights[$right];expected=if($success){'ACCESS_SUCCEEDED'}else{'ACCESS_DENIED'}})
  }
 }
 return ,$rows.ToArray()
}
function Get-PmaInstallationBinding([string]$Base) {
 $metadata=Read-PmaEvidence "$Base\ProtectedMission\installation.json"
 $public=Read-PmaEvidence "$Base\ProtectedMissionProbePlans\deployment-binding.json"
 foreach($key in @('runtime_sid','caller_sid','setup_session','package_manifest_sha256')){Assert-PmaEqual $metadata[$key] $public[$key] 'READINESS_INSTALLATION_BINDING'}
 if($metadata.runtime_sid -ceq $metadata.caller_sid -or $metadata.runtime_sid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $metadata.caller_sid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $metadata.setup_session -notmatch '^[a-f0-9]{32}$'){throw 'READINESS_INSTALLATION_IDENTITY'}
 return [ordered]@{setup_session=$metadata.setup_session;runtime_sid=$metadata.runtime_sid;caller_sid=$metadata.caller_sid;package_manifest_sha256=$metadata.package_manifest_sha256;installation_sha256=(Get-FileHash "$Base\ProtectedMission\installation.json").Hash;php_ini_sha256=(Get-FileHash "$Base\ProtectedMissionPHP\php.ini").Hash;base=$Base}
}
function Assert-PmaMeasurement($Measurement,$Plan,[string]$PlanHash,$Binding,[string]$Phase,$Inventory,[long]$NowTicks) {
 Assert-PmaEqual $Measurement.schema 'imperium.local-access-measurement/v2' 'READINESS_SCHEMA'
 Assert-PmaEqual $Measurement.binding $Binding 'READINESS_MEASUREMENT_BINDING'
 Assert-PmaEqual $Measurement.phase $Phase 'READINESS_PHASE'
 Assert-PmaEqual $Measurement.plan_sha256 $PlanHash 'READINESS_PLAN_HASH'
 Assert-PmaEqual $Measurement.role $Plan.role 'READINESS_ROLE'
 Assert-PmaToken $Measurement $Plan.sid
 if($Phase -eq 'current' -and $NowTicks-$Measurement.captured_ticks -gt [TimeSpan]::FromMinutes(15).Ticks){throw 'READINESS_CURRENT_MEASUREMENT_STALE'}
 if($Measurement.captured_ticks -lt $Inventory.captured_ticks -or $Measurement.captured_ticks -gt $NowTicks -or $NowTicks-$Measurement.captured_ticks -gt [TimeSpan]::FromHours(24).Ticks){throw 'READINESS_STALE_MEASUREMENT'}
 $required=Get-PmaRequiredProbes $Inventory $Plan.role
 if(@($Plan.probes).Count -ne $required.Count){throw 'READINESS_PLAN_COVERAGE'}
 if(@($Measurement.probes).Count -ne $required.Count){throw 'READINESS_ROW_COUNT'}
 for($i=0;$i -lt $required.Count;$i++) {
  $row=$Measurement.probes[$i];$want=$required[$i]
  if($row.mask -is [string] -or $row.win32_error -is [string] -or $Plan.probes[$i].mask -is [string]){throw 'READINESS_ROW_TYPE'}
  foreach($field in @('path','right','mask','expected')){
   if($Plan.probes[$i][$field] -cne $want[$field]){throw 'READINESS_PLAN_COVERAGE'}
   if($row[$field] -cne $want[$field]){throw 'READINESS_ROW_CHANGED'}
  }
  if($row.result -cne $want.expected){throw 'READINESS_RESULT'}
  if($row.win32_error -cne $(if($want.expected -eq 'ACCESS_SUCCEEDED'){0}else{5})){throw 'READINESS_NATIVE_ERROR'}
  if($row.pass -isnot [bool] -or -not $row.pass){throw 'READINESS_ROW_FAILED'}
 }
 Assert-PmaEqual $Measurement.result 'RECORDED_ACCESS_EXPECTATIONS_MET' 'READINESS_RESULT'
}
function Assert-PmaStartup($Startup,$Binding,[string]$Phase,[string]$Role,$Trust,[long]$NowTicks) {
 Assert-PmaEqual $Startup.binding $Binding 'READINESS_STARTUP_BINDING'
 Assert-PmaEqual $Startup.phase $Phase 'READINESS_STARTUP_PHASE'
 Assert-PmaEqual $Startup.role $Role 'READINESS_STARTUP_ROLE'
 Assert-PmaToken $Startup $Binding[($Role.ToLower()+'_sid')]
 if($Startup.captured_ticks -gt $NowTicks -or $NowTicks-$Startup.captured_ticks -gt [TimeSpan]::FromHours(24).Ticks){throw 'READINESS_STALE_STARTUP'}
 $checker=$Startup.checker;$cli=$Startup.cli
 if($Role -eq 'Caller') {
  Assert-PmaEqual $checker @{ExitCode=2;Output="PMA_RUNTIME_IDENTITY_REFUSED`r`n";Error=''} 'READINESS_EXPECTED_IDENTITY_REFUSAL'
  Assert-PmaEqual $cli @{ExitCode=2;Output='';Error="PMA_RUNTIME_IDENTITY_REFUSED`n"} 'READINESS_EXPECTED_IDENTITY_REFUSAL'
 } else {
  Assert-PmaEqual $checker @{ExitCode=0;Output="PMA_INSTALLATION_ACL_AND_IDENTITY_VERIFIED`r`n";Error=''} 'READINESS_STARTUP_CHECKER'
  if($Phase -eq 'pre') {Assert-PmaEqual $cli @{ExitCode=2;Output='';Error="PMA_TRUST_ABSENT`n"} 'READINESS_PREENROLLMENT_STARTUP'}
  else {
   Assert-PmaEqual $cli.ExitCode 0 'READINESS_TRUST_STARTUP';Assert-PmaEqual $cli.Error '' 'READINESS_TRUST_STARTUP'
   Assert-PmaEqual ($cli.Output|ConvertFrom-Json -AsHashtable -DateKind String) $Trust 'READINESS_ENROLLED_TRUST'
  }
 }
}
function Test-PmaReadiness($Ready,[string]$Base='C:\ProgramData\Imperium') {
 try {
  $binding=Get-PmaInstallationBinding $Base
  Assert-PmaEqual $Ready.schema $PmaReadinessSchema 'READINESS_SCHEMA'
  Assert-PmaEqual $Ready.binding $binding 'READINESS_BINDING'
  if([string]::IsNullOrWhiteSpace($Ready.transport) -or [string]::IsNullOrWhiteSpace($Ready.custody)){throw 'READINESS_OWNER_FACTS_REQUIRED'}
  $now=[DateTime]::UtcNow.Ticks
  if($Ready.captured_ticks -gt $now -or $now-$Ready.captured_ticks -gt [TimeSpan]::FromMinutes(15).Ticks){throw 'READINESS_STALE'}
  $null=& "$PSScriptRoot\Test-LocalIsolationInstalledPackage.ps1" -Base $Base -Manifest "$Base\ProtectedMissionExchange\package-manifest.json" -ManifestSha256 $binding.package_manifest_sha256
  $trust=Read-PmaEvidence "$Base\ProtectedMissionExchange\public-trust.json"
  $key=[Convert]::FromBase64String($trust.public_key)
  if($key.Length -ne 32){throw 'READINESS_TRUST_KEY'}
  $fingerprint=[Convert]::ToHexString([Security.Cryptography.SHA256]::HashData($key)).ToLowerInvariant()
  Assert-PmaEqual $Ready.public_fingerprint $fingerprint 'READINESS_FINGERPRINT'
  $enrolled=$trust.Clone();$enrolled['fingerprint']=$fingerprint;$enrolled['revoked']=$false
  $seconds=[DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
  if($trust.competence -cne 'APPROVE_CANONICAL_MISSION_PLAN' -or $trust.not_before -gt $seconds -or $trust.expires_at -le $seconds){throw 'READINESS_TRUST_STALE'}
  $live=Get-PmaInventory $Base
  $liveMap=@{};foreach($item in $live.items){$liveMap[$item.path]=$item}
  $previous=0;$groups=@{};$total=0
  Assert-PmaEqual @($Ready.phases.Keys|Sort-Object) @('current','post','pre') 'READINESS_PHASE_SET'
  foreach($phase in @('pre','post','current')) {
   $ref=$Ready.phases[$phase]
   $parent=if($phase -eq 'pre'){'ProtectedMissionProbePlans'}else{'ProtectedMissionPostEnrollmentPlans'}
   $dir=[IO.Path]::GetFullPath($ref.directory)
   if((Split-Path $dir) -cne "$Base\$parent" -or (Split-Path $dir -Leaf) -notmatch ('^'+$phase+'-[a-f0-9]{32}$')){throw 'READINESS_PHASE_PATH'}
   Assert-PmaEqual (Get-FileHash "$dir\detached-manifest.json").Hash $ref.sha256 'READINESS_EVIDENCE_HASH'
   $hashes=Read-PmaEvidence "$dir\detached-manifest.json"
   $files=@('inventory.json','Runtime-plan.json','Caller-plan.json','Runtime-access.json','Caller-access.json','Runtime-startup.json','Caller-startup.json')
   Assert-PmaEqual @($hashes.Keys|Sort-Object) @($files|Sort-Object) 'READINESS_EVIDENCE_SET'
   foreach($file in $files){Assert-PmaEqual (Get-FileHash "$dir\$file").Hash $hashes[$file] 'READINESS_EVIDENCE_HASH'}
   $inventory=Read-PmaEvidence "$dir\inventory.json"
   if($inventory.captured_ticks -lt $previous -or $inventory.captured_ticks -gt $now){throw 'READINESS_PHASE_ORDER'}
   $previous=$inventory.captured_ticks
   # Reconstruct historical finite inventory from retained installed objects' creation times.
   $expected=@($live.items|Where-Object{$_.created_ticks -le $inventory.captured_ticks})
   Assert-PmaEqual $inventory.items $expected 'READINESS_INVENTORY_CHANGED_OR_REDUCED'
   if($phase -eq 'pre' -and @($inventory.items|Where-Object{$_.path -eq "$Base\ProtectedMission\authority.journal"}).Count){throw 'READINESS_PRE_ALREADY_ENROLLED'}
   if($phase -ne 'pre' -and -not @($inventory.items|Where-Object{$_.path -eq "$Base\ProtectedMission\authority.journal"}).Count){throw 'READINESS_JOURNAL_ABSENT'}
   if($phase -eq 'current'){Assert-PmaEqual $inventory.items $live.items 'READINESS_CURRENT_COVERAGE_STALE'}
   foreach($role in @('Runtime','Caller')) {
    $plan=Read-PmaEvidence "$dir\$role-plan.json"
    Assert-PmaEqual $plan.schema 'imperium.local-isolation-plan/v2' 'READINESS_PLAN_SCHEMA'
    Assert-PmaEqual $plan.binding $binding 'READINESS_PLAN_BINDING';Assert-PmaEqual $plan.phase $phase 'READINESS_PLAN_PHASE'
    Assert-PmaEqual $plan.role $role 'READINESS_PLAN_ROLE';Assert-PmaEqual $plan.sid $binding[($role.ToLower()+'_sid')] 'READINESS_PLAN_SID'
    $measurement=Read-PmaEvidence "$dir\$role-access.json"
    Assert-PmaMeasurement $measurement $plan $hashes["$role-plan.json"] $binding $phase $inventory $now
    $startup=Read-PmaEvidence "$dir\$role-startup.json"
    if($startup.captured_ticks -lt $inventory.captured_ticks){throw 'READINESS_STARTUP_PRECEDES_PHASE'}
    Assert-PmaStartup $startup $binding $phase $role $enrolled $now
    Assert-PmaEqual $startup.groups $measurement.groups 'READINESS_TOKEN_GROUPS_CHANGED'
    if($groups.ContainsKey($role)){Assert-PmaEqual $measurement.groups $groups[$role] 'READINESS_TOKEN_GROUPS_CHANGED'}else{$groups[$role]=$measurement.groups}
    $total+=@($measurement.probes).Count
   }
  }
  return [ordered]@{result='LOCAL_ISOLATION_READINESS_VALID';binding=$binding;public_fingerprint=$fingerprint;public_trust=$enrolled;rows=$total;isolation_claim='Consistency of trusted observations only; owner custody and transport remain premises'}
 } catch {return [ordered]@{result='LOCAL_ISOLATION_READINESS_REFUSED';reason=$_.Exception.Message}}
}
