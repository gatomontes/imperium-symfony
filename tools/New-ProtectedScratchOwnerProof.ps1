# Owner administrator: disposable relocation only. Never creates accounts or real installation.
[CmdletBinding()]
param([Parameter(Mandatory)][string]$Package,[Parameter(Mandatory)][string]$ManifestSha256,
 [Parameter(Mandatory)][string]$Workspace,[Parameter(Mandatory)][string]$RuntimeSid,[Parameter(Mandatory)][string]$CallerSid)
$ErrorActionPreference='Stop'
if($Workspace -cnotmatch '^C:\\ProgramData\\PmaScratchProof-[a-f0-9]{32}$'){throw 'EXACT_FRESH_DISPOSABLE_PATH_REQUIRED'}
if(Test-Path -LiteralPath $Workspace){throw 'PRESERVE_EXISTING_PROOF'}
$principal=[Security.Principal.WindowsPrincipal]::new([Security.Principal.WindowsIdentity]::GetCurrent())
if(-not $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)){throw 'OWNER_ELEVATED_TERMINAL_REQUIRED'}
if($RuntimeSid -eq $CallerSid){throw 'DISTINCT_ACCOUNTS_REQUIRED'}
$admins=@(Get-LocalGroupMember (Get-LocalGroup -SID 'S-1-5-32-544')|ForEach-Object{$_.SID.Value})
foreach($sid in @($RuntimeSid,$CallerSid)){
 if($sid -notmatch '^S-1-5-21-(\d+-){3}\d+$' -or $sid -in $admins){throw 'EXISTING_STANDARD_ACCOUNTS_REQUIRED'}
 $null=([Security.Principal.SecurityIdentifier]$sid).Translate([Security.Principal.NTAccount])
}
$Package=(Resolve-Path $Package).Path
if((Get-FileHash "$Package/package-manifest.json").Hash -cne $ManifestSha256){throw 'PACKAGE_DIGEST_MISMATCH'}
$manifest=Get-Content "$Package/package-manifest.json" -Raw|ConvertFrom-Json -AsHashtable
$files=@(Get-ChildItem $Package -Recurse -Force -File)
if(@(Get-Item $Package)+@(Get-ChildItem $Package -Recurse -Force)|Where-Object{$_.Attributes -band [IO.FileAttributes]::ReparsePoint}){throw 'PACKAGE_REPARSE'}
if($files.Count -ne $manifest.Count+1){throw 'PACKAGE_FILE_SET'}
foreach($name in $manifest.Keys){if((Get-FileHash (Join-Path $Package $name)).Hash -ine $manifest[$name]){throw 'PACKAGE_BYTES_CHANGED'}}
New-Item -ItemType Directory -Path $Workspace|Out-Null
$acl=[Security.AccessControl.DirectorySecurity]::new();$acl.SetAccessRuleProtection($true,$false)
$acl.SetOwner([Security.Principal.SecurityIdentifier]'S-1-5-32-544');$acl.SetGroup([Security.Principal.SecurityIdentifier]'S-1-5-32-544')
foreach($sid in @('S-1-5-32-544','S-1-5-18',$RuntimeSid,$CallerSid)){
 $rights=if($sid -in @($RuntimeSid,$CallerSid)){'ReadAndExecute'}else{'FullControl'}
 $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]$sid,[Security.AccessControl.FileSystemRights]$rights,[Security.AccessControl.InheritanceFlags]'ContainerInherit,ObjectInherit',[Security.AccessControl.PropagationFlags]::None,[Security.AccessControl.AccessControlType]::Allow))
}
Set-Acl $Workspace $acl
$stage="$Workspace/package";$base="$Workspace/installation".Replace('/','\')
Copy-Item -LiteralPath $Package -Destination $stage -Recurse
$changes=@()
foreach($file in Get-ChildItem $stage -Recurse -File|Where-Object{$_.Extension -in @('.php','.ps1','.ini','.json') -and $_.Name -ne 'package-manifest.json'}){
 $old=[IO.File]::ReadAllText($file.FullName)
 $new=$old.Replace('C:\ProgramData\Imperium',$base).Replace('C:/ProgramData/Imperium',$base.Replace('\','/'))
 if($new -cne $old){
  $before=(Get-FileHash $file.FullName).Hash
  [IO.File]::WriteAllText($file.FullName,$new,[Text.UTF8Encoding]::new($false))
  $changes+=@{path=$file.FullName.Substring($stage.Length+1);original_sha256=$before;relocated_sha256=(Get-FileHash $file.FullName).Hash}
 }
}
# Hash every relocated byte before applying the exact production installers.
$relocated=@{}
foreach($file in Get-ChildItem $stage -Recurse -File|Where-Object{$_.Name -ne 'package-manifest.json'}){
 $relocated[$file.FullName.Substring($stage.Length+1).Replace('\','/')]=(Get-FileHash $file.FullName).Hash
}
[IO.File]::WriteAllText("$stage/package-manifest.json",($relocated|ConvertTo-Json -Depth 10),[Text.UTF8Encoding]::new($false))
$digest=(Get-FileHash "$stage/package-manifest.json").Hash
& "$stage/ProtectedMissionCode/tools/Install-LocalIsolationOwnerPackage.ps1" -Package $stage -ManifestSha256 $digest -RuntimeSid $RuntimeSid -CallerSid $CallerSid
if(-not(Test-Path "$base/ProtectedMission/installation.json")){throw 'DISPOSABLE_SETUP_INCOMPLETE'}
# Native old-policy canary and active scratch canary, outside measured installation.
$oldRoot="$Workspace/old-state";New-Item -ItemType Directory $oldRoot|Out-Null
$oldAcl=Get-Acl "$base/ProtectedMission";Set-Acl $oldRoot $oldAcl
$canary="$Workspace/scratch-canary";New-Item -ItemType Directory $canary|Out-Null
. "$base/ProtectedMissionCode/tools/ProtectedMissionScratch.ps1"
Set-Acl $canary (New-PmaScratchAcl $RuntimeSid)
$proof=@{schema='imperium.disposable-scratch-owner-proof/v1';workspace=$Workspace;base=$base;source_package=$Package;source_digest=$ManifestSha256;relocated_digest=$digest;changes=$changes;runtime_sid=$RuntimeSid;caller_sid=$CallerSid;old_state=$oldRoot;scratch_canary=$canary;native_proof='NOT_RUN';real_installation=$false}
[IO.File]::WriteAllText("$Workspace/proof.json",($proof|ConvertTo-Json -Depth 20),[Text.UTF8Encoding]::new($false))
$proof|ConvertTo-Json -Depth 20
