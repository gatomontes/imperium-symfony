# Owner administrator only, after reviewing this exact package and proposed accounts.
[CmdletBinding(SupportsShouldProcess)]
param([Parameter(Mandatory)][string]$Package,[Parameter(Mandatory)][string]$ManifestSha256,
      [Parameter(Mandatory)][string]$RuntimeSid,[Parameter(Mandatory)][string]$CallerSid)
$ErrorActionPreference='Stop'
$base='C:\ProgramData\Imperium'
if((Get-FileHash (Join-Path $Package 'package-manifest.json')).Hash -ine $ManifestSha256){throw 'PACKAGE_DIGEST_MISMATCH'}
$manifest=Get-Content (Join-Path $Package 'package-manifest.json') -Raw|ConvertFrom-Json -AsHashtable
$actual=@(Get-ChildItem -LiteralPath $Package -Recurse -Force -File|ForEach-Object{$_.FullName.Substring($Package.TrimEnd('\','/').Length+1).Replace('\','/')}|Where-Object{$_ -ne 'package-manifest.json'}|Sort-Object)
if(($actual -join "`n") -cne (($manifest.Keys|Sort-Object) -join "`n")){throw 'PACKAGE_FILE_SET_MISMATCH'}
foreach($relative in $manifest.Keys){if((Get-FileHash -LiteralPath (Join-Path $Package $relative)).Hash -ine $manifest[$relative]){throw "PACKAGE_FILE_MISMATCH: $relative"}}
if(@(Get-Item $Package)+@(Get-ChildItem $Package -Recurse -Force)|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'PACKAGE_REPARSE_REFUSED'}
if($RuntimeSid -eq $CallerSid){throw 'DISTINCT_ACCOUNTS_REQUIRED'}
foreach($sid in @($RuntimeSid,$CallerSid)){$null=([Security.Principal.SecurityIdentifier]$sid).Translate([Security.Principal.NTAccount])}
if(Test-Path -LiteralPath $base){throw 'EXISTING_PARENT_REQUIRES_SEPARATE_REVIEW_NO_REPLACEMENT'}
if(-not $PSCmdlet.ShouldProcess($base,'Fresh protected parent, code/state/PHP/shell/target/exchange; no account creation or trust enrollment')){return}
$principal=[Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'OWNER_ELEVATED_TERMINAL_REQUIRED'}
function Set-OwnerAcl([string]$Path,[bool]$RuntimeWrite=$false,[bool]$CallerRead=$true){
    $dir=(Get-Item -LiteralPath $Path -Force).PSIsContainer
    $acl=if($dir){[Security.AccessControl.DirectorySecurity]::new()}else{[Security.AccessControl.FileSecurity]::new()}
    $acl.SetAccessRuleProtection($true,$false);$acl.SetOwner([Security.Principal.SecurityIdentifier]'S-1-5-32-544');$acl.SetGroup([Security.Principal.SecurityIdentifier]'S-1-5-32-544')
    $inherit=if($dir){[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit'}else{[Security.AccessControl.InheritanceFlags]::None}
    $grants=@{'S-1-5-32-544'='FullControl';'S-1-5-18'='FullControl'};$grants[$RuntimeSid]=if($RuntimeWrite){'Modify'}else{'ReadAndExecute'}
    if($CallerRead){$grants[$CallerSid]='ReadAndExecute'}
    foreach($sid in $grants.Keys){$acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]$grants[$sid],$inherit,[Security.AccessControl.PropagationFlags]::None,[Security.AccessControl.AccessControlType]::Allow))}
    Set-Acl -LiteralPath $Path -AclObject $acl
}
[void][IO.Directory]::CreateDirectory($base);Set-OwnerAcl $base
# Any subsequent error leaves this fresh partial setup in place for owner inspection.
foreach($name in @('ProtectedMissionPHP','ProtectedMissionShell','ProtectedMissionTarget')){
    Copy-Item -LiteralPath (Join-Path $Package $name) -Destination $base -Recurse
    foreach($item in @((Get-Item (Join-Path $base $name)))+@(Get-ChildItem (Join-Path $base $name) -Recurse -Force)){Set-OwnerAcl $item.FullName}
}
& (Join-Path $Package 'ProtectedMissionCode/tools/Install-ProtectedMission.ps1') -Source (Join-Path $Package 'ProtectedMissionCode') -RuntimeSid $RuntimeSid -CallerSid $CallerSid
$exchange=Join-Path $base 'ProtectedMissionExchange';[void][IO.Directory]::CreateDirectory($exchange);Set-OwnerAcl $exchange $true $false
foreach($name in @('target-inventory.json','mission-draft.json','package-manifest.json')){Copy-Item -LiteralPath (Join-Path $Package $name) -Destination $exchange;Set-OwnerAcl (Join-Path $exchange $name) $false $false}
# Harmless pre-enrollment canaries. Their ACLs are the exact state/metadata ACLs.
foreach($pair in @(@('state-canary',"$base\ProtectedMission"),@('metadata-canary',"$base\ProtectedMission\installation.json"))){
    $file=Join-Path "$base\ProtectedMission" $pair[0];[IO.File]::WriteAllText($file,'public disposable access canary')
    if($pair[0] -eq 'metadata-canary'){$acl=Get-Acl -LiteralPath $pair[1];Set-Acl -LiteralPath $file -AclObject $acl}
}
# Only file creation is permitted here; directories belong in dedicated scratch.
# Owner reference children cannot be replaced
# using DELETE_CHILD. File inheritance gives Runtime its required output rights.
function Set-PmaOperationalDirectory([string]$Path){
    Set-OwnerAcl $Path $false $false
    $acl=Get-Acl -LiteralPath $Path
    $sid=[Security.Principal.SecurityIdentifier]$RuntimeSid
    foreach($rule in @($acl.GetAccessRules($true,$false,[Security.Principal.SecurityIdentifier]))){if($rule.IdentityReference.Value -eq $RuntimeSid){[void]$acl.RemoveAccessRuleSpecific($rule)}}
    $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new($sid,[Security.AccessControl.FileSystemRights]'ReadAndExecute,CreateFiles',[Security.AccessControl.AccessControlType]::Allow))
    $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new($sid,[Security.AccessControl.FileSystemRights]::Modify,[Security.AccessControl.InheritanceFlags]::ObjectInherit,[Security.AccessControl.PropagationFlags]::InheritOnly,[Security.AccessControl.AccessControlType]::Allow))
    Set-Acl -LiteralPath $Path -AclObject $acl
}
Set-PmaOperationalDirectory $exchange
Set-PmaOperationalDirectory "$base\ProtectedMission"
foreach($name in @('ProtectedMissionProbePlans','ProtectedMissionPostEnrollmentPlans')){
    [void][IO.Directory]::CreateDirectory("$base\$name");Set-OwnerAcl "$base\$name"
}
$binding=[ordered]@{runtime_sid=$RuntimeSid;caller_sid=$CallerSid;setup_session=[guid]::NewGuid().ToString('N');package_manifest_sha256=$ManifestSha256.ToUpperInvariant()}
$path="$base\ProtectedMission\installation.json"
$metadata=Get-Content -LiteralPath $path -Raw|ConvertFrom-Json -AsHashtable
foreach($key in $binding.Keys){$metadata[$key]=$binding[$key]}
[IO.File]::WriteAllText($path,($metadata|ConvertTo-Json -Compress),[Text.UTF8Encoding]::new($false))
$path="$base\ProtectedMissionProbePlans\deployment-binding.json"
[IO.File]::WriteAllText($path,($binding|ConvertTo-Json -Compress),[Text.UTF8Encoding]::new($false));Set-OwnerAcl $path
foreach($name in @('owner-readiness.json','public-trust.json')){
    $path=Join-Path $exchange $name
    [IO.File]::WriteAllText($path,'PMA_RESERVED_UNMEASURED',[Text.UTF8Encoding]::new($false));Set-OwnerAcl $path $false $false
}
'OWNER_FRESH_SETUP_APPLIED_NOT_YET_ISOLATION_PROVED'
